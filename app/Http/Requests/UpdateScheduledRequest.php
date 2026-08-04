<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BackMessage;

class UpdateScheduledRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array {
        return [
            'start_date.date'           => BackMessage::get('validation.date', ['attribute' => BackMessage::get('field.start_date')]),
            'end_date.date'             => BackMessage::get('validation.date', ['attribute' => BackMessage::get('field.end_date')]),
            'end_date.after_or_equal'   => BackMessage::get('validation.after_or_equal', ['attribute' => BackMessage::get('field.end_date'), 'date' => BackMessage::get('field.start_date')]),
        ];
    }
}
