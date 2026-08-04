<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use App\Constants\Type;

class UserRequest extends BaseRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        // Registration is open (or handled by middleware/controller logic for admin creation)
        // Update is restricted to admin or self (handled by controller/policy usually, but we can add checks here)

        // For simplicity, we rely on the controller/middleware for permissions, 
        // similar to RegisterRequest which just returned true.
        // UpdateUserRequest had checks for 'super_admin' or self conceptually. 
        // We will allow true for now as Auth check is done in BaseRequest or middleware.
        return true;
    }

    const NAME_MIN = 7;
    const ID_MIN_LETTERS = 2;
    const ID_MAX_LETTERS = 5;
    const ID_MIN_DIGITS = 5;
    const ID_MAX_DIGITS = 8;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        $userId = $this->route('user') ? $this->route('user')->id : null;
        $isRegister = ($this->routeIs('register') || $this->routeIs('users.register') || $this->routeIs('*.register') || $this->isMethod('post')) && !$this->routeIs('users.update');

        $idRegex = sprintf('/^[A-Za-z]{%d,%d}\d{%d,%d}$/', self::ID_MIN_LETTERS, self::ID_MAX_LETTERS, self::ID_MIN_DIGITS, self::ID_MAX_DIGITS);

        $rules = [
            'name' => [
                $isRegister ? 'required' : 'nullable',
                'string',
                'min:' . self::NAME_MIN,
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^[A-Za-z\s]+$/', $value)) {
                        $fail('validation.letters_only');
                    } elseif (!preg_match('/^\S+\s+\S+.*$/', $value)) {
                        $fail('validation.name_format');
                    }
                },
            ],
            'email' => [
                $isRegister ? 'required' : 'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&!#%^&*()\-+_={}\[\]|\\:;"\'<>,.\/]).+$/', 'confirmed'],
        ];

        if ($isRegister) {
            $rules['role'] = ['required_if:user_type,staff', 'nullable', 'string', 'exists:roles,slug'];
            $rules['permissions'] = ['nullable', 'array'];
            $rules['permissions.*'] = ['string', 'exists:permissions,slug'];
            $rules['start_date'] = ['nullable', 'date'];
            $rules['end_date'] = ['nullable', 'date', 'after_or_equal:start_date'];

            $rules['user_university_id'] = ['required', 'string', 'unique:user_info,user_university_id', 'regex:' . $idRegex];
            $rules['user_type'] = ['required', 'string', 'in:' . implode(',', Type::all())];
            $rules['gender'] = ['required', 'string', 'in:male,female'];
            $rules['phone_number'] = ['nullable', 'string', 'regex:/^[79]\d{8}$/'];
            $rules['date_of_birth'] = ['nullable', 'date', 'before:-15 years'];
            $rules['address'] = ['nullable', 'string'];
            $rules['profile_picture'] = ['nullable', 'image', 'max:2048'];
        } else {
            $rules['is_active'] = ['nullable', 'boolean'];
            $rules['user_university_id'] = [
                'sometimes',
                'required',
                'string',
                'unique:user_info,user_university_id,' . ($userId ?? 'NULL') . ',user_id',
                'regex:' . $idRegex
            ];
            $rules['user_type'] = ['nullable', 'string', 'in:' . implode(',', Type::all())];
            $rules['gender'] = ['required', 'string', 'in:male,female'];
            $rules['phone_number'] = ['nullable', 'string', 'regex:/^[79]\d{8}$/'];
            $rules['date_of_birth'] = ['nullable', 'date', 'before:-15 years'];
            $rules['address'] = ['nullable', 'string'];
            $rules['profile_picture'] = ['nullable', 'image', 'max:2048'];
            $rules['remove_profile_picture'] = ['nullable', 'boolean'];

            if ($this->has('role')) {
                $rules['role'] = ['string', 'exists:roles,slug'];
            }
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'name.required' => 'validation.required',
            'name.min' => 'validation.min_length|count=' . self::NAME_MIN,
            'email.required' => 'validation.required',
            'email.email' => 'validation.email',
            'user_university_id.required' => 'validation.required',
            'user_university_id.regex' => sprintf('validation.id_format|l_min=%d,l_max=%d,d_min=%d,d_max=%d', self::ID_MIN_LETTERS, self::ID_MAX_LETTERS, self::ID_MIN_DIGITS, self::ID_MAX_DIGITS),
            'phone_number.regex' => 'validation.phone_format',
            'gender.required' => 'validation.required',
            'gender.in' => 'validation.gender_format',
            'user_type.required' => 'validation.required',
            'password.confirmed' => 'auth.password_mismatch',
            'password.regex' => 'validation.password_complexity',
            'date_of_birth.before' => 'validation.age_requirement',
        ];
    }
    public function attributes(): array {
        return [
            'name' => 'attributes.name',
            'email' => 'attributes.email',
            'user_university_id' => 'attributes.user_university_id',
            'user_type' => 'attributes.user_type',
            'gender' => 'attributes.gender',
            'phone_number' => 'attributes.phone_number',
            'date_of_birth' => 'attributes.date_of_birth',
            'address' => 'attributes.address',
            'profile_picture' => 'attributes.profile_picture',
        ];
    }
}
