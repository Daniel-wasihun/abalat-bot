<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\BaseRequest;

class StudentResultRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('academic_classes.manage') ||
               $this->user()->hasPermission('academic_courses.manage');
    }

    public function rules(): array
    {
        return [
            'scores'        => ['nullable', 'array'],
            'scores.*'      => ['nullable', 'numeric', 'min:0'],
            'remarks'       => 'nullable|string|max:1000',
            'change_reason' => 'nullable|string|max:500',
        ];
    }
}
