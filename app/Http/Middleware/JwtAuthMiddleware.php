<?php

namespace App\Http\Middleware;

use App\Services\AdminAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    protected AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized: Token missing'], 401);
        }

        $token = substr($authHeader, 7);
        $admin = $this->authService->validateToken($token);

        if (!$admin) {
            return response()->json(['message' => 'Unauthorized: Invalid token'], 401);
        }

        // Attach admin to request attributes
        $request->attributes->set('admin', $admin);

        return $next($request);
    }
}
