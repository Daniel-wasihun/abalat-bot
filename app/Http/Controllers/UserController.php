<?php

namespace App\Http\Controllers;

use App\Constants\Type;
use App\Helpers\Permission as PermissionHelper;
use App\Helpers\Response;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\UserRequest;
use App\Http\Requests\BulkUserActionRequest;
use App\Http\Requests\UpdateScheduledRequest;
use App\Http\Resources\UserResource;
use App\Jobs\UserImportJob;
use App\Mail\UserRegisteredMail;
use App\Models\ImportResult;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\BackMessage;
use App\Traits\CanImportCsv;
use App\Traits\CanExportCsv;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller {
    use \App\Traits\InteractsWithCsv;

    public function __construct() {
        $this->middleware(PermissionHelper::users()->view())->only(['index', 'show']);
        $this->middleware(PermissionHelper::users()->create())->only(['store', 'import', 'downloadTemplate']);
        $this->middleware(PermissionHelper::users()->edit())->only([
            'update',
            'assignRole',
            'grantPermission',
            'revokePermission',
            'syncPermissions',
            'bulkAction'
        ]);
        $this->middleware(PermissionHelper::users()->delete())->only(['destroy']);
    }

    /**
     * Get single user details — bypasses ActiveScope so admins can view inactive users.
     */
    public function show($user) {
        // Resolve by ID or by the passed model (route model binding may have already loaded it)
        if (!$user instanceof \App\Models\User) {
            $user = \App\Models\User::findOrFail($user);
        }
        return Response::_200(UserResource::success($user->load(['info', 'roles', 'directPermissions'])));
    }

    /**
     * Get filter options (types, etc)
     */
    public function options() {
        return Response::_200([
            'user_types' => \App\Constants\Type::labelMap(),
        ]);
    }

    /**
     * Apply common filters to user query
     */
    private function applyFilters(Request $request) {
        $query = User::query()
            ->with(['roles.permissions', 'directPermissions', 'info'])
            ->whereDoesntHave('roles', function ($q) {
                $q->where('slug', 'super-admin');
            });

        return $query->when($request->search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name->en', 'ilike', "%{$search}%")
                    ->orWhere('name->am', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhereHas('info', function ($qi) use ($search) {
                        $qi->where('user_university_id', 'ilike', "%{$search}%");
                    });
            });
        })
            ->when($request->user_type, function ($query, $type) {
                $types = is_array($type) ? $type : explode(',', $type);
                $query->whereHas('info', function ($q) use ($types) {
                    $q->whereIn('user_type', $types);
                });
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('slug', $role)
                        ->where('user_role.is_active', true);
                });
            })
            ->when($request->permission, function ($query, $permission) {
                $query->where(function ($q) use ($permission) {
                    $q->whereHas('roles', function ($qr) use ($permission) {
                        $qr->where('user_role.is_active', true)
                            ->whereHas('permissions', function ($qp) use ($permission) {
                                $qp->where('slug', $permission);
                            });
                    })->orWhereHas('directPermissions', function ($qp) use ($permission) {
                        $qp->where('slug', $permission);
                    });
                });
            })
            ;
    }

    /**
     * List all users
     */
    public function index(Request $request) {
        $perPage = $request->get('per_page', 10);

        $query = $this->applyFilters($request);

        // Apply robust sorting using the Model's HasSorting trait
        $query->applySort(
            $request,
            ['name', 'role', 'user_type', 'type', 'is_active', 'status', 'created_at', 'updated_at']
        );

        $users = $query->paginate($perPage);

        return Response::_200(UserResource::collection($users));
    }

    /**
     * Create a new user
     */
    public function store(UserRequest $request) {
        $data = $request->validated();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // 1. Permission & Hierarchy Check
        if (isset($data['role'])) {
            $role = Role::whereIn('slug', [$data['role'], 'super-admin'])->where('slug', $data['role'])->firstOrFail();
            
            // Critical Rule: Only one Super Admin can exist in the entire system
            if ($role->slug === 'super-admin') {
                $hasSuperAdmin = User::whereHas('roles', fn($q) => $q->where('slug', 'super-admin'))->exists();
                if ($hasSuperAdmin) {
                    return Response::_422('The system only allows one primary Super Administrator.');
                }
            }

            if (!$currentUser->canModifyRole($role)) {
                return Response::_403(BackMessage::get('forbidden'));
            }
        } else {
            return Response::_422(BackMessage::get('role_required'));
        }

        // 3. Create Core User
        DB::beginTransaction();
        try {
            // Generate random password
            $password = Str::random(8) . rand(10, 99) . '!';

            // 3. Create Core User
            $user = User::create([
                'name' => ['en' => $data['name']],
                'email' => $data['email'],
                'password' => Hash::make($password),
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 4. Create User Info
            $userInfoData = [
                'user_university_id' => $data['user_university_id'],
                'user_type'          => $data['user_type'] ?? Type::STUDENT,
                'gender'             => $data['gender'] ?? null,
                'phone_number'       => $data['phone_number'] ?? null,
                'date_of_birth'      => $data['date_of_birth'] ?? null,
                'address'            => $data['address'] ?? null,
            ];

            if ($request->has('phone_number') && $data['phone_number']) {
                $userInfoData['phone_number'] = '+251' . $data['phone_number'];
            }

            if ($request->hasFile('profile_picture')) {
                $path = $request->file('profile_picture')->store('profile_pictures', 'public');
                $userInfoData['profile_picture'] = $path;
            }

            $user->info()->create($userInfoData);

            // 5. Assign Role
            $user->roles()->attach($role->id, [
                'assigned_by' => $currentUser->id,
                'start_date'  => now(),
                'is_active'   => true,
            ]);

            DB::commit();

            // 6. Queue Welcome Email
            Mail::to($user->email)->queue(new UserRegisteredMail($user, $password));

            return Response::_201(UserResource::success($user->load('info', 'roles'), 'user_created_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update user and profile
     */
    public function update(UserRequest $request, User $user) {
        $data = $request->validated();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        try {
            DB::transaction(function () use ($user, $data, $request, $currentUser) {
                $user->update([
                    'name'      => isset($data['name']) ? ['en' => $data['name']] : $user->name,
                    'email'     => $data['email'] ?? $user->email,
                    'is_active' => $data['is_active'] ?? $user->is_active,
                ]);

                // Prepare User Info fields
                $userInfoData = [];
                $infoFields = [
                    'user_university_id',
                    'user_type',
                    'gender',
                    'phone_number',
                    'date_of_birth',
                    'address'
                ];

                foreach ($infoFields as $field) {
                    if (array_key_exists($field, $data)) {
                        $userInfoData[$field] = $data[$field];
                    }
                }



                // Handle Profile Picture
                if ($request->hasFile('profile_picture')) {
                    if ($user->info && $user->info->profile_picture) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($user->info->profile_picture);
                    }
                    $path = $request->file('profile_picture')->store('profile_pictures', 'public');
                    $userInfoData['profile_picture'] = $path;
                } elseif ($request->boolean('remove_profile_picture') && $user->info && $user->info->profile_picture) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->info->profile_picture);
                    $userInfoData['profile_picture'] = null;
                }

                if (!empty($userInfoData)) {
                    if (isset($userInfoData['phone_number']) && $userInfoData['phone_number']) {
                        $userInfoData['phone_number'] = '+251' . $userInfoData['phone_number'];
                    }
                    // Update or Create info
                    $user->info()->updateOrCreate(
                        ['user_id' => $user->id],
                        array_merge(
                            ['user_university_id' => 'TEMP-' . Str::random(8)], // Fallback for create
                            $userInfoData
                        )
                    );
                }
            });
        } catch (\Exception $e) {
            return Response::_422($e->getMessage());
        }

        return Response::_200(UserResource::success($user->load(['info']), 'user_updated_success'));
    }

    /**
     * Assign role to user (super_admin or admin)
     */
    public function assignRole(AssignmentRequest $request, User $user) {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        $roleName = $request->role;
        $startDate = $request->start_date ? new \DateTime($request->start_date) : null;
        $endDate = $request->end_date ? new \DateTime($request->end_date) : null;

        $role = Role::where('slug', $roleName)->firstOrFail();

        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        // Hierarchy Enforcement: current user must have level > role level
        if ($currentUser && !$currentUser->canModifyRole($role)) {
            return Response::_403(BackMessage::get('forbidden') . " (Insufficient hierarchy level or restricted super-admin assignment)");
        }

        // Critical Rule: Only one Super Admin can exist in the entire system
        if ($role->slug === 'super-admin') {
            $hasSuperAdmin = User::whereHas('roles', fn($q) => $q->where('slug', 'super-admin'))
                ->where('users.id', '!=', $user->id) // Exclude current target if they already have it (updating)
                ->exists();
            if ($hasSuperAdmin) {
                return Response::_422('The system only allows one primary Super Administrator.');
            }
        }

        // Automatic Cancellation of Previous Pending Assignments
        // We deactivate any assignment that hasn't started yet (future-dated) 
        // to make room for this new definitive instruction.
        DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('is_active', false)
            ->where('start_date', '>', now())
            ->update([
                'revoked_by' => $currentUser ? $currentUser->id : null,
                'revoked_at' => now(),
                'updated_at' => now()
            ]);

        // Determine if the role should be active immediately
        // Only activate if start_date is now or in the past
        $startDate = $request->start_date ? new \DateTime($request->start_date) : now();
        $endDate = $request->end_date ? new \DateTime($request->end_date) : null;
        $isImmediatelyActive = $startDate <= now();

        // If the new role is immediately active, deactivate current active role(s)
        if ($isImmediatelyActive) {
            DB::table('user_role')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'is_active'  => false,
                    'revoked_by' => $currentUser ? $currentUser->id : null,
                    'end_date'   => now(), // Terminate immediately
                    'updated_at' => now()
                ]);
        }

        // Attach the new role with correct active status
        $user->roles()->attach($role->id, [
            'assigned_by' => $currentUser ? $currentUser->id : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $isImmediatelyActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Response::_200(UserResource::success($user->load(['roles']), 'role_assigned_success'));
    }

    /**
     * Grant direct permission to user
     */
    public function grantPermission(AssignmentRequest $request, User $user) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        return $this->performPermissionSync($user, [
            $request->permission => true
        ], $request->start_date, $request->end_date);
    }

    /**
     * Revoke direct permission from user
     */
    public function revokePermission(AssignmentRequest $request, User $user) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        return $this->performPermissionSync($user, [
            $request->permission => false
        ]);
    }

    /**
     * Sync multiple permissions (bulk grant/revoke/clear)
     */
    public function syncPermissions(AssignmentRequest $request, User $user) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        return $this->performPermissionSync(
            $user,
            $request->input('permissions', []),
            $request->start_date,
            $request->end_date
        );
    }

    /**
     * Internal robust sync logic for scheduled permissions
     */
    private function performPermissionSync(User $user, array $targetPermissions = [], $startDateStr = null, $endDateStr = null) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $now = now();
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : $now;
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : null;
        $isImmediatelyActive = $startDate->lte($now);

        $allPermissions = Permission::all();
        $allPermissionsBySlug = $allPermissions->keyBy('slug');

        // 0. Build initial effective permissions map (role-based + current overrides)
        // This prevents the issue of checking $user->hasPermission() inside the transaction
        $rolePermissions = $user->getActiveRoles()
            ->flatMap(fn($r) => $r->permissions)
            ->pluck('slug')
            ->unique()
            ->toArray();

        // Get all current active overrides
        $currentOverrides = DB::table('user_permission')
            ->join('permissions', 'user_permission.permission_id', '=', 'permissions.id')
            ->where('user_permission.user_id', $user->id)
            ->where('user_permission.is_active', true)
            ->whereNull('user_permission.revoked_at')
            ->where(function ($q) use ($now) {
                $q->where(function ($sq) use ($now) {
                    $sq->whereNull('user_permission.start_date')
                        ->orWhere('user_permission.start_date', '<=', $now);
                })
                    ->where(function ($sq) use ($now) {
                        $sq->whereNull('user_permission.end_date')
                            ->orWhere('user_permission.end_date', '>=', $now);
                    });
            })
            ->select('permissions.slug', 'user_permission.granted')
            ->get()
            ->keyBy('slug');

        // Build effective permissions map: start with role permissions, then apply overrides
        $effectivePermissions = [];
        foreach ($allPermissions as $perm) {
            $slug = $perm->slug;
            $hasFromRole = in_array($slug, $rolePermissions);

            if ($currentOverrides->has($slug)) {
                // Override exists: use its granted value
                $effectivePermissions[$slug] = (bool) $currentOverrides->get($slug)->granted;
            } else {
                // No override: use role permission
                $effectivePermissions[$slug] = $hasFromRole;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($targetPermissions as $slug => $granted) {
                $permission = $allPermissionsBySlug->get($slug);
                if (!$permission) continue;

                $granted = (bool)$granted;
                $effectiveNow = $effectivePermissions[$slug] ?? false;

                // Get existing valid override record if it exists
                $existing = DB::table('user_permission')
                    ->where('user_id', $user->id)
                    ->where('permission_id', $permission->id)
                    ->whereNull('revoked_at')
                    ->first();

                // Determine if this is a meaningful change
                $statusChanged = $effectiveNow !== $granted;
                $requestHasDates = $startDateStr !== null || $endDateStr !== null;
                $isExplicitAction = false;

                if ($existing) {
                    // Update existing override if status OR schedule changed
                    $existingStart = $existing->start_date ? Carbon::parse($existing->start_date)->toDateString() : null;
                    $existingEnd = $existing->end_date ? Carbon::parse($existing->end_date)->toDateString() : null;

                    $isEffectivelySameStart = ($startDateStr === $existingStart) ||
                        ($startDateStr === null && $existing->start_date && Carbon::parse($existing->start_date)->lte($now));

                    $scheduleChanged = !$isEffectivelySameStart || ($endDateStr !== $existingEnd);

                    if ($statusChanged || $scheduleChanged) {
                        $isExplicitAction = true;
                    }
                } else {
                    // No existing override: ONLY create record if status changed (toggled)
                    // We ignore bundle dates if the user didn't explicitly toggle the permission
                    if ($statusChanged) {
                        $isExplicitAction = true;
                    }
                }

                if (!$isExplicitAction) {
                    continue;
                }

                // Deactivate old record
                if ($existing) {
                    DB::table('user_permission')
                        ->where('id', $existing->id)
                        ->update([
                            'is_active' => false,
                            'revoked_at' => $now,
                            'revoked_by' => $currentUser->id,
                            'updated_at' => $now
                        ]);
                }

                // Insert new record with specific schedule
                DB::table('user_permission')->insert([
                    'user_id' => $user->id,
                    'permission_id' => $permission->id,
                    'granted' => $granted,
                    'is_active' => $isImmediatelyActive,
                    'assigned_by' => $granted ? $currentUser->id : null,
                    'revoked_by' => !$granted ? $currentUser->id : null,
                    'start_date' => $startDateStr ? $startDate : $now, // Default to now as per request
                    'end_date' => $endDate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Update our tracking map for subsequent iterations
                if ($isImmediatelyActive) {
                    $effectivePermissions[$slug] = $granted;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to sync permissions: ' . $e->getMessage()], 500);
        }

        return Response::_200([
            'success' => true,
            'user' => new UserResource($user->fresh())
        ]);
    }


    public function resetPermissions(User $user) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        // Remove all direct permissions (both granted and revoked)
        $user->directPermissions()->detach();
        return Response::_200(UserResource::success($user->fresh(), 'permissions_reset_success'));
    }

    /**
     * Delete a user
     */
    public function destroy(User $user) {
        // Check if user is trying to delete themselves
        if (Auth::id() === $user->id) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        $user->delete(); // Soft delete

        return Response::_200(['message' => BackMessage::get('user_deleted_success')]);
    }

    /**
     * Import users from CSV
     */
    public function import(Request $request) {
        return $this->importFromCsvAsync($request, [
            'type' => 'user_import',
            'required_columns' => ['name', 'email', 'user_university_id', 'gender'],
            'validation_rules' => [
                'role' => 'required|exists:roles,slug',
            ],
            'attributes' => [
                'name' => BackMessage::get('attributes.name'),
                'email' => BackMessage::get('attributes.email'),
                'user_university_id' => BackMessage::get('attributes.user_university_id'),
                'gender' => BackMessage::get('attributes.gender'),
                'user_type' => BackMessage::get('attributes.user_type'),
                'phone_number' => BackMessage::get('attributes.phone_number'),
                'date_of_birth' => BackMessage::get('attributes.date_of_birth'),
                'address' => BackMessage::get('attributes.address'),
            ],
            'context' => [
                'role' => $request->role,
                'current_user_id' => Auth::id()
            ]
        ]);
    }

    /**
     * Process single row for User import (called by Job)
     */
    public function processImportRow(array $data, int $rowNum, array $context = []) {
        $data['user_type'] = $data['user_type'] ?? \App\Constants\Type::STUDENT;

        \Illuminate\Support\Facades\Validator::make(
            $data,
            \App\Http\Requests\ImportUsersRequest::rowRules(),
            \App\Http\Requests\ImportUsersRequest::rowMessages(),
            \App\Http\Requests\ImportUsersRequest::rowAttributes()
        )->validate();

        $role = Role::where('slug', $context['role'])->firstOrFail();
        $password = Str::random(10) . '!';

        $user = User::create([
            'name' => ['en' => $data['name']],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $user->info()->create([
            'user_university_id' => $data['user_university_id'],
            'user_type' => $data['user_type'],
            'gender' => strtolower($data['gender']),
            'phone_number' => !empty($data['phone_number']) ? '+251' . $data['phone_number'] : null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id => [
            'assigned_by' => $context['current_user_id'] ?? null,
            'start_date' => now(),
            'is_active' => true
        ]]);

        try {
            Mail::to($user->email)->queue(new UserRegisteredMail($user, $password));
        } catch (\Exception $me) {
            Log::warning("Welcome email queue error for {$user->email}: " . $me->getMessage());
        }

        return BackMessage::get('import.line_success', ['name' => $data['name'], 'email' => $data['email']]);
    }

    /**
     * Check Import Status
     */
    public function checkImportStatus($id) {
        $import = ImportResult::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return Response::_200([
            'status' => $import->status,
            'processed' => $import->processed_rows,
            'total' => $import->total_rows,
            'imported' => $import->imported_count,
            'errors' => $import->errors ?? [],
            'success_log' => $import->success_log ?? [],
        ]);
    }

    /**
     * Download User Import Template
     */
    public function downloadTemplate() {
        return $this->downloadCsvTemplate(
            'users_import_template',
            ['name', 'email', 'user_university_id', 'gender', 'phone_number', 'user_type', 'date_of_birth', 'address'],
            ['Daniel Wasihun', 'daniel@etsub.qmt', 'WDU123456', 'male', '911223344', 'student', '2000-01-01', 'Addis Ababa']
        );
    }

    /**
     * Perform bulk actions on users
     */
    public function bulkAction(BulkUserActionRequest $request) {
        $validated = $request->validated();

        $ids = $validated['ids'];
        $action = $validated['action'];
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Filter users that the current user is allowed to modify
        // Must bypass ActiveScope to find and manage inactive users
        $targetUsers = User::withoutGlobalScope('active')->whereIn('id', $ids)->get();
        $allowedIds = [];

        foreach ($targetUsers as $user) {
            // Skip self
            if ($user->id === $currentUser->id) continue;

            if ($currentUser->canModifyUser($user)) {
                $allowedIds[] = $user->id;
            }
        }

        if (empty($allowedIds)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        $count = count($allowedIds);

        if ($action === 'delete') {
            User::whereIn('id', $allowedIds)->delete(); // Soft deletes
            return Response::_200(['message' => BackMessage::get('users_bulk_deleted', ['count' => $count])]);
        }

        $isActive = ($action === 'activate');
        User::withoutGlobalScope('active')->whereIn('id', $allowedIds)->update(['is_active' => $isActive]);

        $key = $isActive ? 'users_bulk_activated' : 'users_bulk_deactivated';
        return Response::_200(['message' => BackMessage::get($key, ['count' => $count])]);
    }

    /**
     * Toggle user status (activation/deactivation)
     */
    public function toggleStatus($id) {
        $user = User::withoutGlobalScope('active')->findOrFail($id);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return Response::_200([
            'message' => BackMessage::get('user_status_success'),
            'user' => new UserResource($user->load('info'))
        ]);
    }

    /**
     * Cancel a scheduled role assignment
     */
    public function cancelScheduledRole(User $user, $id) {
        return $this->performCancelScheduled($user, $id, 'user_role');
    }

    /**
     * Cancel a scheduled permission override
     */
    public function cancelScheduledPermission(User $user, $id) {
        return $this->performCancelScheduled($user, $id, 'user_permission');
    }

    /**
     * Shared logic for cancelling scheduled items
     */
    private function performCancelScheduled(User $user, $id, $table) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        $item = DB::table($table)
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where('is_active', false)
            ->whereNull('revoked_at')
            ->first();

        if (!$item) {
            return Response::_404(BackMessage::get('assignment_not_found'));
        }

        DB::table($table)->where('id', $id)->update([
            'revoked_at' => now(),
            'revoked_by' => $currentUser->id,
            'updated_at' => now()
        ]);

        return Response::_200(['message' => BackMessage::get('schedule_cancelled_success')]);
    }

    /**
     * Update dates of a scheduled role assignment
     */
    public function updateScheduledRole(UpdateScheduledRequest $request, User $user, $id) {
        return $this->performUpdateScheduled($request, $user, $id, 'user_role');
    }

    /**
     * Update dates of a scheduled permission override
     */
    public function updateScheduledPermission(UpdateScheduledRequest $request, User $user, $id) {
        return $this->performUpdateScheduled($request, $user, $id, 'user_permission');
    }

    /**
     * Shared logic for updating scheduled dates
     */
    private function performUpdateScheduled(UpdateScheduledRequest $request, User $user, $id, $table) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser->canModifyUser($user)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        $validated = $request->validated();

        $item = DB::table($table)
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where('is_active', false)
            ->whereNull('revoked_at')
            ->first();

        if (!$item) {
            return Response::_404(BackMessage::get('assignment_not_found'));
        }

        // Preserve existing values if not provided in request
        // Use input() to check for presence to allow nullable explicit clearing if desired, 
        // but normally for dates 'nullable' validation allows null. 
        // If the user sends null, we assume they want to clear it. 
        // If they don't send the key, we keep existing.

        $newStartDate = $request->exists('start_date') ?
            ($request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null)
            : $item->start_date;

        $newEndDate = $request->exists('end_date') ?
            ($request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null)
            : $item->end_date;

        $now = now();
        // Check if the NEW start date (or existing if unchanged) means it should be active now
        // Treat null start_date as "start immediately" / "always active" context usually, 
        // but here it's "scheduled", so null start date might mean "no specific start", implies now?
        // In assignRole, null start_date defaults to now(). Here we should probably stick to that logic.
        $effectiveStartDate = $newStartDate ? Carbon::parse($newStartDate) : $now;
        $shouldBeActive = $effectiveStartDate->lte($now);

        DB::transaction(function () use ($table, $id, $user, $newStartDate, $newEndDate, $now, $shouldBeActive, $item) {
            DB::table($table)->where('id', $id)->update([
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
                'is_active' => $shouldBeActive,
                'updated_at' => $now
            ]);

            if ($shouldBeActive) {
                // Deactivate conflicting active records
                if ($table === 'user_role') {
                    // For roles, we deactivate ALL currently active roles (single role set logic)
                    DB::table('user_role')
                        ->where('user_id', $user->id)
                        ->where('is_active', true)
                        ->where('id', '!=', $id)
                        ->update([
                            'is_active' => false,
                            'revoked_at' => $now, // Mark as revoked by system (or implication)
                            'updated_at' => $now
                        ]);
                } elseif ($table === 'user_permission') {
                    // For permissions, we deactivate active records for THE SAME permission
                    if (isset($item->permission_id)) {
                        DB::table('user_permission')
                            ->where('user_id', $user->id)
                            ->where('permission_id', $item->permission_id)
                            ->where('is_active', true)
                            ->where('id', '!=', $id)
                            ->update([
                                'is_active' => false,
                                'revoked_at' => $now,
                                'updated_at' => $now
                            ]);
                    }
                }
            }
        });

        return Response::_200(['message' => BackMessage::get('schedule_updated_success')]);
    }
}
