<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SuspiciousActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SecurityFeatureTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function it_blocks_honeypot_honey_field_submissions() {
        $response = $this->postJson('/api/login', [
            'email' => 'bot@example.com',
            'password' => 'password',
            '_hp_email_verification' => 'spammed',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('suspicious_activities', [
            'activity_type' => 'honeypot_honey_field',
            'severity' => 4,
        ]);
    }

    #[Test]
    public function it_blocks_fast_honeypot_submissions() {
        $tooFastTime = btoa(time()); // Current time, so it's 0s difference

        $response = $this->postJson('/api/login', [
            'email' => 'human@example.com',
            'password' => 'password',
            '_hp_timestamp' => $tooFastTime,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Security check failed. Please try again.']);

        $this->assertDatabaseHas('suspicious_activities', [
            'activity_type' => 'honeypot_fast_submit',
        ]);
    }

    #[Test]
    public function it_detects_malicious_sql_patterns() {
        $response = $this->postJson('/api/login', [
            'email' => "admin@example.com' UNION SELECT 1,2,3--",
            'password' => 'password',
            '_hp_timestamp' => btoa(time() - 10),
        ]);

        $this->assertDatabaseHas('suspicious_activities', [
            'activity_type' => 'pattern_detected_sql_injection',
        ]);
    }

    #[Test]
    public function it_strips_unauthorized_fields_from_non_admins() {
        $user = User::factory()->create();
        // Assume 'user' role is not admin

        $response = $this->actingAs($user, 'api')->putJson('/api/profile', [
            'name' => ['en' => 'New Name'],
            'role_id' => 1, // Restricted field
            '_hp_timestamp' => btoa(time() - 10),
        ]);

        $this->assertDatabaseHas('suspicious_activities', [
            'user_id' => $user->id,
            'activity_type' => 'unauthorized_field_submission',
        ]);
    }

    #[Test]
    public function it_returns_security_headers() {
        $response = $this->getJson('/api/languages');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Strict-Transport-Security');
    }

    #[Test]
    public function it_temporarily_blocks_ips_with_too_many_suspicious_activities() {
        $ip = '123.123.123.123';

        // Simulate 10 suspicious activities
        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/api/login', [
                    'email' => 'bot@example.com',
                    '_hp_email_verification' => 'spam',
                ]);
        }

        $this->assertTrue(Cache::has('blocked_ip_' . $ip));

        // Next request should be blocked
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson('/api/languages');

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Your access has been temporarily suspended due to suspicious activity. Please contact support.']);
    }
}

function btoa($data) {
    return base64_encode($data);
}
