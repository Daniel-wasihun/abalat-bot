<?php

namespace App\Http\Requests;

use App\Models\UserInfo;
use Illuminate\Validation\Rule;

class UserRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        // FormData sends roles[] — normalise to roles
        if (!$this->has('roles') && $this->has('roles[]')) {
            $merge['roles'] = $this->input('roles[]', []);
        }

        // Cast boolean string fields to actual booleans
        foreach (['is_active', 'is_member', 'previous_participation'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Auto-generate registration ID on create if not supplied
        if ($this->isCreating() && empty($this->input('registration_id'))) {
            $merge['registration_id'] = UserInfo::generateNextRegistrationId();
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $userId   = $this->route('user')?->id;
        $creating = $this->isCreating();

        $rules = [
            'name'     => [$creating ? 'required' : 'nullable', 'string', 'min:2', 'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^[A-Za-z]+$/', $value))  $fail('validation.letters_only');
                },
            ],
            'email'    => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).+$/',
            ],

            // Profile Info (both create & update)
            'gender'        => ['required', 'string', 'in:male,female'],
            'phone_number'  => ['required', 'string', 'regex:/^[79]\d{8}$/'],
            'father_name'        => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'grandfather_name'   => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'christian_name'     => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'spiritual_father_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z\s]+$/'],
            'sub_city'      => ['nullable', 'string', 'max:255'],
            'woreda'        => ['nullable', 'string', 'max:255'],
            'house_number'  => ['nullable', 'string', 'max:255'],
            'address'       => ['required', 'string'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],

            // Senbet Membership (always optional at field level; conditionally required via withValidator)
            'is_member'         => ['nullable', 'boolean'],
            'senbet_date_of_birth' => ['nullable', 'date', 'before:-15 years'],
            'senbet_class'      => ['nullable', 'string', 'exists:senbet_classes,code'],
            'section'           => ['nullable', 'string', 'max:50'],
            'education_level'   => ['nullable', 'string'],
            'emergency_name'    => ['nullable', 'string'],
            'emergency_phone'   => ['nullable', 'string'],
            'emergency_sub_city'    => ['nullable', 'string', 'max:255'],
            'emergency_woreda'      => ['nullable', 'string', 'max:255'],
            'emergency_house_number'=> ['nullable', 'string', 'max:255'],
            'emergency_address'     => ['nullable', 'string'],
            'previous_participation' => ['nullable', 'boolean'],
            'previous_participation_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

        // Registration ID
        $rules['registration_id'] = $creating
            ? ['nullable', 'string', 'unique:user_info,registration_id', 'regex:/^DBSS-\d{6,}$/']
            : ['sometimes', 'nullable', 'string', "unique:user_info,registration_id,{$userId},user_id", 'regex:/^DBSS-\d{6,}$/'];

        // Role assignment
        if ($creating) {
            $rules['roles']   = ['required', 'array', 'min:1'];
            $rules['roles.*'] = ['string', 'exists:roles,slug'];
            $rules['permissions']   = ['nullable', 'array'];
            $rules['permissions.*'] = ['string', 'exists:permissions,slug'];
            $rules['start_date'] = ['nullable', 'date'];
            $rules['end_date']   = ['nullable', 'date', 'after_or_equal:start_date'];
        } else {
            $rules['is_active'] = ['nullable', 'boolean'];
            $rules['remove_profile_picture'] = ['nullable', 'boolean'];
            if ($this->has('roles')) {
                $rules['roles']   = ['array', 'min:1'];
                $rules['roles.*'] = ['string', 'exists:roles,slug'];
            }
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        // Email required unless the user has only the student role
        $validator->sometimes('email', 'required', fn($input) =>
            collect($input->roles ?? [])->contains(fn($r) => $r !== 'student') ||
            empty($input->roles)
        );

        // Membership core fields required when is_member = true
        $isMember = fn($input) => (bool) ($input->is_member ?? false);
        $validator->sometimes(
            ['senbet_date_of_birth', 'education_level', 'emergency_name', 'emergency_phone', 'senbet_class'],
            'required',
            $isMember
        );

        // Participation document required when member + previous_participation
        $validator->sometimes('previous_participation_document', 'required', fn($input) =>
            $isMember($input) && (bool)($input->previous_participation ?? false)
        );
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'validation.required',
            'name.min'               => 'validation.min_length|count=7',
            'email.required'         => 'validation.required',
            'email.email'            => 'validation.email',
            'registration_id.unique' => 'validation.unique',
            'registration_id.regex'  => 'validation.id_format',
            'phone_number.required'  => 'validation.required',
            'phone_number.regex'     => 'validation.phone_format',
            'father_name.required'   => 'validation.required',
            'grandfather_name.required' => 'validation.required',
            'address.required'       => 'validation.required',
            'gender.required'        => 'validation.required',
            'gender.in'              => 'validation.gender_format',
            'password.confirmed'     => 'auth.password_mismatch',
            'password.regex'         => 'validation.password_complexity',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'               => 'attributes.name',
            'email'              => 'attributes.email',
            'registration_id'    => 'attributes.registration_id',
            'gender'             => 'attributes.gender',
            'phone_number'       => 'attributes.phone_number',
            'father_name'        => 'attributes.father_name',
            'grandfather_name'   => 'attributes.grandfather_name',
            'address'            => 'attributes.address',
            'profile_picture'    => 'attributes.profile_picture',
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function isCreating(): bool
    {
        return $this->isMethod('post') && !$this->routeIs('users.update');
    }
}
