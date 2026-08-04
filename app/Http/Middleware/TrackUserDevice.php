<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserSession;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;
use App\Events\UserSessionUpdated;
use App\Notifications\NewDeviceNotification;

class TrackUserDevice {

    /**
     * Session-tracking rules implemented here:
     *
     * 1. Every distinct OAuth token (session_id) is its own session record.
     *    There is NO fingerprint-based consolidation.  The same device can log
     *    in ten times and produce ten separate records — each treated as new.
     *
     * 2. If the token is already tracked and active  → just refresh heartbeat.
     *    No notification of any kind.
     *
     * 3. If the token is brand-new:
     *    a. Snapshot how many OTHER active sessions exist BEFORE inserting.
     *    b. Check whether the device fingerprint (UA + device_name) matches
     *       any TERMINATED session.
     *       • Yes → notification_type = 'terminated_relogin'  (more alarming)
     *       • No  → notification_type = 'new_device'
     *    c. Insert the new session record (status = active).
     *    d. If step (a) found ≥ 1 other active session → send the notification.
     *       (The NotificationController already suppresses it for the session
     *        that triggered it, so every OTHER device sees the alert.)
     *
     * 4. Logout (AuthController::logout / logoutSession) sets status = logged_out.
     *    Termination (SecurityActionController / logoutSession from another device)
     *    sets status = terminated.
     *    A subsequent login after a normal logout is NOT flagged as terminated relogin.
     */
    public function handle(Request $request, Closure $next): Response {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::guard('api')->user();

            if ($user) {
                $sessionId = $this->resolveCurrentTokenId($user);

                if ($sessionId) {
                    // 60-second heartbeat throttle — avoid hammering the DB on
                    // every single API call for the same session.
                    $cacheKey = "session_sync_{$sessionId}";
                    if (Cache::has($cacheKey)) {
                        return $next($request);
                    }

                    $agent = new Agent();
                    $agent->setUserAgent($request->userAgent());

                    $browser    = $agent->browser();
                    $platform   = $agent->platform();
                    $deviceName = trim(implode(' on ', array_filter([$browser, $platform])));
                    $ip         = $request->ip();
                    $location   = $this->getLocation($ip);

                    // ── Existing active session for this exact token ─────────
                    $session = UserSession::where('user_id', $user->id)
                        ->where('session_id', $sessionId)
                        ->where('status', UserSession::STATUS_ACTIVE)
                        ->first();

                    if ($session) {
                        // Just refresh the heartbeat; no notification needed.
                        $session->fill([
                            'ip_address'     => $ip,
                            'user_agent'     => $request->userAgent(),
                            'location'       => $location,
                            'last_active_at' => now(),
                        ])->save();
                    } else {
                        // ── Brand-new token → new session ────────────────────

                        // (a) Count OTHER active sessions BEFORE we add this one.
                        $otherActiveSessions = UserSession::where('user_id', $user->id)
                            ->where('status', UserSession::STATUS_ACTIVE)
                            ->get();

                        $hasOtherActiveSessions = $otherActiveSessions->isNotEmpty();

                        // (b) Was this device fingerprint previously TERMINATED
                        //     (not merely logged-out)?
                        $wasTerminated = UserSession::where('user_id', $user->id)
                            ->where('user_agent', $request->userAgent())
                            ->where('device_name', $deviceName)
                            ->where('status', UserSession::STATUS_TERMINATED)
                            ->exists();

                        $notificationType = $wasTerminated
                            ? NewDeviceNotification::TYPE_TERMINATED_RELOGIN
                            : NewDeviceNotification::TYPE_NEW_DEVICE;

                        // (c) Insert the new session record.
                        $session = UserSession::create([
                            'user_id'        => $user->id,
                            'session_id'     => $sessionId,
                            'device_name'    => $deviceName,
                            'device_type'    => $agent->deviceType(),
                            'browser'        => $browser ?: null,
                            'platform'       => $platform ?: null,
                            'ip_address'     => $ip,
                            'user_agent'     => $request->userAgent(),
                            'location'       => $location,
                            'last_active_at' => now(),
                            'status'         => UserSession::STATUS_ACTIVE,
                            'is_active'      => true,
                        ]);

                        // (d) Notify all other active sessions — but only if
                        //     there were any before this login.
                        if ($hasOtherActiveSessions) {
                            try {
                                // One-shot cache lock prevents duplicate
                                // notifications on rapid concurrent requests.
                                if (Cache::add("new_device_notif_{$sessionId}", true, 30)) {
                                    $user->notify(new NewDeviceNotification([
                                        'user_id'           => $user->id,
                                        'session_id'        => $session->session_id,
                                        'device_name'       => $session->device_name,
                                        'device_type'       => $session->device_type,
                                        'ip_address'        => $session->ip_address,
                                        'location'          => $session->location,
                                        'notification_type' => $notificationType,
                                    ]));
                                }
                            } catch (\Exception $e) {
                                Log::error('Security notification failed: ' . $e->getMessage());
                            }
                        }
                    }

                    // ── Broadcast session heartbeat ──────────────────────────
                    try {
                        event(new UserSessionUpdated($user->id, [
                            'id'                   => $session->id,
                            'session_id'           => $session->session_id,
                            'last_active_at'       => $session->last_active_at?->toDateTimeString(),
                            'last_active_at_human' => $session->last_active_at?->diffForHumans(),
                            'ip_address'           => $session->ip_address,
                            'location'             => $session->location,
                        ]));
                    } catch (\Exception $e) {
                        Log::warning('Session broadcast failed: ' . $e->getMessage());
                    }

                    Cache::put($cacheKey, true, 60);
                }
            }
        } catch (\Exception $e) {
            Log::error('Session tracking failed: ' . $e->getMessage());
        }

        return $next($request);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function getLocation(string $ip): string {
        if (App::environment('local')) {
            if (
                $ip === '127.0.0.1' ||
                $ip === '::1' ||
                str_starts_with($ip, '192.168.') ||
                str_starts_with($ip, '172.')
            ) {
                $ip = '197.156.96.221';
            }
        }

        return Cache::remember('ip_location_' . $ip, 86400, function () use ($ip) {
            try {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
                if ($response->successful() && ($data = $response->json())['status'] === 'success') {
                    $city    = $data['city'] ?? '';
                    $country = $data['country'] ?? '';
                    return ($city && $country) ? "{$city}, {$country}" : ($country ?: 'Unknown Location');
                }

                $fallback = Http::timeout(5)->get("https://ipwho.is/{$ip}");
                if ($fallback->successful() && ($data = $fallback->json())['success']) {
                    $city    = $data['city'] ?? '';
                    $country = $data['country'] ?? '';
                    return ($city && $country) ? "{$city}, {$country}" : ($country ?: 'Unknown Location');
                }
            } catch (\Exception $e) {
                Log::warning('Geolocation failed: ' . $e->getMessage());
            }

            return 'Unknown Location';
        });
    }

    private function resolveCurrentTokenId($user): ?string {
        $token = $user->token();
        return $token ? (string) ($token->oauth_access_token_id ?? $token->id) : null;
    }
}
