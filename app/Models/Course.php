<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'credit_hours',
        'senbet_class', 'semester', 'prerequisites',
        'duration_weeks', 'teaching_hours', 'schedule_details', 'is_active',
    ];

    protected $casts = [
        'name'          => 'array',
        'prerequisites' => 'array',
        'is_active'     => 'boolean',
    ];

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'course_teacher', 'course_id', 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_id', 'student_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function assessmentComponents()
    {
        return $this->hasMany(AssessmentComponent::class);
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    /**
     * Course Offerings: one per grade/class × year × semester.
     * Use offerings instead of senbet_class when assigning teachers and enrolling students.
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function activeOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class)->where('is_active', true);
    }
}
