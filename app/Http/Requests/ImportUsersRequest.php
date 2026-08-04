<?php

namespace App\Http\Requests;

use App\Services\BackMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportUsersRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'file' => 'required|file|mimes:csv,txt',
            'role' => 'required|string|exists:roles,slug',
        ];
    }

    public static function rowRules(): array {
        $idRegex = '/^[A-Za-z]{2,5}\d{5,8}$/';
        $attrLabels = self::rowAttributes();

        return [
            'name' => [
                'required',
                'string',
                'min:7',
                'max:255',
                function ($attribute, $value, $fail) use ($attrLabels) {
                    if (!preg_match('/^[A-Za-z\s]+$/', $value)) {
                        $fail(BackMessage::get('validation.letters_only', [':attribute' => $attrLabels['name']]));
                    } elseif (!preg_match('/^\S+\s+\S+.*$/', $value)) {
                        $fail(BackMessage::get('validation.name_format', [':attribute' => $attrLabels['name']]));
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'user_university_id' => ['required', 'string', Rule::unique('user_info', 'user_university_id')->whereNull('deleted_at'), 'regex:' . $idRegex],
            'user_type' => ['required', 'string', 'in:' . implode(',', \App\Constants\Type::all())],
            'gender' => ['required', 'string', 'in:male,female'],
            'phone_number' => ['nullable', 'string', 'regex:/^[79]\d{8}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:-15 years'],
        ];
    }

    public static function rowMessages(): array {
        $attrLabels = self::rowAttributes();
        return [
            'required' => BackMessage::get('validation.required', [':attribute' => ':attribute']),
            'min' => BackMessage::get('validation.min_length', [':count' => ':min', ':attribute' => ':attribute']),
            'email' => BackMessage::get('validation.email', [':attribute' => ':attribute']),
            'unique' => BackMessage::get('validation.unique', [':attribute' => ':attribute']),
            'user_university_id.unique' => BackMessage::get('validation.unique', [':attribute' => $attrLabels['user_university_id']]),
            'regex' => BackMessage::get('validation.id_format', ['l_min' => 2, 'l_max' => 5, 'd_min' => 5, 'd_max' => 8, ':attribute' => ':attribute']),
            'in' => BackMessage::get('validation.in', [':attribute' => ':attribute']),
            'before' => BackMessage::get('validation.age_requirement', [':attribute' => ':attribute']),
        ];
    }

    public static function rowAttributes(): array {
        return [
            'name' => BackMessage::get('attributes.name'),
            'email' => BackMessage::get('attributes.email'),
            'user_university_id' => BackMessage::get('attributes.user_university_id'),
            'gender' => BackMessage::get('attributes.gender'),
            'user_type' => BackMessage::get('attributes.user_type'),
            'phone_number' => BackMessage::get('attributes.phone_number'),
            'date_of_birth' => BackMessage::get('attributes.date_of_birth'),
            'address' => BackMessage::get('attributes.address'),
        ];
    }
}
