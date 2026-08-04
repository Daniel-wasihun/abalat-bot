<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SecurityService;
use Symfony\Component\HttpFoundation\Response;

class HoneypotMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response {
        // Only check POST, PUT, PATCH requests (form submissions)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {

            // 1. Check for the hidden "honey" field. 
            // If it's filled, it's definitely a bot.
            $honeyField = $request->input('_hp_email_verification'); // Intentionally named to trick bots

            if (!empty($honeyField)) {
                SecurityService::logActivity($request, 'honeypot_honey_field', SecurityService::SEVERITY_CRITICAL);

                // Return a fake success to confuse the bot, but use localized message
                return response()->json([
                    'status' => 'success',
                    'message' => \App\Services\BackMessage::get('common.done')
                ], 200);
            }

            // 2. Check for time-based submission
            $timestamp = $request->input('_hp_timestamp');
            if ($timestamp) {
                $decodedTime = base64_decode($timestamp, true);
                if ($decodedTime === false || !is_numeric($decodedTime)) {
                    SecurityService::logActivity($request, 'honeypot_invalid_timestamp', SecurityService::SEVERITY_HIGH);
                    return SecurityService::securityResponse('security_check_failed', 422, 'INVALID_SECURITY_TOKEN');
                }

                $currentTime = time();
                $diff = $currentTime - $decodedTime;

                /**
                 * Bot Detection Logic:
                 * 1. Too Fast: If submitted in less than 0.5 seconds (reduced from 1s to avoid false positives).
                 * 2. Clock Drift: Allow up to 8 seconds of "future" time to account for client-server clock mismatch.
                 * 3. Too Stale: If the form is older than 4 hours, it might be a re-submission or very old page.
                 */
                if ($diff < 0.5 && $diff > -8) {
                    SecurityService::logActivity($request, 'honeypot_fast_submit', SecurityService::SEVERITY_LOW, [
                        'duration' => $diff . 's',
                        'threshold' => '0.5s',
                        'drift_allowed' => '8s'
                    ]);

                    return SecurityService::securityResponse('security_check_failed', 422, 'FAST_SUBMIT');
                }

                if ($diff < -8 || $diff > 14400) {
                    SecurityService::logActivity($request, 'honeypot_invalid_time_range', SecurityService::SEVERITY_MEDIUM, [
                        'duration' => $diff . 's'
                    ]);
                    return SecurityService::securityResponse('security_check_failed', 422, 'EXPIRED_SESSION');
                }
            } else {
                // If timestamp is missing on sensitive routes (like register/login), log it.
                $sensitiveRoutes = ['api/register', 'api/login', 'api/password/reset'];
                if (collect($sensitiveRoutes)->contains(fn($route) => $request->is($route))) {
                    Log::warning('Sensitive route accessed without honeypot timestamp', [
                        'path' => $request->path(),
                        'ip' => $request->ip()
                    ]);
                }
            }
        }

        return $next($request);
    }
}
