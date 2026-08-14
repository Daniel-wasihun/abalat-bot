<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends BaseRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('post')) {
            return $this->user()->hasPermission('academic_courses.create');
        }
        return $this->user()->hasPermission('academic_courses.edit');
    }

    public function rules(): array
    {
        $courseId = $this->route('id') ?? $this->route('course');
        $isPost = $this->isMethod('post');

        return [
            'name'              => [$isPost ? 'required' : 'sometimes|required', 'string', 'max:255'],
            'code'              => [
                $isPost ? 'required' : 'sometimes|required',
                'string',
                'max:50',
                $courseId ? Rule::unique('courses', 'code')->ignore($courseId) : Rule::unique('courses', 'code'),
            ],
            'senbet_class'      => 'nullable|string',
            'semester'          => [$isPost ? 'required' : 'sometimes|required', 'in:1,2'],
            'credit_hours'      => [$isPost ? 'required' : 'sometimes|required', 'integer', 'min:1'],
            'description'       => 'nullable|string',
            'prerequisites'     => 'nullable|array',
            'duration_weeks'    => 'nullable|integer|min:1',
            'teaching_hours'    => 'nullable|integer|min:1',
            'schedule_details'  => 'nullable|string|max:500',
            'is_active'         => 'boolean',
            'grade_levels'      => 'nullable|array',
            'grade_levels.*'    => 'string',
            'academic_year_id'  => 'nullable|exists:academic_years,id',
        ];
    }
}
