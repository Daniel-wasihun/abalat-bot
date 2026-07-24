<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// Public Telegram Webhook Endpoint
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Public Auth Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected Admin API Endpoints
Route::middleware(['jwt.auth'])->group(function () {
    
    // Auth profile actions
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Dashboard Data
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Feedback Management
    Route::prefix('feedback')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']);
        Route::get('/export/csv', [FeedbackController::class, 'exportCsv']);
        Route::get('/export/pdf', [FeedbackController::class, 'exportPdf']);
        Route::get('/{id}', [FeedbackController::class, 'show']);
        Route::put('/{id}/status', [FeedbackController::class, 'updateStatus']);
        Route::put('/{id}/priority', [FeedbackController::class, 'updatePriority']);
        Route::put('/{id}/category', [FeedbackController::class, 'updateCategory']);
        Route::post('/{id}/notes', [FeedbackController::class, 'addNote']);
        Route::post('/{id}/reply', [FeedbackController::class, 'reply']);
        Route::delete('/{id}', [FeedbackController::class, 'destroy']);
    });

    // Telegram Users Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('/{id}/message', [UserController::class, 'sendDirectMessage']);
    });

    // Notifications & Broadcasts
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::post('/estimate', [NotificationController::class, 'estimate']);
        Route::get('/{id}/logs', [NotificationController::class, 'show']);
    });

    // Bot Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::put('/', [SettingController::class, 'update']);
        Route::get('/webhook', [SettingController::class, 'getWebhookStatus']);
        Route::post('/webhook', [SettingController::class, 'setupWebhook']);
    });

    // Admin Users Management (Super Admin & Admin roles allowed)
    Route::prefix('admins')->middleware(['role:Super Admin,Admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::post('/', [AdminController::class, 'store']);
        Route::put('/{id}', [AdminController::class, 'update']);
        Route::delete('/{id}', [AdminController::class, 'destroy']);
    });
});
