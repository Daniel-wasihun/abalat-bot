<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Helpers\Response;
use App\Services\BackMessage;

class ForgotPasswordController extends Controller {
    public function sendOtp(Request $request) {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $email = $request->email;
        $otp = rand(100000, 999999);

        // Update or Insert OTP
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $otp, 'created_at' => now()]
        );

        // Dispatch OTP Email
        Mail::to($email)->queue(new \App\Mail\PasswordResetOtpMail($otp));

        return Response::_200(['message' => BackMessage::get('otp_sent')]);
    }

    public function verifyOtp(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$record) {
            return Response::_422(BackMessage::get('otp_invalid'));
        }

        // Check expiry (e.g. 15 mins)
        if (now()->diffInMinutes($record->created_at) > 15) {
            return Response::_422(BackMessage::get('otp_expired'));
        }

        return Response::_200(['message' => BackMessage::get('otp_verified')]);
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&!#%^&*()\-+_={}\[\]|\\:;"\'<>,.\/]).+$/', 'confirmed']
        ], [
            'password.confirmed' => 'auth.password_mismatch',
            'password.regex' => 'validation.password_complexity',
            'password.min' => 'validation.min_length|count=8',
            'password.required' => 'validation.required',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$record) {
            return Response::_422(BackMessage::get('otp_invalid'));
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            return Response::_422(BackMessage::get('otp_expired'));
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return Response::_200(['message' => BackMessage::get('password_reset_success')]);
    }
}
