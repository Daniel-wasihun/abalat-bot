<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;

class StudentResultResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'student_id'       => $this->student_id,
            'course_offering_id' => $this->course_offering_id,
            'quiz_ca_score'    => $this->quiz_ca_score,
            'midterm_score'    => $this->midterm_score,
            'final_exam_score' => $this->final_exam_score,
            'total_score'      => $this->total_score,
            'letter_grade'     => $this->letter_grade,
            'remarks'          => $this->remarks,
            'is_finalized'     => $this->is_finalized,
            'recorded_by'      => $this->recordedBy?->name,
            'student'          => new UserResource($this->whenLoaded('student')),
            'course_offering'  => new CourseOfferingResource($this->whenLoaded('courseOffering')),
            'updated_at'       => $this->updated_at,
        ];
    }
}
