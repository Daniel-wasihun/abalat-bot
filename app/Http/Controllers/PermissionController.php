<?php

namespace App\Http\Controllers;



use App\Constants\Module;
use App\Constants\Action;
use App\Http\Requests\PermissionRequest;
use App\Helpers\Permission as PermissionHelper;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Response;
use App\Services\BackMessage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller {
    public static function middleware(): array {
        return [
            new Middleware(function ($request, $next) {
                /** @var \App\Models\User $auth */
                $auth = Auth::user();
                if ($auth && ($auth->hasPermission('permissions.view') ||
                    $auth->hasPermission('users.edit') ||
                    $auth->hasPermission('roles.edit'))) {
                    return $next($request);
                }
                return Response::_403(BackMessage::get('forbidden'));
            }, only: ['index', 'options']),
            new Middleware(PermissionHelper::permissions()->create(), only: ['store']),
            new Middleware(PermissionHelper::permissions()->edit(), only: ['update', 'toggleStatus', 'bulkToggle']),
            new Middleware(PermissionHelper::permissions()->delete(), only: ['destroy', 'bulkDelete']),
        ];
    }

    /**
     * List all permissions
     */
    public function index(Request $request) {
        $perPage = $request->get('per_page', 10);

        $permissions = Permission::when($request->search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        })
            ->when($request->module, function ($query, $module) {
                $query->where('module', $module);
            })
            ->when($request->action, function ($query, $action) {
                $query->where('action', $action);
            })
            ->orderBy('is_active', 'desc')
            ->latest()
            ->paginate($perPage);

        return PermissionResource::collection($permissions);
    }

    /**
     * Get available permission options (modules and actions)
     */
    public function options() {
        return Response::_200([
            'modules' => Module::labelMap(),
            'actions' => Action::labelMap(),
        ]);
    }

    /**
     * Create a new permission manually
     */
    public function store(PermissionRequest $request) {
        $data = $request->validated();
        $slug = "{$data['module']}.{$data['action']}";

        $permissionData = [
            'slug' => $slug,
            'module' => $data['module'],
            'action' => $data['action'],
        ];

        if (isset($data['description'])) {
            $permissionData['description'] = $data['description'];
        }

        $permission = Permission::create($permissionData);

        return Response::_201(
            PermissionResource::success($permission, 'permission_created_success')
        );
    }

    /**
     * Update an existing permission
     */
    public function update(PermissionRequest $request, Permission $permission) {
        $data = $request->validated();

        $updateData = [];

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'] ?? '';
        }

        if (!empty($updateData)) {
            $permission->update($updateData);
        }

        return new PermissionResource($permission->fresh());
    }

    /**
     * Delete a permission
     */
    public function destroy(Permission $permission) {
        if ($permission->is_system_level) {
            return Response::_403(BackMessage::get('permission_deletion_restricted'));
        }

        $permission->delete();

        return Response::_200(PermissionResource::success(null, 'permission_deleted_success'));
    }

    /**
     * Toggle permission status
     */
    public function toggleStatus(Permission $permission) {
        return Response::_200(PermissionResource::success($permission, 'status_updated_success')); // Re-using generic status change message
    }

    /**
     * Bulk toggle permission status
     */
    public function bulkToggle(Request $request) {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:permissions,id',
            'active' => 'required|boolean',
        ]);

        Permission::whereIn('id', $request->ids)->update(['is_active' => $request->active]);

        return Response::_200(PermissionResource::success(null, 'bulk_status_success', [':count' => count($request->ids)]));
    }

    /**
     * Bulk delete permissions
     */
    public function bulkDelete(Request $request) {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $request->ids)->get();
        $deletedCount = 0;

        foreach ($permissions as $permission) {
            if ($permission->is_system_level) {
                continue;
            }
            $permission->delete();
            $deletedCount++;
        }

        return Response::_200(PermissionResource::success(null, 'bulk_deleted_success', [':count' => $deletedCount]));
    }
}
