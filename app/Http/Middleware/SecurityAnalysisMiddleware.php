<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityAnalysisMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response {
        // 0. Check if IP is already blocked
        SecurityService::checkIpBlocked($request);

        // 1. Detect Malicious Patterns (SQLi, XSS)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            if (SecurityService::detectMaliciousPatterns($request)) {
                return SecurityService::securityResponse('security_suspicious_activity', 422, 'MALICIOUS_PATTERN');
            }
        }

        // 2. Check for unexpected high-privilege field submission
        $user = $request->user();
        if ($user && !$user->hasRole('admin') && !$user->isSuperAdmin() && !$user->hasPermission('users.edit')) {
            $restrictedFields = ['role_id', 'role', 'permissions', 'is_active', 'is_admin'];
            foreach ($restrictedFields as $field) {
                if ($request->has($field)) {
                    SecurityService::logActivity($request, 'unauthorized_field_submission', SecurityService::SEVERITY_CRITICAL, ['field' => $field]);

                    return SecurityService::securityResponse('unauthorized', 403, 'UNAUTHORIZED_FIELD');
                }
            }
        }

        return $next($request);
    }
}
