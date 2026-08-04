<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\NewDeviceNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class NewDeviceNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test notification contains the correct structure and channels.
     */
    public function test_notification_channels_and_data_structure()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => ['en' => 'Test User']
        ]);

        $sessionData = [
            'device_name' => 'Chrome on Windows',
            'location' => 'Addis Ababa, Ethiopia',
            'session_id' => 'test_session_123',
        ];

        $user->notify(new NewDeviceNotification($sessionData));

        Notification::assertSentTo(
            $user,
            NewDeviceNotification::class,
            function ($notification, $channels) use ($sessionData, $user) {
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);
                $this->assertContains('broadcast', $channels);

                $data = $notification->toArray($user);
                $this->assertEquals($sessionData['device_name'], $data['device_name']);
                $this->assertEquals($sessionData['location'], $data['location']);
                $this->assertEquals('/dashboard/profile/devices', $data['link']);

                return true;
            }
        );
    }

    /**
     * Test security action routes are correctly generated.
     */
    public function test_signed_urls_generation()
    {
        $user = User::factory()->create();
        $sessionId = 'signed_test_session';
        
        $notification = new NewDeviceNotification([
            'device_name' => 'Test Device',
            'location' => 'Test Location',
            'session_id' => $sessionId,
        ]);

        $mail = $notification->toMail($user);
        
        $this->assertNotNull($mail->approve_url);
        $this->assertNotNull($mail->terminate_url);
        $this->assertNotNull($mail->lock_url);

        // To validate signed URLs, we simulate a request to that URL
        $this->get($mail->approve_url)->assertStatus(302);
        $this->get($mail->terminate_url)->assertStatus(302);
        $this->get($mail->lock_url)->assertStatus(302);
    }
}
