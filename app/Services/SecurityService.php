<?php

namespace App\Services;

use App\Models\SuspiciousActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\BackMessage;
use Symfony\Component\HttpFoundation\Response;

class SecurityService {
    const SEVERITY_LOW = 1;
    const SEVERITY_MEDIUM = 2;
    const SEVERITY_HIGH = 3;
    const SEVERITY_CRITICAL = 4;
    const SEVERITY_BLOCKED = 5;

    /**
     * Log a suspicious activity to the database and standard logs.
     */
    public static function logActivity(Request $request, string $type, int $severity = self::SEVERITY_LOW, array $extraData = []): void {
        try {
            self::checkIpBlocked($request);

            $data = array_merge($request->except(['password', 'password_confirmation', 'token', 'otp']), $extraData);

            SuspiciousActivity::create([
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'activity_type' => $type,
                'request_data' => $data,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'severity' => $severity,
            ]);

            Log::warning("Suspicious Activity Detected: {$type}", [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'url' => $request->fullUrl(),
                'severity' => $severity,
            ]);

            // If severity is high or repeated, we could trigger alerts or temporary IP bans
            if ($severity >= 4) {
                self::triggerHighSeverityAlert($request, $type);
            }

            // Automatic temporary blocking after 10 suspicious events in an hour
            $recentCount = SuspiciousActivity::where('ip_address', $request->ip())
                ->where('created_at', '>', now()->subHour())
                ->count();

            if ($recentCount >= 10) {
                Log::critical("IP Blocked Temporarily: {$request->ip()} due to repeated suspicious activity.");
                // We could store blocked IPs in Cache for faster access
                \Illuminate\Support\Facades\Cache::put('blocked_ip_' . $request->ip(), true, now()->addHours(2));
            }
        } catch (\Exception $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            Log::error('Failed to log suspicious activity: ' . $e->getMessage());
        }
    }

    /**
     * Throw an exception or return JSON if the IP is blocked.
     */
    public static function checkIpBlocked(Request $request): void {
        if (\Illuminate\Support\Facades\Cache::has('blocked_ip_' . $request->ip())) {
            $message = BackMessage::get('security_access_suspended');

            if ($request->is('api/*') || $request->expectsJson()) {
                throw new \Illuminate\Http\Exceptions\HttpResponseException(
                    response()->json([
                        'status' => 'error',
                        'message' => $message,
                        'security_code' => 'IP_BLOCKED'
                    ], 403)
                );
            }

            abort(403, $message);
        }
    }

    /**
     * Helper to return a consistent security error response.
     */
    public static function securityResponse(string $messageKey, int $status = 422, string $code = 'SECURITY_CHECK_FAILED'): Response {
        return response()->json([
            'status' => 'error',
            'message' => BackMessage::get($messageKey),
            'security_code' => $code
        ], $status);
    }

    /**
     * Trigger an alert for high severity security events.
     */
    protected static function triggerHighSeverityAlert(Request $request, string $type): void {
        // Implement email notification or Slack alert here in the future
        Log::critical("HIGH SEVERITY SECURITY ALERT: {$type} from IP {$request->ip()}");
    }

    /**
     * Check for common malicious patterns in request data.
     */
    public static function detectMaliciousPatterns(Request $request): bool {
        // Exclude sensitive and internal fields from pattern matching to avoid false positives
        $data = $request->except([
            'password',
            'password_confirmation',
            'token',
            'otp',
            'new_password',
            'current_password',
            '_hp_email_verification',
            '_hp_timestamp',
            '_method'
        ]);
        $payload = json_encode($data);

        $patterns = [
            'sql_injection' => '/(\bUNION\b.*\bSELECT\b|\bUPDATE\b.*\bSET\b|\bDELETE\b\s+FROM\b|\bDROP\b\s+TABLE\b)/i',
            'xss' => '/(<script|javascript:|onclick|onerror|alert\()/i',
            'path_traversal' => '/(\.\.\/|\.\.\\\\)/',
        ];

        foreach ($patterns as $type => $regex) {
            if (preg_match($regex, $payload)) {
                self::logActivity($request, "pattern_detected_{$type}", self::SEVERITY_HIGH);
                return true;
            }
        }

        return false;
    }
}
