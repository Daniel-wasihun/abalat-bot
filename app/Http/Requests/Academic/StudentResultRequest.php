<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\BaseRequest;
use App\Services\Academic\GradingService;

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
            'quiz_ca_score'    => ['nullable', 'numeric', 'min:0', 'max:' . GradingService::QUIZ_CA_MAX],
            'midterm_score'    => ['nullable', 'numeric', 'min:0', 'max:' . GradingService::MIDTERM_MAX],
            'final_exam_score' => ['nullable', 'numeric', 'min:0', 'max:' . GradingService::FINAL_MAX],
            'remarks'          => 'nullable|string|max:1000',
            'change_reason'    => 'nullable|string|max:500',
        ];
    }
}
