<?php

namespace App\Models;

use App\Traits\HasCustomPermissions;
use App\Traits\HasSorting;
use App\Traits\Localizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable as OAuthenticatableContract;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatableContract
{
    use HasApiTokens, HasFactory, Notifiable, HasCustomPermissions, SoftDeletes, Localizable, HasSorting;

    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'name'              => 'array',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('id', 'assigned_by', 'start_date', 'end_date', 'revoked_by', 'revoked_at', 'is_active')
            ->withTimestamps();
    }

    public function info(): HasOne
    {
        return $this->hasOne(UserInfo::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function senbetMembership(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SenbetMembership::class);
    }

    public function teachingCourses()
    {
        return $this->belongsToMany(Course::class, 'course_teacher', 'teacher_id', 'course_id');
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'course_enrollments', 'student_id', 'course_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    /**
     * Offering-scoped enrollment (new system).
     * A student is enrolled in specific CourseOfferings (class × year × semester).
     */
    public function enrolledInOfferings()
    {
        return $this->belongsToMany(CourseOffering::class, 'course_enrollments', 'student_id', 'course_offering_id')
                    ->withPivot('status', 'course_id')
                    ->withTimestamps();
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    /**
     * Course offerings this teacher is assigned to.
     */
    public function assignedOfferings()
    {
        return $this->belongsToMany(CourseOffering::class, 'teacher_assignments', 'teacher_id', 'course_offering_id')
                    ->withTimestamps();
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResult::class, 'student_id');
    }

    public function suspiciousActivities(): HasMany
    {
        return $this->hasMany(SuspiciousActivity::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system_admin');
    }

    // ─── Custom Sort Scopes ───────────────────────────────────────────────────

    public function sortByRole($query, $sortOrder)
    {
        return $query
            ->leftJoin('user_role', fn($j) => $j->on('users.id', '=', 'user_role.user_id')->where('user_role.is_active', true))
            ->leftJoin('roles', 'user_role.role_id', '=', 'roles.id')
            ->select('users.*')
            ->selectRaw('MAX(roles.hierarchy_level) as top_role')
            ->groupBy('users.id')
            ->orderBy('top_role', $sortOrder);
    }

    public function sortByUserType($query, $sortOrder)
    {
        return $query
            ->leftJoin('user_info', 'users.id', '=', 'user_info.user_id')
            ->select('users.*')
            ->orderBy('user_info.user_type', $sortOrder);
    }

    public function sortByType($query, $sortOrder)
    {
        return $this->sortByUserType($query, $sortOrder);
    }
}
