<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ActiveScope {
    /**
     * Boot the trait and add a global scope to filter by is_active = true.
     */
    public static function bootActiveScope() {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable() . '.is_active', true);
        });
    }

    /**
     * Scope to include inactive records.
     */
    public function scopeWithInactive(Builder $query) {
        return $query->withoutGlobalScope('active');
    }

    /**
     * Scope to only get inactive records.
     */
    public function scopeOnlyInactive(Builder $query) {
        return $query->withoutGlobalScope('active')->where('is_active', false);
    }
}
