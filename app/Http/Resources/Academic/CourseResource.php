<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\ApiResource;

class CourseResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'code'             => $this->code,
            'credit_hours'     => $this->credit_hours,
            'description'      => $this->description,
            'senbet_class'     => $this->senbet_class,
            'semester'         => $this->semester,
            'prerequisites'    => $this->prerequisites,
            'duration_weeks'   => $this->duration_weeks,
            'teaching_hours'   => $this->teaching_hours,
            'schedule_details' => $this->schedule_details,
            'is_active'        => $this->is_active,
            'offerings'        => CourseOfferingResource::collection($this->whenLoaded('offerings')),
        ];
    }
}
