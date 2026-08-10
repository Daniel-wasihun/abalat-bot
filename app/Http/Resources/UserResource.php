<?php

namespace App\Http\Resources;

use Illuminate\Support\Collection;

class UserResource extends ApiResource {
    public static $wrap = 'user';
    protected static $userCache = [];

    public function toArray($request): array {
        /** @var \App\Models\User $user */
        $user = $this->resource;

        $allAssignments = $user->relationLoaded('roles')
            ? $user->roles->whereNull('pivot.revoked_at')->sortByDesc(fn($r) => $r->pivot->start_date ?? $r->pivot->created_at)
            : $user->roles()->whereNull('revoked_at')->orderByPivot('start_date', 'desc')->orderByPivot('created_at', 'desc')->get();

        $now = now();
        $activeAssignments = $allAssignments->filter(function ($r) use ($now) {
            $pivot = $r->pivot;
            return $pivot->is_active &&
                ($pivot->start_date === null || \Carbon\Carbon::parse($pivot->start_date)->lte($now)) &&
                ($pivot->end_date === null || \Carbon\Carbon::parse($pivot->end_date)->gte($now));
        });

        $primaryRole = $activeAssignments->first();

        $allPermissions = $user->relationLoaded('directPermissions')
            ? $user->directPermissions->sortByDesc('pivot.created_at')
            : $user->directPermissions()->orderByPivot('created_at', 'desc')->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'is_super_admin' => $user->isSuperAdmin(),
            'role' => $primaryRole ? [
                'name' => $primaryRole->name,
                'slug' => $primaryRole->slug
            ] : null,
            'assignments' => $allAssignments->map(function ($r) use ($now) {
                return [
                    'id' => $r->pivot->id,
                    'role_name' => $r->name,
                    'role_slug' => $r->slug,
                    'start_date' => $r->pivot->start_date,
                    'end_date' => $r->pivot->end_date,
                    'assigned_by' => $this->getAssignerDetail($r->pivot->assigned_by)['name'],
                    'assigned_by_avatar' => $this->getAssignerDetail($r->pivot->assigned_by)['avatar'],
                    'revoked_by' => $this->getAssignerDetail($r->pivot->revoked_by)['name'],
                    'revoked_by_avatar' => $this->getAssignerDetail($r->pivot->revoked_by)['avatar'],
                    'is_active' => (bool)$r->pivot->is_active,
                    'is_currently_valid' => $r->pivot->is_active &&
                        ($r->pivot->start_date === null || \Carbon\Carbon::parse($r->pivot->start_date)->lte($now)) &&
                        ($r->pivot->end_date === null || \Carbon\Carbon::parse($r->pivot->end_date)->gte($now))
                ];
            })->values(),
            'scheduled_roles' => $allAssignments->filter(
                fn($r) =>
                !$r->pivot->is_active &&
                    $r->pivot->start_date &&
                    \Carbon\Carbon::parse($r->pivot->start_date)->gt($now)
            )->map(function ($r) {
                return [
                    'id' => $r->pivot->id,
                    'role_name' => $r->name,
                    'role_slug' => $r->slug,
                    'start_date' => $r->pivot->start_date,
                    'end_date' => $r->pivot->end_date,
                    'assigned_by' => $this->getAssignerDetail($r->pivot->assigned_by)['name'],
                    'assigned_by_avatar' => $this->getAssignerDetail($r->pivot->assigned_by)['avatar'],
                ];
            })->values(),
            'pending_permissions' => $allPermissions->filter(
                function ($p) use ($now) {
                    $pivot = $p->pivot;
                    // Include if it's not revoked AND
                    // EITHER: It's future-dated (!is_active and start > now)
                    // OR: It's currently active (is_active) but has an end_date
                    return !$pivot->revoked_at && (
                        (!$pivot->is_active && $pivot->start_date && \Carbon\Carbon::parse($pivot->start_date)->gt($now)) ||
                        $pivot->is_active
                    );
                }
            )->map(function ($p) use ($now) {
                return [
                    'id' => $p->pivot->id,
                    'permission_name' => $p->name,
                    'permission_slug' => $p->slug,
                    'permission_module' => $p->module,
                    'permission_action' => $p->action,
                    'action' => $p->pivot->granted ? 'grant' : 'revoke',
                    'action_label' => $p->pivot->granted ? \App\Services\BackMessage::get('action.grant') : \App\Services\BackMessage::get('action.revoke'),
                    'start_date' => $p->pivot->start_date,
                    'end_date' => $p->pivot->end_date,
                    'assigned_by' => $this->getAssignerDetail($p->pivot->assigned_by ?? $p->pivot->revoked_by)['name'],
                    'assigned_by_avatar' => $this->getAssignerDetail($p->pivot->assigned_by ?? $p->pivot->revoked_by)['avatar'],
                    'is_currently_active' => (bool)$p->pivot->is_active && (
                        ($p->pivot->start_date === null || \Carbon\Carbon::parse($p->pivot->start_date)->lte($now)) &&
                        ($p->pivot->end_date === null || \Carbon\Carbon::parse($p->pivot->end_date)->gte($now))
                    )
                ];
            })->values(),
            'permissions' => $this->getAllPermissions($activeAssignments, $allPermissions)->unique()->values()->toArray(),
            'info' => $this->formatUserInfo($this->info),
            'roles' => $activeAssignments->map(fn($r) => [
                'id'   => $r->id,
                'slug' => $r->slug,
                'name' => $r->name,
            ])->values(),
            'senbetMembership' => $user->relationLoaded('senbetMembership') ? $user->senbetMembership : null,

            'profile_picture' => $this->info && $this->info->profile_picture ? asset('storage/' . $this->info->profile_picture) : null,
            'avatar' => $this->info && $this->info->profile_picture ? asset('storage/' . $this->info->profile_picture) : null,
            'hierarchy_level' => $activeAssignments->max('hierarchy_level') ?? 0,
            'sessions' => $user->relationLoaded('sessions')
                ? $user->sessions->where('is_active', true)->map(function ($s) use ($user) {
                    $token = $user->token();
                    $currentTokenId = $token ? (string)($token->oauth_access_token_id ?? $token->id) : null;

                    // To calculate protection, we need to know the age of the CURRENT session
                    $currentSession = $currentTokenId 
                        ? $user->sessions->where('session_id', $currentTokenId)->first() 
                        : null;

                    $isCurrentSessionNew = $currentSession && $currentSession->created_at->gt(now()->subMonth());
                    $isThisSessionEstablished = $s->created_at->lt(now()->subMonth());
                    $isCurrent = $currentTokenId && $s->session_id === $currentTokenId;

                    return [
                        'id' => $s->id,
                        'session_id' => $s->session_id,
                        'device_name' => $s->device_name,
                        'device_type' => $s->device_type,
                        'browser' => $s->browser,
                        'platform' => $s->platform,
                        'ip_address' => $s->ip_address,
                        'location' => $s->location,
                        'last_active_at' => $s->last_active_at?->toDateTimeString(),
                        'last_active_at_human' => $s->last_active_at?->diffForHumans(),
                        'created_at' => $s->created_at?->toDateTimeString(),
                        'created_at_human' => $s->created_at?->diffForHumans(),
                        'is_current' => $isCurrent,
                        'is_protected' => $isCurrentSessionNew && $isThisSessionEstablished && !$isCurrent
                    ];
                })->sortByDesc('is_current')->values()
                : null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    protected function getAssignerDetail($id) {
        if (!$id) return ['name' => null, 'avatar' => null];
        if (isset(static::$userCache[$id])) return static::$userCache[$id];

        $u = \App\Models\User::with('info')->find($id);
        $detail = [
            'name' => $u ? $u->name : ['en' => 'System', 'am' => 'ሲስተም'],
            'avatar' => ($u && $u->info && $u->info->profile_picture) ? asset('storage/' . $u->info->profile_picture) : null
        ];

        static::$userCache[$id] = $detail;
        return $detail;
    }

    protected function formatUserInfo($info) {
        if (!$info) return null;

        $infoArray = $info->toArray();

        // Strip +251 country prefix for frontend display
        if (!empty($infoArray['phone_number']) && str_starts_with($infoArray['phone_number'], '+251')) {
            $infoArray['phone_number'] = substr($infoArray['phone_number'], 4);
        }

        return $infoArray;
    }

    protected function getAllPermissions(Collection $activeRoles, Collection $allPermissions): Collection {
        $now = now();

        // 1. Initial set from Active Roles
        $permissions = $activeRoles
            ->flatMap(fn($role) => $role->permissions)
            ->pluck('slug');

        // 2. Active Direct Overrides (already loaded in $allPermissions)
        $activeOverrides = $allPermissions->filter(function ($p) use ($now) {
            $pivot = $p->pivot;
            return $pivot->is_active &&
                ($pivot->start_date === null || \Carbon\Carbon::parse($pivot->start_date)->lte($now)) &&
                ($pivot->end_date === null || \Carbon\Carbon::parse($pivot->end_date)->gte($now));
        });

        // 3. Apply Professional Overrides (Merge & Filter)
        $grantedOverrides = $activeOverrides->where('pivot.granted', true)->pluck('slug');
        $revokedOverrides = $activeOverrides->where('pivot.granted', false)->pluck('slug');

        return $permissions
            ->merge($grantedOverrides)
            ->diff($revokedOverrides)
            ->unique()
            ->map(function ($slug) use ($activeRoles, $allPermissions) {
                // Find name from either roles or direct permissions
                $permModel = $activeRoles->flatMap(fn($r) => $r->permissions)->firstWhere('slug', $slug)
                    ?? $allPermissions->firstWhere('slug', $slug);

                return [
                    'slug' => $slug,
                    'name' => $permModel ? $permModel->name : ['en' => $slug, 'am' => $slug]
                ];
            });
    }
}
