<?php

namespace App\Http\Middleware;

use App\Services\BackMessage;
use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Universal API Gatekeeper:
 * 1. Global Initialization (Language, Token, JSON enforcement)
 * 2. Route-specific Authorization (Roles, Permissions, Account Status)
 */
class MainMiddleware {
    public function handle(Request $request, Closure $next, ?string $checkType = null, ...$args): Response {
        // --- LOGGING (Filter sensitive data) ---
        if (config('app.debug')) {
            $logData = $request->except(['password', 'password_confirmation', 'new_password', 'current_password', 'token', 'otp']);
            Log::debug('API Request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'data' => $logData,
            ]);
        }

        // --- SECTION 1: GLOBAL INITIALIZATION ---

        // 1. Handle Language
        $lang = $request->query('lang')
            ?? $request->header('lang')
            ?? $request->header('Accept-Language')
            ?? $request->input('lang')
            ?? 'en';

        $lang = $lang ? strtolower(substr(trim((string)$lang), 0, 2)) : 'en';
        $supported = \App\Services\FrontLang::getAvailableLangKeys();

        if (!in_array($lang, $supported)) {
            $lang = \App\Services\FrontLang::getDefaultLanguage();
        }

        BackMessage::set($lang);
        app()->setLocale($lang);
        $request->headers->set('lang', $lang);

        // 2. Handle Token Extraction
        if (!$request->bearerToken()) {
            $token = $request->query('token') ?? $request->input('token');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        // 3. Enforce JSON behavior
        if (!$request->expectsJson() && $request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        // 4. Handle Method Spoofing
        if ($request->hasHeader('X-HTTP-Method-Override')) {
            $method = strtoupper($request->header('X-HTTP-Method-Override'));
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                $request->setMethod($method);
            }
        }

        // 5. Sanitize Files
        if ($files = $request->files->all()) {
            foreach ($files as $key => $file) {
                $path = is_object($file) && method_exists($file, 'getPathname') ? $file->getPathname() : null;
                if ($path && is_dir($path)) {
                    $request->files->remove($key);
                    Log::warning('Removed directory from request files collection', ['key' => $key, 'url' => $request->fullUrl()]);
                }
            }
        }


        // --- SECTION 2: AUTHORIZATION CHECKS ---
        if ($checkType) {
            if (str_contains($checkType, ':')) {
                [$realType, $firstArg] = explode(':', $checkType, 2);
                $checkType = $realType;
                array_unshift($args, $firstArg);
            }

            $user = $request->user();

            if (!$user) {
                return SecurityService::securityResponse('unauthenticated', 401, 'UNAUTHENTICATED');
            }

            switch ($checkType) {
                case 'active':
                    if (isset($user->is_active) && !$user->is_active) {
                        return SecurityService::securityResponse('inactive_account', 403, 'ACCOUNT_INACTIVE');
                    }
                    break;

                case 'role':
                    $role = $args[0] ?? null;
                    if (!$role || !$user->hasRole($role)) {
                        return SecurityService::securityResponse('unauthorized', 403, 'INSUFFICIENT_ROLE');
                    }
                    break;

                case 'permission':
                    $permissions = $args[0] ?? null;
                    if (!$permissions) {
                        return SecurityService::securityResponse('forbidden', 403, 'INSUFFICIENT_PERMISSION');
                    }

                    // Support multiple permissions via pipes (OR logic)
                    $permList = explode('|', $permissions);
                    $hasAny = false;
                    foreach ($permList as $perm) {
                        if ($user->hasPermission(trim($perm))) {
                            $hasAny = true;
                            break;
                        }
                    }

                    if (!$hasAny) {
                        return SecurityService::securityResponse('forbidden', 403, 'INSUFFICIENT_PERMISSION');
                    }
                    break;

                case 'can_modify_user':
                    $targetUser = $request->route('user');
                    if (!$targetUser || !($targetUser instanceof \App\Models\User)) {
                        // Bypass ActiveScope — admins must be able to manage inactive users too
                        $userId = is_numeric($targetUser) ? $targetUser : null;
                        if ($userId) {
                            $targetUser = \App\Models\User::withoutGlobalScope('active')->find($userId);
                        }
                    }

                    if (!$targetUser || !$user->canModifyUser($targetUser)) {
                        return SecurityService::securityResponse('forbidden', 403, 'CANNOT_MODIFY_USER');
                    }
                    break;
            }
        }

        return $next($request);
    }
}
