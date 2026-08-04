<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

// Language & Translation (public)
Route::get('languages', [LanguageController::class, 'list']);
Route::get('front-language', [LanguageController::class, 'frontLanguage']);
Route::get('translations/{lang}', [LanguageController::class, 'translations']);

// Auth (public)
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:auth');

// Password Reset
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])->middleware('throttle:auth');
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->middleware('throttle:auth');
Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])->middleware('throttle:auth');

// Security Actions (Signed URLs — no auth required, signed instead)
Route::prefix('security')->group(function () {
    Route::get('/approve-session/{session_id}', [\App\Http\Controllers\SecurityActionController::class, 'approveSession'])->name('security.approve-session')->middleware('signed');
    Route::get('/terminate-session/{session_id}', [\App\Http\Controllers\SecurityActionController::class, 'terminateSession'])->name('security.terminate-session')->middleware('signed');
    Route::get('/lock-account/{user_id}', [\App\Http\Controllers\SecurityActionController::class, 'lockAccount'])->name('security.lock-account')->middleware('signed');
});

// Public Telegram Webhook Endpoint
Route::post('/telegram/webhook', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);

// Protected routes
Route::middleware(['auth:api', 'active', \App\Http\Middleware\TrackUserDevice::class])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/sessions', [AuthController::class, 'sessions']);
    Route::post('/sessions/{id}/logout', [AuthController::class, 'logoutSession']);
    Route::post('/sessions/logout-other', [AuthController::class, 'logoutAllOtherSessions']);

    // System Management (Roles, Permissions, Users)
    Route::prefix('system')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::post('/roles/{role}/sync', [RoleController::class, 'syncToUsers']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        Route::post('/roles/bulk-delete', [RoleController::class, 'bulkDelete']);
        Route::patch('/roles/bulk-toggle', [RoleController::class, 'bulkToggle']);
        Route::patch('/roles/{role}/toggle', [RoleController::class, 'toggleStatus']);

        Route::post('/users/bulk-action', [UserController::class, 'bulkAction']);
        Route::get('/users/template', [UserController::class, 'downloadTemplate']);
        Route::post('/users/import', [UserController::class, 'import']);
        Route::get('/users/import-status/{id}', [UserController::class, 'checkImportStatus']);
        Route::get('/users/options', [UserController::class, 'options']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);

        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/permissions/options', [PermissionController::class, 'options']);
        Route::post('/permissions', [PermissionController::class, 'store']);
        Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
        Route::patch('/permissions/{permission}/toggle', [PermissionController::class, 'toggleStatus']);
        Route::patch('/permissions/bulk-toggle', [PermissionController::class, 'bulkToggle']);
        Route::post('/permissions/bulk-delete', [PermissionController::class, 'bulkDelete']);

        Route::middleware('can_modify_user')->group(function () {
            Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
            Route::post('/users/{user}/grant-permission', [UserController::class, 'grantPermission']);
            Route::post('/users/{user}/revoke-permission', [UserController::class, 'revokePermission']);
            Route::post('/users/{user}/sync-permissions', [UserController::class, 'syncPermissions']);
            Route::post('/users/{user}/reset-permissions', [UserController::class, 'resetPermissions']);
            Route::delete('/users/{user}/cancel-scheduled-role/{id}', [UserController::class, 'cancelScheduledRole']);
            Route::delete('/users/{user}/cancel-scheduled-permission/{id}', [UserController::class, 'cancelScheduledPermission']);
            Route::patch('/users/{user}/update-scheduled-role/{id}', [UserController::class, 'updateScheduledRole']);
            Route::patch('/users/{user}/update-scheduled-permission/{id}', [UserController::class, 'updateScheduledPermission']);
            Route::match(['put', 'post'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
    });

    // Bot API routes — all under /bot prefix
    Route::prefix('bot')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

        Route::prefix('feedback')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\FeedbackController::class, 'index']);
            Route::get('/export/csv', [\App\Http\Controllers\Api\FeedbackController::class, 'exportCsv']);
            Route::get('/export/pdf', [\App\Http\Controllers\Api\FeedbackController::class, 'exportPdf']);
            Route::get('/{id}', [\App\Http\Controllers\Api\FeedbackController::class, 'show']);
            Route::put('/{id}/status', [\App\Http\Controllers\Api\FeedbackController::class, 'updateStatus']);
            Route::put('/{id}/priority', [\App\Http\Controllers\Api\FeedbackController::class, 'updatePriority']);
            Route::put('/{id}/category', [\App\Http\Controllers\Api\FeedbackController::class, 'updateCategory']);
            Route::post('/{id}/notes', [\App\Http\Controllers\Api\FeedbackController::class, 'addNote']);
            Route::post('/{id}/reply', [\App\Http\Controllers\Api\FeedbackController::class, 'reply']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\FeedbackController::class, 'destroy']);
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\UserController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Api\UserController::class, 'toggleStatus']);
            Route::post('/{id}/message', [\App\Http\Controllers\Api\UserController::class, 'sendDirectMessage']);
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\NotificationController::class, 'store']);
            Route::post('/estimate', [\App\Http\Controllers\Api\NotificationController::class, 'estimate']);
            Route::get('/{id}/logs', [\App\Http\Controllers\Api\NotificationController::class, 'show']);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\SettingController::class, 'index']);
            Route::put('/', [\App\Http\Controllers\Api\SettingController::class, 'update']);
            Route::get('/webhook', [\App\Http\Controllers\Api\SettingController::class, 'getWebhookStatus']);
            Route::post('/webhook', [\App\Http\Controllers\Api\SettingController::class, 'setupWebhook']);
        });
    });
});
