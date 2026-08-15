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

    // Academic Management (Admin & Teachers)
    Route::prefix('academic')->group(function () {

        // ── General Attendance ───────────────────────────────────────────────
        Route::prefix('general-attendance')->group(function () {
            Route::get('/classes', [\App\Http\Controllers\Api\Academic\GeneralAttendanceController::class, 'getClasses']);
            Route::post('/session', [\App\Http\Controllers\Api\Academic\GeneralAttendanceController::class, 'getOrCreateSession']);
            Route::post('/session/{sessionId}/records', [\App\Http\Controllers\Api\Academic\GeneralAttendanceController::class, 'saveRecords']);
        });

        // ── Configuration (Admin) ─────────────────────────────────────────────
        Route::prefix('config')->group(function () {
            Route::get('/classes', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'getClasses']);
            Route::post('/classes', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'storeClass']);
            Route::put('/classes/{id}', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'updateClass']);
            Route::delete('/classes/{id}', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'destroyClass']);

            Route::get('/assessments', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'getAssessments']);
            Route::post('/assessments', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'storeAssessment']);
            Route::put('/assessments/{id}', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'updateAssessment']);
            Route::delete('/assessments/{id}', [\App\Http\Controllers\Api\Academic\ConfigurationController::class, 'destroyAssessment']);
        });

        // ── Course Management (Admin) ─────────────────────────────────────────
        Route::prefix('courses')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Academic\CourseController::class, 'index'])
                ->middleware('permission:academic_courses.view');
            Route::get('/{id}', [\App\Http\Controllers\Api\Academic\CourseController::class, 'show'])
                ->middleware('permission:academic_courses.view');
            Route::post('/', [\App\Http\Controllers\Api\Academic\CourseController::class, 'store'])
                ->middleware('permission:academic_courses.create');
            Route::put('/{id}', [\App\Http\Controllers\Api\Academic\CourseController::class, 'update'])
                ->middleware('permission:academic_courses.edit');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Academic\CourseController::class, 'destroy'])
                ->middleware('permission:academic_courses.delete');

            // Legacy: assign teachers by course (kept for backward compat)
            Route::post('/{id}/teachers', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'assignTeachers'])
                ->middleware('permission:academic_courses.manage');

            // Legacy: course-level student management
            Route::get('/{id}/students', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'courseStudents'])
                ->middleware('permission:academic_classes.view');
            Route::post('/{id}/enroll', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'enrollStudent'])
                ->middleware('permission:academic_courses.manage');
            Route::post('/{id}/unenroll', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'unenrollStudent'])
                ->middleware('permission:academic_courses.manage');

            // Assessments & Gradebook (legacy assessment_components system)
            Route::get('/{id}/assessments', [\App\Http\Controllers\Api\Academic\GradebookController::class, 'index'])
                ->middleware('permission:academic_classes.view');
            Route::post('/{id}/assessments', [\App\Http\Controllers\Api\Academic\GradebookController::class, 'store'])
                ->middleware('permission:academic_classes.manage');
            Route::delete('/{id}/assessments/{assessment_id}', [\App\Http\Controllers\Api\Academic\GradebookController::class, 'destroy'])
                ->middleware('permission:academic_classes.manage');
            Route::get('/{id}/marks', [\App\Http\Controllers\Api\Academic\GradebookController::class, 'getMarks'])
                ->middleware('permission:academic_classes.view');
            Route::post('/{id}/marks', [\App\Http\Controllers\Api\Academic\GradebookController::class, 'saveMarks'])
                ->middleware('permission:academic_classes.manage');

            // Attendance (course-level legacy removed)
            // ── Course Offerings (nested under a course) ──────────────────────
            Route::prefix('{courseId}/offerings')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\Academic\CourseOfferingController::class, 'index'])
                    ->middleware('permission:academic_courses.view');
                Route::post('/', [\App\Http\Controllers\Api\Academic\CourseOfferingController::class, 'store'])
                    ->middleware('permission:academic_courses.manage');
                Route::put('/{offeringId}', [\App\Http\Controllers\Api\Academic\CourseOfferingController::class, 'update'])
                    ->middleware('permission:academic_courses.manage');
                Route::delete('/{offeringId}', [\App\Http\Controllers\Api\Academic\CourseOfferingController::class, 'destroy'])
                    ->middleware('permission:academic_courses.manage');
            });


        });

        // ── Offerings (top-level, scoped to teacher or admin) ─────────────────
        Route::prefix('offerings')->group(function () {

            // Teacher Assignments for an offering
            Route::get('/{offeringId}/teachers', [\App\Http\Controllers\Api\Academic\TeacherAssignmentController::class, 'index'])
                ->middleware('permission:academic_courses.view');
            Route::post('/{offeringId}/teachers/sync', [\App\Http\Controllers\Api\Academic\TeacherAssignmentController::class, 'sync'])
                ->middleware('permission:academic_courses.manage');
            Route::delete('/{offeringId}/teachers/{assignmentId}', [\App\Http\Controllers\Api\Academic\TeacherAssignmentController::class, 'destroy'])
                ->middleware('permission:academic_courses.manage');

            // Students enrolled in an offering
            Route::get('/{offeringId}/students', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'offeringStudents'])
                ->middleware('permission:academic_classes.view');
            Route::post('/{offeringId}/students/enroll', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'enrollInOffering'])
                ->middleware('permission:academic_courses.manage');
            Route::post('/{offeringId}/students/unenroll', [\App\Http\Controllers\Api\Academic\EnrollmentController::class, 'unenrollFromOffering'])
                ->middleware('permission:academic_courses.manage');

            // Course Attendance
            Route::prefix('{offeringId}/attendance')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\Academic\AttendanceController::class, 'index'])
                    ->middleware('permission:academic_classes.view');
                // Load or create session for a specific date (used by frontend attendance tab)
                Route::post('/by-date', [\App\Http\Controllers\Api\Academic\AttendanceController::class, 'getOrCreateByDate'])
                    ->middleware('permission:academic_classes.manage');
                Route::get('/{sessionId}', [\App\Http\Controllers\Api\Academic\AttendanceController::class, 'showSession'])
                    ->middleware('permission:academic_classes.view');
                Route::post('/{sessionId}/records', [\App\Http\Controllers\Api\Academic\AttendanceController::class, 'saveRecords'])
                    ->middleware('permission:academic_classes.manage');
            });

            // Student Results (teacher-scoped grading)
            Route::get('/{offeringId}/results', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'index'])
                ->middleware('permission:academic_classes.view');
            Route::put('/{offeringId}/results/{studentId}', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'update'])
                ->middleware('permission:academic_classes.manage');
            Route::get('/{offeringId}/results/{studentId}', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'show'])
                ->middleware('permission:academic_classes.view');
            Route::get('/{offeringId}/results/{studentId}/history', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'history'])
                ->middleware('permission:academic_classes.view');
            // Student self-view: see own assessment result for an enrolled offering
            Route::get('/{offeringId}/my-result', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'myResult'])
                ->middleware('permission:academic_classes.view');
            Route::post('/{offeringId}/finalize', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'finalize'])
                ->middleware('permission:academic_courses.manage');
            Route::post('/{offeringId}/unfinalize', [\App\Http\Controllers\Api\Academic\StudentResultController::class, 'unfinalize'])
                ->middleware('permission:academic_courses.manage');
        });

        // Admin: all teacher assignments
        Route::get('/teacher-assignments', [\App\Http\Controllers\Api\Academic\TeacherAssignmentController::class, 'all'])
            ->middleware('permission:academic_courses.manage');

        // Teacher's assigned offerings dashboard
        Route::get('/my-offerings', [\App\Http\Controllers\Api\Academic\CourseController::class, 'myOfferings'])
            ->middleware('permission:academic_classes.view');

        // Student's enrolled offerings
        Route::get('/my-student-courses', [\App\Http\Controllers\Api\Academic\CourseController::class, 'myStudentCourses'])
            ->middleware('permission:academic_classes.view');

        // Teacher-only user search (for assignment modals — only returns teacher-role users)
        Route::get('/teacher-search', [\App\Http\Controllers\Api\Academic\TeacherAssignmentController::class, 'searchTeachers'])
            ->middleware('permission:academic_courses.manage');

        // Legacy: teacher's course list
        Route::get('/my-classes', [\App\Http\Controllers\Api\Academic\CourseController::class, 'myClasses'])
            ->middleware('permission:academic_classes.view');
    });
});

