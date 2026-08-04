<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\AccessToken;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Events\UserSessionUpdated;
use App\Notifications\NewDeviceNotification;
use PHPUnit\Framework\Attributes\Test;

class TrackUserDeviceTest extends TestCase {
    use RefreshDatabase;

    // ── Helper ───────────────────────────────────────────────────────────────

    private function actWithToken(User $user, string $tokenId, string $userAgent = 'TestBrowser/1.0'): void {
        $token = new AccessToken([
            'oauth_access_token_id' => $tokenId,
            'oauth_user_id'         => $user->id,
            'oauth_scopes'          => [],
        ]);

        $user->withAccessToken($token);
        app('auth')->guard('api')->setUser($user);
        app('auth')->shouldUse('api');

        // Inject consistent UA so fingerprinting is deterministic
        $this->withHeader('User-Agent', $userAgent);
    }

    // ── Rule 1: No notification on first-ever login ───────────────────────────

    #[Test]
    public function no_notification_when_no_other_sessions_exist(): void {
        Event::fake([UserSessionUpdated::class]);
        Notification::fake();

        $user    = User::factory()->create();
        $tokenId = (string) Str::uuid();
        $this->actWithToken($user, $tokenId);

        $this->getJson('/api/me')->assertStatus(200);

        // Session record created
        $this->assertDatabaseHas('user_sessions', [
            'user_id'    => $user->id,
            'session_id' => $tokenId,
            'status'     => UserSession::STATUS_ACTIVE,
        ]);

        // No notification — there were no other active sessions at login time
        Notification::assertNotSentTo($user, NewDeviceNotification::class);
    }

    // ── Rule 2: New-device notification sent to all existing sessions ─────────

    #[Test]
    public function new_device_notification_sent_when_other_active_sessions_exist(): void {
        Event::fake([UserSessionUpdated::class]);
        Notification::fake();

        $user = User::factory()->create();

        // Pre-existing active session (Device A)
        UserSession::create([
            'user_id'    => $user->id,
            'session_id' => 'session-device-a',
            'ip_address' => '1.2.3.4',
            'device_name'=> 'Chrome on Windows',
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        // Device B logs in
        $tokenId = (string) Str::uuid();
        $this->actWithToken($user, $tokenId, 'Firefox/110 (Linux)');
        $this->getJson('/api/me')->assertStatus(200);

        // Notification with type=new_device sent
        Notification::assertSentTo(
            $user,
            NewDeviceNotification::class,
            fn($notif) =>
                $notif->sessionData['notification_type'] === NewDeviceNotification::TYPE_NEW_DEVICE &&
                $notif->sessionData['session_id'] === $tokenId
        );
    }

    // ── Rule 3: Same device re-login after normal logout → new_device (not alarm) ──

    #[Test]
    public function same_device_after_logged_out_gets_new_device_type(): void {
        Notification::fake();
        Event::fake([UserSessionUpdated::class]);

        $user    = User::factory()->create();
        $ua      = 'Chrome/100 (Windows NT 10.0)';

        // Device A was previously logged out (not terminated)
        UserSession::create([
            'user_id'     => $user->id,
            'session_id'  => 'old-session-logged-out',
            'user_agent'  => $ua,
            'device_name' => 'Chrome on Windows',
            'status'      => UserSession::STATUS_LOGGED_OUT,
            'is_active'   => false,
        ]);

        // Another active session so notification WILL fire
        UserSession::create([
            'user_id'    => $user->id,
            'session_id' => 'session-device-b',
            'device_name'=> 'Safari on iPhone',
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        $newTokenId = (string) Str::uuid();
        $this->actWithToken($user, $newTokenId, $ua);
        $this->getJson('/api/me')->assertStatus(200);

        // Should send new_device (not terminated_relogin) because prior status was logged_out
        Notification::assertSentTo(
            $user,
            NewDeviceNotification::class,
            fn($notif) =>
                $notif->sessionData['notification_type'] === NewDeviceNotification::TYPE_NEW_DEVICE
        );
    }

    // ── Rule 4: Terminated session re-login triggers alarming notification ────

    #[Test]
    public function terminated_session_relogin_sends_alarming_notification(): void {
        Notification::fake();
        Event::fake([UserSessionUpdated::class]);

        $user = User::factory()->create();
        $ua   = 'Chrome/100 (Windows NT 10.0)';

        // Device A was terminated by another device
        UserSession::create([
            'user_id'     => $user->id,
            'session_id'  => 'old-session-terminated',
            'user_agent'  => $ua,
            'device_name' => 'Chrome on Windows',
            'status'      => UserSession::STATUS_TERMINATED,
            'is_active'   => false,
        ]);

        // Device B is currently active (will receive the notification)
        UserSession::create([
            'user_id'    => $user->id,
            'session_id' => 'session-device-b-active',
            'device_name'=> 'Safari on iPhone',
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        // Device A logs in again from the same fingerprint
        $newTokenId = (string) Str::uuid();
        $this->actWithToken($user, $newTokenId, $ua);
        $this->getJson('/api/me')->assertStatus(200);

        // Must use the alarming terminated_relogin type
        Notification::assertSentTo(
            $user,
            NewDeviceNotification::class,
            fn($notif) =>
                $notif->sessionData['notification_type'] === NewDeviceNotification::TYPE_TERMINATED_RELOGIN
        );

        // New session record created with status active
        $this->assertDatabaseHas('user_sessions', [
            'user_id'    => $user->id,
            'session_id' => $newTokenId,
            'status'     => UserSession::STATUS_ACTIVE,
        ]);
    }

    // ── Rule 5: Every new token = its own session (no fingerprint consolidation) ──

    #[Test]
    public function every_new_token_creates_a_separate_session_record(): void {
        Event::fake([UserSessionUpdated::class]);
        Notification::fake();

        $user = User::factory()->create();
        $ua   = 'Chrome/100 (Windows NT 10.0)';

        // First login from this browser
        $token1 = (string) Str::uuid();
        $this->actWithToken($user, $token1, $ua);
        $this->getJson('/api/me')->assertStatus(200);

        // Second login from the identical browser (same UA) — new token
        Cache::forget("session_sync_{$token1}");
        $token2 = (string) Str::uuid();
        $this->actWithToken($user, $token2, $ua);
        $this->getJson('/api/me')->assertStatus(200);

        // Two separate session rows must exist
        $this->assertDatabaseHas('user_sessions', ['session_id' => $token1]);
        $this->assertDatabaseHas('user_sessions', ['session_id' => $token2]);
        $this->assertSame(
            2,
            UserSession::where('user_id', $user->id)->count()
        );
    }

    // ── Rule 6: Existing active session → no new record, no notification ──────

    #[Test]
    public function existing_active_session_refreshes_heartbeat_only(): void {
        Event::fake([UserSessionUpdated::class]);
        Notification::fake();

        $user    = User::factory()->create();
        $tokenId = (string) Str::uuid();

        UserSession::create([
            'user_id'        => $user->id,
            'session_id'     => $tokenId,
            'device_name'    => 'Chrome on Linux',
            'status'         => UserSession::STATUS_ACTIVE,
            'is_active'      => true,
            'last_active_at' => now()->subHour(),
        ]);

        $this->actWithToken($user, $tokenId);
        $this->getJson('/api/me')->assertStatus(200);

        // Still just one session row
        $this->assertSame(1, UserSession::where('user_id', $user->id)->count());

        // No notification
        Notification::assertNotSentTo($user, NewDeviceNotification::class);

        // last_active_at was refreshed
        $session = UserSession::where('session_id', $tokenId)->first();
        $this->assertTrue($session->last_active_at->gt(now()->subMinute()));
    }

    // ── Rule 7: Session status model helpers work correctly ───────────────────

    #[Test]
    public function session_status_helpers_set_correct_state(): void {
        $user = User::factory()->create();

        $session = UserSession::create([
            'user_id'    => $user->id,
            'session_id' => (string) Str::uuid(),
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        $this->assertTrue($session->isActive());
        $this->assertFalse($session->isTerminated());

        $session->markAsLoggedOut();
        $session->refresh();

        $this->assertSame(UserSession::STATUS_LOGGED_OUT, $session->status);
        $this->assertFalse($session->is_active);
        $this->assertTrue($session->isLoggedOut());

        // Restore and test terminate
        $session->update(['status' => UserSession::STATUS_ACTIVE, 'is_active' => true]);
        $session->markAsTerminated();
        $session->refresh();

        $this->assertSame(UserSession::STATUS_TERMINATED, $session->status);
        $this->assertFalse($session->is_active);
        $this->assertTrue($session->isTerminated());
    }
}
