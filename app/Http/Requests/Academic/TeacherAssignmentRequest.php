<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\BaseRequest;

class TeacherAssignmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('academic_courses.manage');
    }

    public function rules(): array
    {
        return [
            'teacher_ids'   => 'required|array',
            'teacher_ids.*' => 'exists:users,id',
        ];
    }
}
