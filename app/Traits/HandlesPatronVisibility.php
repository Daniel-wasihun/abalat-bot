<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HandlesPatronVisibility {
    /**
     * Scope a query to handle visibility permissions for patrons vs staff.
     * Patrons (hierarchy < 40) are strictly limited to active items.
     * Staff can filter by is_active if they choose, otherwise defaults to active.
     */
    public function scopeApplyPatronFilters(Builder $query, ?User $user, $request = null) {
        if (!$user) {
            return $query->where('is_active', true);
        }

        if ($user->isPatron()) {
            return $query->where('is_active', true);
        }

        // Staff Logic: Remove global active scope to allow seeing inactive items
        $query->withoutGlobalScope('active');

        if ($request && $request->filled('is_active')) {
            return $query->where('is_active', $request->boolean('is_active'));
        }

        // Default: If no explicit filter is provided, staff can see everything.
        return $query;
    }
}
