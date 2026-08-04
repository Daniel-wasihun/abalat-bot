<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Laravel\Passport\AccessToken;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class UserSessionTest extends TestCase {
    use RefreshDatabase;

    /**
     * Helper: Authenticate a user with a known token ID so that
     * the controller's resolveCurrentTokenId() can find the session.
     * Passport::actingAs creates an AccessToken missing oauth_access_token_id,
     * so we create a custom one with it set.
     */
    private function actWithToken(User $user, string $tokenId): void {
        $token = new AccessToken([
            'oauth_access_token_id' => $tokenId,
            'oauth_user_id' => $user->id,
            'oauth_scopes' => [],
        ]);

        $user->withAccessToken($token);
        app('auth')->guard('api')->setUser($user);
        app('auth')->shouldUse('api');
    }

    #[Test]
    public function user_can_see_active_sessions() {
        $user = User::factory()->create();
        $tokenId = (string) Str::uuid();

        $this->actWithToken($user, $tokenId);

        // Create a session that matches the current token
        UserSession::create([
            'user_id' => $user->id,
            'session_id' => $tokenId,
            'ip_address' => '127.0.0.1',
            'device_name' => 'Test Device',
            'is_active' => true,
            'last_active_at' => now(),
        ]);

        $response = $this->getJson('/api/sessions');

        $response->assertStatus(200);
        $response->assertJsonFragment(['is_current' => true]);
        $response->assertJsonStructure([
            'user' => [
                'sessions' => [
                    '*' => ['id', 'device_name', 'ip_address', 'is_current']
                ]
            ]
        ]);
    }

    #[Test]
    public function new_device_cannot_logout_old_session() {
        $user = User::factory()->create();

        // 1. Create an established (old) session — March 2025
        Carbon::setTestNow(Carbon::create(2025, 3, 1));
        $oldSession = UserSession::create([
            'user_id' => $user->id,
            'session_id' => 'established_token_id',
            'ip_address' => '1.1.1.1',
            'device_name' => 'Old Legacy PC',
            'is_active' => true,
        ]);

        // 2. Authenticate as a new device — August 2025
        Carbon::setTestNow(Carbon::create(2025, 8, 1));
        $newTokenId = (string) Str::uuid();
        $this->actWithToken($user, $newTokenId);

        UserSession::create([
            'user_id' => $user->id,
            'session_id' => $newTokenId,
            'ip_address' => '2.2.2.2',
            'device_name' => 'New Phone',
            'is_active' => true,
        ]);

        // 3. Attempt to kill old session from new device — should be blocked
        $response = $this->postJson("/api/sessions/{$oldSession->id}/logout");

        $response->assertStatus(403);
        $this->assertTrue($oldSession->fresh()->is_active, 'Old session should still be active');

        Carbon::setTestNow(); // Reset
    }

    #[Test]
    public function logout_others_protects_established_sessions() {
        $user = User::factory()->create();

        // 1. Create established session — January 2025
        Carbon::setTestNow(Carbon::create(2025, 1, 1));
        $established = UserSession::create([
            'user_id'    => $user->id,
            'session_id' => 'legacy_token',
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        // 2. Create recent peer session — April 2025
        Carbon::setTestNow(Carbon::create(2025, 4, 1));
        $recent = UserSession::create([
            'user_id'    => $user->id,
            'session_id' => 'recent_peer_token',
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        // 3. Authenticate as the CURRENT (established) device — also April 2025 but
        //    we freeze time to June so that 'now' is > 1 month after its created_at,
        //    making isNewDevice = false.  An established device can kick ALL others.
        $currentTokenId = (string) Str::uuid();
        Carbon::setTestNow(Carbon::create(2025, 4, 2)); // created_at = April 2
        $this->actWithToken($user, $currentTokenId);
        UserSession::create([
            'user_id'    => $user->id,
            'session_id' => $currentTokenId,
            'status'     => UserSession::STATUS_ACTIVE,
            'is_active'  => true,
        ]);

        // Move time forward so the current session is > 1 month old (established)
        Carbon::setTestNow(Carbon::create(2025, 6, 15));

        // 4. Logout all other sessions — established current device can kick everyone
        $response = $this->postJson('/api/sessions/logout-other');

        $response->assertStatus(200);
        // Both the really-old session AND the recent peer should be terminated
        $this->assertFalse($established->fresh()->is_active, 'Established peer should be terminated');
        $this->assertFalse($recent->fresh()->is_active,      'Recent peer should be terminated');

        Carbon::setTestNow(); // Reset
    }
}
