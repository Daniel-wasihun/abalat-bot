<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\BaseRequest;

class EnrollStudentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('academic_courses.manage');
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:users,id',
        ];
    }
}
