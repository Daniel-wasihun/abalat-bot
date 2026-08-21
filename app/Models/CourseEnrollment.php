<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id', 'course_offering_id', 'student_id', 'status',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
