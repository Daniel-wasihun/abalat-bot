<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Provides role-based and permission-based authorization for the User model.
 *
 * PHPDoc properties listed below are defined on the consuming class (User extends Authenticatable)
 * and on related traits (HasApiTokens, HasFactory). They are listed here so that
 * static analysis tools (Intelephense, PHPStan) can resolve them within trait methods.
 *
 * @property int                                         $id
 * @property \Illuminate\Database\Eloquent\Collection    $roles
 * @property \Illuminate\Database\Eloquent\Collection    $directPermissions
 */
trait HasCustomPermissions {


    /**
     * Direct/custom permissions relationship.
     */
    public function directPermissions(): BelongsToMany {
        return $this->belongsToMany(Permission::class, 'user_permission')
            ->withPivot('id', 'granted', 'assigned_by', 'start_date', 'end_date', 'revoked_by', 'revoked_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Returns all currently active roles for this user.
     */
    public function getActiveRoles(): \Illuminate\Support\Collection {
        $now = now();

        if ($this->relationLoaded('roles')) {
            return $this->roles->filter(function ($role) use ($now) {
                $pivot = $role->pivot;
                return $role->is_active
                    && $pivot->is_active
                    && ($pivot->start_date === null || \Carbon\Carbon::parse($pivot->start_date)->lte($now))
                    && ($pivot->end_date === null || \Carbon\Carbon::parse($pivot->end_date)->gte($now));
            })->sortByDesc(fn($r) => $r->pivot->start_date . $r->pivot->created_at);
        }

        return $this->roles()
            ->where('roles.is_active', true)
            ->wherePivot('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where(function ($sq) use ($now) {
                    $sq->whereNull('user_role.start_date')
                       ->orWhere('user_role.start_date', '<=', $now);
                })->where(function ($sq) use ($now) {
                    $sq->whereNull('user_role.end_date')
                       ->orWhere('user_role.end_date', '>=', $now);
                });
            })
            ->orderByPivot('start_date', 'desc')
            ->orderByPivot('created_at', 'desc')
            ->get();
    }

    /**
     * Check if this user holds any of the given role slugs.
     */
    public function hasRole(string|array $roles): bool {
        $slugs = array_map(fn($r) => Str::slug($r), (array) $roles);
        return $this->getActiveRoles()->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Returns the highest hierarchy level across all active roles.
     */
    public function getHierarchyLevel(): int {
        return $this->getActiveRoles()->max('hierarchy_level') ?? 0;
    }

    /**
     * Super Admin: has 'super-admin' role OR a direct super-admin permission grant.
     */
    public function isSuperAdmin(): bool {
        if ($this->hasRole(['super-admin', 'super_admin'])) {
            return true;
        }

        return $this->directPermissions()
            ->wherePivot('is_active', true)
            ->where('permissions.is_active', true)
            ->whereIn('slug', ['super-admin', 'super_admin'])
            ->where('user_permission.granted', true)
            ->where(function ($q) {
                $now = now();
                $q->where(function ($sq) use ($now) {
                    $sq->whereNull('user_permission.start_date')
                       ->orWhere('user_permission.start_date', '<=', $now);
                })->where(function ($sq) use ($now) {
                    $sq->whereNull('user_permission.end_date')
                       ->orWhere('user_permission.end_date', '>=', $now);
                });
            })
            ->exists();
    }

    /**
     * Check whether this user has the given permission slug.
     * Flow: Super Admin → Direct override → Role-inherited.
     * Implicit: create/edit/delete on a module implies view on the same module.
     */
    public function hasPermission(string $permissionSlug): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->checkRawPermission($permissionSlug)) {
            return true;
        }

        // Implicit view: if the user can write to a module they can view it
        $parts = explode('.', $permissionSlug);
        if (count($parts) === 2 && $parts[1] === 'view') {
            $module = $parts[0];
            if (
                $this->checkRawPermission("$module.create") ||
                $this->checkRawPermission("$module.edit")   ||
                $this->checkRawPermission("$module.delete") ||
                $this->checkRawPermission("$module.*")
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Raw permission lookup: checks direct overrides first, then role-inherited permissions.
     */
    private function checkRawPermission(string $permissionSlug): bool {
        $now = now();
        $wildcardSlug = str_contains($permissionSlug, '.')
            ? explode('.', $permissionSlug)[0] . '.*'
            : null;

        // 1. Direct override (loaded or queried)
        if ($this->relationLoaded('directPermissions')) {
            $override = $this->directPermissions->first(function ($p) use ($now, $permissionSlug, $wildcardSlug) {
                $pivot = $p->pivot;
                return ($p->slug === $permissionSlug || ($wildcardSlug && $p->slug === $wildcardSlug))
                    && $p->is_active
                    && $pivot->is_active
                    && ($pivot->start_date === null || \Carbon\Carbon::parse($pivot->start_date)->lte($now))
                    && ($pivot->end_date === null || \Carbon\Carbon::parse($pivot->end_date)->gte($now));
            });

            if ($override) {
                return (bool) $override->pivot->granted;
            }
        } else {
            $override = $this->directPermissions()
                ->wherePivot('is_active', true)
                ->where('permissions.is_active', true)
                ->where(function ($q) use ($permissionSlug, $wildcardSlug) {
                    $q->where('slug', $permissionSlug);
                    if ($wildcardSlug) $q->orWhere('slug', $wildcardSlug);
                })
                ->where(function ($q) use ($now) {
                    $q->where(function ($sq) use ($now) {
                        $sq->whereNull('user_permission.start_date')
                           ->orWhere('user_permission.start_date', '<=', $now);
                    })->where(function ($sq) use ($now) {
                        $sq->whereNull('user_permission.end_date')
                           ->orWhere('user_permission.end_date', '>=', $now);
                    });
                })
                ->first();

            if ($override) {
                return (bool) $override->pivot->granted;
            }
        }

        // 2. Role-inherited permission
        return $this->getActiveRoles()
            ->filter(fn($role) => $role->is_active)
            ->flatMap(fn($role) => $role->permissions)
            ->filter(fn($p) => $p->is_active)
            ->contains(fn($p) => $p->slug === $permissionSlug || ($wildcardSlug && $p->slug === $wildcardSlug));
    }

    /**
     * Can this user modify another user?
     * Rules:
     *  - Cannot modify yourself.
     *  - Super Admins can modify anyone (except themselves).
     *  - Otherwise: must have a higher hierarchy level, OR must have originally assigned the target's role.
     */
    public function canModifyUser($targetUser): bool {
        if (!$targetUser instanceof \App\Models\User) {
            return false;
        }

        if ($this->id === $targetUser->id) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $myLevel     = $this->getActiveRoles()->max('hierarchy_level') ?? 0;
        $targetLevel = $targetUser->getActiveRoles()->max('hierarchy_level') ?? 0;

        if ($myLevel > $targetLevel) {
            return true;
        }

        // Manager-of-record: this user originally assigned the target's role
        return $targetUser->getActiveRoles()
            ->where('pivot.assigned_by', $this->id)
            ->isNotEmpty();
    }

    /**
     * Can this user assign or modify the given role?
     * Rule: must have a strictly higher hierarchy level than the role.
     * Exception: only user ID 1 can manage level-100+ roles.
     */
    public function canModifyRole(Role $role): bool {
        if ($this->isSuperAdmin()) {
            if ($role->hierarchy_level >= 100 && $this->id !== 1) {
                return false;
            }
            return true;
        }

        if ($role->hierarchy_level >= 100) {
            return false;
        }

        return ($this->getActiveRoles()->max('hierarchy_level') ?? 0) > $role->hierarchy_level;
    }
}
