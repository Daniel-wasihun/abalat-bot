<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\Localizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Role extends Model implements Auditable {

    use HasSlug, SoftDeletes, \App\Traits\HasSorting, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'hierarchy_level',
        'is_system_level',
        'is_active',
    ];

    protected $casts = [
        'is_system_level' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function isHigherThan(Role $otherRole): bool {
        return $this->hierarchy_level > $otherRole->hierarchy_level;
    }

    public function isEqualOrHigherThan(Role $otherRole): bool {
        return $this->hierarchy_level >= $otherRole->hierarchy_level;
    }
    public function permissions(): BelongsToMany {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',   //  pivot table
            'role_id',           //  FK for Role
            'permission_id'      //  FK for Permission
        );
    }

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'user_role');
    }



    public function sortByName($query, $sortOrder) {
        return $query->orderBy('name', $sortOrder);
    }

    public function sortByUsersCount($query, $sortOrder) {
        return $query->withCount('users')->orderBy('users_count', $sortOrder);
    }
}
