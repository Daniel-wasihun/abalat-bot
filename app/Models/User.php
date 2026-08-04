<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HasCustomPermissions;
use App\Traits\Localizable;
use App\Traits\HasSorting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Contracts\OAuthenticatable as OAuthenticatableContract;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements OAuthenticatableContract {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasCustomPermissions, SoftDeletes, Localizable, HasSorting;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'name' => 'array',
            'is_active' => 'boolean',
        ];
    }
    public function roles(): BelongsToMany {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('id', 'assigned_by', 'start_date', 'end_date', 'revoked_by', 'revoked_at', 'is_active')
            ->withTimestamps();
    }



    public function info(): HasOne {
        return $this->hasOne(UserInfo::class);
    }

    public function sessions(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(UserSession::class);
    }


    /**
     * Check if the user is a system admin.
     */
    public function isSystemAdmin(): bool {
        return $this->hasRole('system_admin');
    }

    public function suspiciousActivities(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(SuspiciousActivity::class);
    }

    public function sortByRole($query, $sortOrder) {
        return $query->leftJoin('user_role', function ($join) {
            $join->on('users.id', '=', 'user_role.user_id')
                ->where('user_role.is_active', true);
        })
            ->leftJoin('roles', 'user_role.role_id', '=', 'roles.id')
            ->select('users.*')
            ->selectRaw('MAX(roles.hierarchy_level) as top_role')
            ->groupBy('users.id')
            ->orderBy('top_role', $sortOrder);
    }

    public function sortByUserType($query, $sortOrder) {
        return $query->leftJoin('user_info', 'users.id', '=', 'user_info.user_id')
            ->select('users.*')
            ->orderBy('user_info.user_type', $sortOrder);
    }

    public function sortByType($query, $sortOrder) {
        return $this->sortByUserType($query, $sortOrder);
    }


}
