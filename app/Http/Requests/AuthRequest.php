<?php

namespace App\Http\Requests;

class AuthRequest extends BaseRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        // Logout requires authentication
        if ($this->routeIs('login')) {
            return true;
        }

        if ($this->routeIs('logout')) {
            return auth('api')->check();
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        if ($this->routeIs('login')) {
            return [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ];
        }

        if ($this->routeIs('logout')) {
            return [
                'token' => ['required', 'string'],
            ];
        }

        return [];
    }
}
