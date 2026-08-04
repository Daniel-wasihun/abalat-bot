<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Helpers\Permission as PermissionHelper;
use App\Http\Requests\RoleRequest;
use App\Http\Requests\BulkRoleActionRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\BackMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Response;

class RoleController extends Controller {
    public function __construct() {
        $this->middleware(function ($request, $next) {
            /** @var \App\Models\User $auth */
            $auth = Auth::user();
            if ($auth && ($auth->hasPermission('roles.view') || $auth->hasPermission('users.view'))) {
                return $next($request);
            }
            return Response::_403(BackMessage::get('forbidden'));
        })->only('index');

        $this->middleware(PermissionHelper::roles()->view())->only('show');
        $this->middleware(PermissionHelper::roles()->create())->only('store');
        $this->middleware(PermissionHelper::roles()->edit())->only(['update', 'syncToUsers']);
        $this->middleware(PermissionHelper::roles()->delete())->only(['destroy', 'bulkDelete']);
        $this->middleware(PermissionHelper::roles()->edit())->only(['update', 'syncToUsers', 'toggleStatus', 'bulkToggle']);
    }

    /**
     * List all roles
     */
    public function index(Request $request) {
        $perPage = $request->get('per_page', 10);

        $roles = Role::with('permissions')
            ->where('slug', '!=', 'super-admin')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name->en', 'like', "%{$search}%")
                        ->orWhere('name->am', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            });

        // Handle Sorting using professional trait
        $roles->applySort($request, ['name', 'hierarchy_level', 'created_at', 'users_count', 'updated_at'], function ($q) {
            $q->orderByDesc('is_active')
                ->orderBy('hierarchy_level', 'desc');
        });

        $roles = $roles->paginate($perPage);

        return RoleResource::collection($roles);
    }

    /**
     * Show a single role
     */
    public function show(Role $role) {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Create a new role (super_admin only)
     */
    public function store(RoleRequest $request) {
        $data = $request->validated();

        $locale = app()->getLocale();
        $role = Role::create([
            'name' => [$locale => $data['name']],
            'description' => [$locale => $data['description'] ?? ''],
            'hierarchy_level' => $data['hierarchy_level'] ?? 1,
            'is_system_level' => false,
        ]);

        if (!empty($data['permissions'])) {
            $permissions = Permission::whereIn('slug', $data['permissions'])->get();

            if ($permissions->count() !== count($data['permissions'])) {
                $found = $permissions->pluck('slug')->toArray();
                $missing = array_diff($data['permissions'], $found);
                throw ValidationException::withMessages([
                    'permissions' => [BackMessage::get('invalid_permissions_detail', [':list' => implode(', ', $missing)])],
                ]);
            }

            $role->permissions()->attach($permissions->pluck('id'));
        }

        return Response::_200(RoleResource::success($role->fresh(), 'role_created_success'));
    }

    /**
     * Update an existing role
     */
    public function update(RoleRequest $request, Role $role) {
        $data = $request->validated();
        $updateData = [];

        $locale = app()->getLocale();
        if (isset($data['name'])) {
            $name = $role->name ?? [];
            $name[$locale] = $data['name'];
            $role->name = $name;
        }

        if (isset($data['hierarchy_level'])) {
            $role->hierarchy_level = $data['hierarchy_level'];
        }

        if (array_key_exists('description', $data)) {
            $description = $role->description ?? [];
            $description[$locale] = $data['description'] ?? '';
            $role->description = $description;
        }

        if ($role->isDirty()) {
            $role->save();
        }

        if (isset($data['permissions'])) {
            $permissionsData = Permission::whereIn('slug', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permissionsData);
        }

        return Response::_200(RoleResource::success($role->fresh(), 'role_updated_success'));
    }

    /**
     * Delete a role
     */
    public function destroy(Request $request, Role $role) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // 1. Prevent deleting protected roles
        if ($role->slug === 'super-admin') {
            return Response::_403(BackMessage::get('role_deletion_restricted'));
        }

        // 2. Prevent deleting system-level roles
        if ($role->is_system_level) {
            return Response::_403(BackMessage::get('role_deletion_restricted'));
        }

        // 3. Seniority Hierarchy: Must have a strictly higher hierarchy level
        if (!$currentUser->canModifyRole($role)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        // 3. Check for assigned users and require confirmation
        $userCount = $role->users()->count();
        if ($userCount > 0 && $request->input('confirm') !== 'true') {
            return Response::_409(BackMessage::get('role_delete_confirm_required', [':count' => $userCount]));
        }

        $role->delete();

        return Response::_200(RoleResource::success(null, 'role_deleted_success'));
    }

    public function syncToUsers(Role $role) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Hierarchy Check: Can't sync a role you can't modify
        if (!$currentUser->canModifyRole($role)) {
            return Response::_403(BackMessage::get('forbidden'));
        }
        // In the new hybrid permission model, users naturally inherit role permissions
        // unless they have a specific active override in the user_permission table.
        // Therefore, we no longer need to push snapshot updates to 'specialized' users.
        // The hasPermission() check handles the prioritized merging.

        return Response::_200(RoleResource::success(null, 'role_sync_success'));
    }

    /**
     * Toggle role status
     */
    public function toggleStatus(Role $role) {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->canModifyRole($role)) {
            return Response::_403(BackMessage::get('forbidden'));
        }

        $role->update(['is_active' => !$role->is_active]);
        return Response::_200(RoleResource::success($role, 'status_updated_success'));
    }

    /**
     * Bulk toggle role status
     */
    public function bulkToggle(BulkRoleActionRequest $request) {
        $validated = $request->validated();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $roles = \App\Models\Role::query()->whereIn('id', $validated['ids'])->get();

        $count = 0;
        foreach ($roles as $role) {
            /** @var \App\Models\Role $role */
            if ($currentUser->canModifyRole($role)) {
                $role->update(['is_active' => $validated['active']]);
                $count++;
            }
        }

        return Response::_200(RoleResource::success(null, 'bulk_status_success', [':count' => $count]));
    }

    /**
     * Bulk delete roles
     */
    public function bulkDelete(BulkRoleActionRequest $request) {
        $validated = $request->validated();

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $roles = \App\Models\Role::query()->whereIn('id', $validated['ids'])->get();

        $deletedCount = 0;
        foreach ($roles as $role) {
            /** @var \App\Models\Role $role */
            // Cannot delete protected, system, or higher/equal level roles
            if ($role->slug === 'super-admin' || $role->is_system_level || !$currentUser->canModifyRole($role)) {
                continue;
            }

            // Check assigned users - if active users exist, skip bulk delete for that role (simplification)
            if ($role->users()->count() > 0) {
                continue;
            }

            $role->delete();
            $deletedCount++;
        }

        return Response::_200(RoleResource::success(null, 'bulk_deleted_success', [':count' => $deletedCount]));
    }
}
