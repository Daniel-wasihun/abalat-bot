<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\Localizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model {

    use HasSlug, Localizable, SoftDeletes, \App\Traits\HasSorting;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'hierarchy_level',
        'is_system_level',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
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

    protected function localizable(): array {
        return ['name', 'description'];
    }

    public function sortByName($query, $sortOrder) {
        return $query->orderByRaw("name->>'en' {$sortOrder}");
    }

    public function sortByUsersCount($query, $sortOrder) {
        return $query->withCount('users')->orderBy('users_count', $sortOrder);
    }
}
