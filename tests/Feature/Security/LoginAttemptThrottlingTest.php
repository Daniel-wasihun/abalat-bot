<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginAttemptThrottlingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that login attempts are tracked separately for different IPs and Devices.
     */
    public function test_login_attempts_are_isolated_by_ip_and_device()
    {
        $email = 'test@example.com';
        $password = 'WrongPassword123!';

        // 1. Fail login from IP 1, Device A
        $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password
        ], [
            'REMOTE_ADDR' => '1.1.1.1',
            'User-Agent' => 'Device-A'
        ])->assertStatus(401);

        $this->assertEquals(1, LoginThrottle::where('ip_address', '1.1.1.1')->where('user_agent', 'Device-A')->first()->attempts);

        // 2. Fail login from IP 2, Device A (Same device, different IP)
        $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password
        ], [
            'REMOTE_ADDR' => '2.2.2.2',
            'User-Agent' => 'Device-A'
        ])->assertStatus(401);

        $this->assertEquals(1, LoginThrottle::where('ip_address', '2.2.2.2')->where('user_agent', 'Device-A')->first()->attempts);

        // 3. Fail login from IP 1, Device B (Same IP, different device)
        $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password
        ], [
            'REMOTE_ADDR' => '1.1.1.1',
            'User-Agent' => 'Device-B'
        ])->assertStatus(401);

        $this->assertEquals(1, LoginThrottle::where('ip_address', '1.1.1.1')->where('user_agent', 'Device-B')->first()->attempts);

        // Verify that the first attempt is still at 1 and not affected by others
        $this->assertEquals(1, LoginThrottle::where('ip_address', '1.1.1.1')->where('user_agent', 'Device-A')->first()->attempts);
    }
}
