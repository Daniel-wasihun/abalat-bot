<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BackMessage;

class BulkIdRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array {
        return [
            'ids.required' => BackMessage::get('validation.required', ['attribute' => BackMessage::get('common.selected')]),
            'ids.array'    => BackMessage::get('validation.array', ['attribute' => BackMessage::get('common.selected')]),
            'ids.min'      => BackMessage::get('policy.at_least_one'),
            'ids.*.integer' => BackMessage::get('validation.integer', ['attribute' => 'ID']),
        ];
    }
}
