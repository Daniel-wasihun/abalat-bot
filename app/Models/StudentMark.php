<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_component_id', 'student_id', 'marks_obtained'
    ];

    public function assessmentComponent()
    {
        return $this->belongsTo(AssessmentComponent::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
