<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BackMessage;

class BulkUserActionRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'exists:users,id',
            'action' => 'required|in:activate,deactivate,delete',
        ];
    }

    public function messages(): array {
        return [
            'ids.required'    => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.selected')]),
            'ids.array'       => BackMessage::get('validation.array', ['attribute' => BackMessage::get('common.selected')]),
            'ids.*.exists'    => BackMessage::get('validation.exists', ['attribute' => 'User ID']),
            'action.required' => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.actions')]),
            'action.in'       => BackMessage::get('validation.in', ['attribute' => BackMessage::get('common.actions')]),
        ];
    }
}
