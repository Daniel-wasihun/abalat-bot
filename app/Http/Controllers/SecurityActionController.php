<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class SecurityActionController extends Controller {
    /**
     * Approve the session (Mark it as verified)
     */
    public function approveSession(Request $request, $sessionId) {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173') . '/dashboard';

        return redirect()->away($frontendUrl . '?notif=session_approved');
    }

    /**
     * Terminate the specific session
     */
    public function terminateSession(Request $request, $sessionId) {
        $session = UserSession::where('session_id', $sessionId)->first();

        if ($session) {
            // Revoke the Passport token
            DB::table('oauth_access_tokens')
                ->where('id', $sessionId)
                ->update(['revoked' => true]);

            // Use markAsTerminated() — NOT markAsLoggedOut() — so that the next
            // login from this device fingerprint triggers the more-alarming
            // "terminated session re-login" security notification.
            $session->markAsTerminated();

            // 📡 Broadcast termination to the affected device for real-time logout
            try {
                $user = User::find($session->user_id);
                if ($user) {
                    event(new \App\Events\SessionTerminated($user->id, $session->session_id, 'security_action'));
                }
            } catch (\Exception $e) {
                // Ignore
            }

            $msg = 'Session terminated successfully.';
        } else {
            $msg = 'Session not found or already terminated.';
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173') . '/login';
        return redirect()->away($frontendUrl . '?message=' . urlencode($msg));
    }

    /**
     * Lock the entire account for security
     */
    public function lockAccount(Request $request, $userId) {
        $user = User::find($userId);

        if ($user) {
            $user->update(['is_active' => false]);

            // Revoke all tokens
            DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->update(['revoked' => true]);

            $msg = 'Account locked for security. Please contact admin to restore access.';
        } else {
            $msg = 'User not found.';
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173') . '/login';
        return redirect()->away($frontendUrl . '?message=' . urlencode($msg));
    }
}
