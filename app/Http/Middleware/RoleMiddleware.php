<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $admin = $request->attributes->get('admin');

        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $adminRole = $admin['role'] ?? 'Viewer';

        // Super Admin gets access to everything
        if ($adminRole === 'Super Admin') {
            return $next($request);
        }

        if (in_array($adminRole, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden: Insufficient privileges'], 403);
    }
}
