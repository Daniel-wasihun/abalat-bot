<?php

namespace App\Http\Requests;

class ProfileUpdateRequest extends BaseRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|string|in:male,female',
            'profile_picture' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,heic,heif|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array {
        return [
            'name.required' => 'validation.required',
            'gender.required' => 'validation.required',
            'gender.in' => 'validation.gender_format',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array {
        return [
            'name' => 'field.name',
            'gender' => 'field.gender',
            'address' => 'field.address',
            'date_of_birth' => 'field.date_of_birth',
            'profile_picture' => 'field.profile_picture',
        ];
    }
}
