<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends BaseRequest {
    public function authorize(): bool {
        return auth('api')->check();
    }

    public function rules(): array {
        return [
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&!#%^&*()\-+_={}\[\]|\\:;"\'<>,.\/]).+$/', 'confirmed'],
        ];
    }

    public function messages(): array {
        return [
            'password.confirmed' => 'auth.password_mismatch',
            'password.regex' => 'validation.password_complexity',
        ];
    }
}
