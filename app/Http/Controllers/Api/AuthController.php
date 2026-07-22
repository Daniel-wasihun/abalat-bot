<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->authService->login($request->email, $request->password);

        if (!$result) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        return response()->json($result);
    }

    public function profile(Request $request)
    {
        $admin = $request->attributes->get('admin');
        return response()->json(['admin' => $admin]);
    }

    public function updateProfile(Request $request)
    {
        $admin = $request->attributes->get('admin');
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'avatar' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = $this->authService->updateProfile($admin['id'], [
            'name' => $request->name,
            'email' => $request->email,
            'avatar' => $request->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($request->name),
        ]);

        return response()->json(['message' => 'Profile updated successfully', 'admin' => $updated]);
    }

    public function changePassword(Request $request)
    {
        $admin = $request->attributes->get('admin');

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->authService->changePassword($admin['id'], $request->current_password, $request->new_password);

        if (!$success) {
            return response()->json(['message' => 'Incorrect current password'], 400);
        }

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $token = $this->authService->generatePasswordResetToken($request->email);

        if (!$token) {
            return response()->json(['message' => 'If the email exists, a password reset link has been generated.'], 200);
        }

        // For local development and demonstration, return the reset token directly so the admin can reset password
        return response()->json([
            'message' => 'Password reset token generated successfully.',
            'reset_token' => $token,
            'reset_url' => url("/reset-password?email=" . urlencode($request->email) . "&token=" . $token),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->authService->resetPassword($request->email, $request->token, $request->password);

        if (!$success) {
            return response()->json(['message' => 'Invalid reset token or expired link.'], 400);
        }

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

    public function logout()
    {
        return response()->json(['message' => 'Logged out successfully']);
    }
}
