<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_otp()
    {
        Mail::fake();

        $user = User::forceCreate([
            'email' => 'otp_test@lms.com',
            'name' => ['en' => 'OTP User'],
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/forgot-password/send-otp', [
            'email' => $user->email
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        
        Mail::assertQueued(PasswordResetOtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_verify_otp_validates_correctly()
    {
        $email = 'verify_test@lms.com';
        $otp = '123456';
        
        User::forceCreate([
            'email' => $email,
            'name' => ['en' => 'Verify User'],
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $otp,
            'created_at' => now()
        ]);

        // Success
        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $email,
            'otp' => $otp
        ])->assertStatus(200);

        // Fail - Wrong OTP
        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $email,
            'otp' => '000000'
        ])->assertStatus(422);
    }
}
