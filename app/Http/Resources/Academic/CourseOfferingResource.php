<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;

class CourseOfferingResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'course_id'        => $this->course_id,
            'academic_year_id' => $this->academic_year_id,
            'senbet_class'     => $this->senbet_class,
            'semester'         => $this->semester,
            'is_active'        => $this->is_active,
            'course'           => $this->relationLoaded('course') && $this->course
                                    ? new CourseResource($this->course)
                                    : null,
            'academic_year'    => $this->academicYear?->year,
            'teachers'         => UserResource::collection($this->whenLoaded('teachers')),
            'students_count'   => $this->whenCounted('students'),
        ];
    }
}
