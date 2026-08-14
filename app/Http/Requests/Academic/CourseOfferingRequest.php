<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\BaseRequest;

class CourseOfferingRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('academic_courses.manage');
    }

    public function rules(): array
    {
        return [
            'senbet_class'      => 'required|string',
            'semester'          => 'required|in:1,2',
            'academic_year_id'  => 'nullable|exists:academic_years,id',
            'is_active'         => 'boolean',
        ];
    }
}
