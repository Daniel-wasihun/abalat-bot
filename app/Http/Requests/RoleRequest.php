<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends BaseRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        $currentUser = $this->user();

        if (!$currentUser) {
            return false;
        }

        $myLevel = $currentUser->roles()->max('hierarchy_level') ?? 0;

        // Creation logic
        if ($this->isMethod('post')) {
            $requestedLevel = $this->input('hierarchy_level', 1);
            return $myLevel > $requestedLevel;
        }

        // Update logic
        $targetRole = $this->route('role');
        if ($targetRole && $myLevel <= $targetRole->hierarchy_level) {
            return false;
        }

        // If hierarchy_level is being changed, the user must have a level strictly higher than the NEW level
        if ($this->has('hierarchy_level')) {
            $requestedLevel = $this->input('hierarchy_level');
            if ($myLevel <= $requestedLevel) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        $role = $this->route('role');
        $roleId = $role ? $role->id : null;
        $isPost = $this->isMethod('post');

        return [
            'name' => [
                $isPost ? 'required' : 'sometimes',
                'string',
                'min:4',
                'max:20',
                function ($attribute, $value, $fail) use ($roleId) {
                    $locale = app()->getLocale();
                    $query = \Illuminate\Support\Facades\DB::table('roles')
                        ->whereRaw("(\"name\"::jsonb)->>'$locale' = ?", [$value])
                        ->whereNull('deleted_at');
                    
                    if ($roleId) {
                        $query->where('id', '!=', $roleId);
                    }
                    
                    if ($query->exists()) {
                        $fail(trans('validation.unique', ['attribute' => 'name']));
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'hierarchy_level' => [$isPost ? 'required' : 'sometimes', 'integer', 'min:1', 'max:99'],
            'permissions' => [$isPost ? 'required' : 'sometimes', 'array', $isPost ? 'min:1' : 'nullable'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ];
    }
}
