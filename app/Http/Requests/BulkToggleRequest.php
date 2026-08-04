<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BackMessage;

class BulkToggleRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'required|integer',
            'active' => 'required|boolean',
        ];
    }

    public function messages(): array {
        return [
            'ids.required'    => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.selected')]),
            'ids.array'       => BackMessage::get('validation.array', ['attribute' => BackMessage::get('common.selected')]),
            'active.required' => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.status')]),
        ];
    }
}
