<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'name', 'percentage', 'type'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function marks()
    {
        return $this->hasMany(StudentMark::class);
    }
}
