<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BackMessage;

class BulkRoleActionRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'exists:roles,id',
            'active' => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array {
        return [
            'ids.required'    => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.selected')]),
            'ids.array'       => BackMessage::get('validation.array', ['attribute' => BackMessage::get('common.selected')]),
            'ids.*.exists'    => BackMessage::get('validation.exists', ['attribute' => 'Role ID']),
            'active.required' => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.status')]),
        ];
    }
}
