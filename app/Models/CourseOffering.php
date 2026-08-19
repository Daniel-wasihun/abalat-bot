<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

class CourseOffering extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'course_id',
        'academic_year_id',
        'senbet_class',
        'semester',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_assignments', 'course_offering_id', 'teacher_id')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_offering_id', 'student_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResult::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where(function ($q) use ($teacherId) {
            $q->whereHas('teacherAssignments', function ($q2) use ($teacherId) {
                $q2->where('teacher_id', $teacherId);
            })->orWhereHas('course.teachers', function ($q3) use ($teacherId) {
                $q3->where('users.id', $teacherId); // course.teachers uses the users table
            });
        });
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->whereHas('enrollments', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });
    }
}
