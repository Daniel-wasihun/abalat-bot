<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class LibraryScope implements Scope {
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Rules:
     * 1. If user has 'libraries.manage_all' or is a Patron, allow all (Personal isolation handled elsewhere).
     * 2. Else if staff has library_id, restrict to that branch.
     * 3. Else, restrict to NONE (deny operational access).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model) {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // If not logged in, we typically allow viewing (handled by other logic if needed)
        // or we can restrict everything. In this system, guests/non-assigned users 
        // can view books but not operational data.
        if (!$user) {
            return;
        }

        if ($user->canManageAllLibraries() || $user->isPatron()) {
            return;
        }

        if ($user->library_id) {
            $builder->where($model->getTable() . '.library_id', $user->library_id);
        } else {
            // No library assigned and no manage_all permission -> deny access to operational data
            // Staff members MUST have a library_id or manage_all to access circulation records.
            $builder->whereRaw('1 = 0');
        }
    }
}
