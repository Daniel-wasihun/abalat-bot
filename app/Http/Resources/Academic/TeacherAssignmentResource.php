<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;

class TeacherAssignmentResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'teacher_id'         => $this->teacher_id,
            'course_offering_id' => $this->course_offering_id,
            'assigned_by'        => $this->assigned_by,
            'teacher'            => new UserResource($this->whenLoaded('teacher')),
            'assigned_by_user'   => new UserResource($this->whenLoaded('assignedBy')),
            'course_offering'    => new CourseOfferingResource($this->whenLoaded('courseOffering')),
        ];
    }
}
