<?php

use App\Http\Middleware\MainMiddleware;
use App\Services\BackMessage;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\MainMiddleware::class,
            \App\Http\Middleware\TrackUserDevice::class,
            \App\Http\Middleware\HoneypotMiddleware::class,
            \App\Http\Middleware\SecurityAnalysisMiddleware::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // Enable method spoofing for FormData PUT/PATCH/DELETE via POST
        $middleware->convertEmptyStringsToNull();

        $middleware->alias([
            'active' => MainMiddleware::class . ':active',
            'role' => MainMiddleware::class . ':role',
            'permission' => MainMiddleware::class . ':permission',
            'can_modify_user' => MainMiddleware::class . ':can_modify_user',
            'honeypot' => \App\Http\Middleware\HoneypotMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = BackMessage::get('resource_not_found');

                // Check if it's a ModelNotFoundException for Role
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    if ($previous->getModel() === \App\Models\Role::class) {
                        $message = BackMessage::get('role_not_found');
                    }
                } elseif (str_contains($e->getMessage(), 'App\\Models\\Role')) {
                    $message = BackMessage::get('role_not_found');
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => BackMessage::get('unauthorized'),
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => BackMessage::get('validation_error'),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = BackMessage::get('unauthenticated');

                // Inspect previous exception for detailed OAuth errors
                $previous = $e->getPrevious();
                if ($previous instanceof \League\OAuth2\Server\Exception\OAuthServerException) {
                    if ($previous->getErrorType() === 'access_denied' && str_contains($previous->getMessage(), 'expired')) {
                        $message = BackMessage::get('token_expired');
                    }
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 401);
            }
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request, $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });
    })->create();
