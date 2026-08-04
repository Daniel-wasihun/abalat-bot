<?php

namespace App\Http\Requests;

use App\Constants\Action;
use App\Constants\Module;
use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends BaseRequest {
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
        if ($this->isMethod('post')) {
            return [
                'module' => ['required', 'string', Rule::in(Module::all())],
                'action' => ['required', 'string', Rule::in(Action::all())],
                'name' => ['nullable', 'string'],
                'description' => ['nullable', 'string'],
            ];
        }

        return [
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator) {
        $validator->after(function ($validator) {
            if ($this->isMethod('post')) {
                $module = $this->input('module');
                $action = $this->input('action');

                if ($module && $action) {
                    $slug = "{$module}.{$action}";
                    if (Permission::where('slug', $slug)->exists()) {
                        $validator->errors()->add('slug', 'validation.unique|attribute=Permission');
                    }
                }
            }
        });
    }
}
