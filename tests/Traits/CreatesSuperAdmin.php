<?php

namespace Tests\Traits;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

trait CreatesSuperAdmin {
    protected $superAdmin;

    protected function createSuperAdmin(): void {
        // Ensure a department exists with proper hierarchy
        $campus = \App\Models\Campus::firstOrCreate(['slug' => 'main-campus'], ['name' => ['en' => 'Main Campus']]);
        $college = \App\Models\College::firstOrCreate(['slug' => 'engineering-college'], [
            'name' => ['en' => 'College of Engineering'],
            'campus_id' => $campus->id
        ]);
        $school = \App\Models\School::firstOrCreate(['slug' => 'tech-school'], [
            'name' => ['en' => 'School of Technology'],
            'college_id' => $college->id
        ]);
        $dept = Department::firstOrCreate(['slug' => 'default-department'], [
            'id' => 1,
            'name' => ['en' => 'Default Department'],
            'school_id' => $school->id,
            'short_code' => 'DD',
            'total_year' => 4
        ]);

        // Create super-admin role
        $adminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => ['en' => 'Super Admin'],
                'hierarchy_level' => 100,
                'is_system_level' => true
            ]
        );

        // Ensure basic permissions exist
        $permissions = [
            ['module' => \App\Constants\Module::USERS, 'action' => \App\Constants\Action::VIEW],
            ['module' => \App\Constants\Module::USERS, 'action' => \App\Constants\Action::EDIT],
            ['module' => \App\Constants\Module::USERS, 'action' => \App\Constants\Action::DELETE],
            ['module' => \App\Constants\Module::ROLES, 'action' => \App\Constants\Action::VIEW],
            ['module' => \App\Constants\Module::ROLES, 'action' => \App\Constants\Action::EDIT],
            ['module' => \App\Constants\Module::ROLES, 'action' => \App\Constants\Action::DELETE],
            ['module' => \App\Constants\Module::BOOKS, 'action' => \App\Constants\Action::VIEW],
            ['module' => \App\Constants\Module::BOOKS, 'action' => \App\Constants\Action::CREATE],
            ['module' => \App\Constants\Module::BOOKS, 'action' => \App\Constants\Action::EDIT],
        ];

        foreach ($permissions as $p) {
            $slug = $p['module'] . '.' . $p['action'];
            $permission = Permission::withTrashed()->where('slug', $slug)->first();

            if ($permission) {
                if ($permission->trashed()) {
                    $permission->restore();
                }
                // Update properties if needed, name is auto-generated in saving hook
                $permission->module = $p['module'];
                $permission->action = $p['action'];
                $permission->save();
            } else {
                Permission::create([
                    'module' => $p['module'],
                    'action' => $p['action'],
                    'name' => ['en' => ucfirst($p['action']) . ' ' . ucfirst($p['module'])]
                ]);
            }
        }

        // Assign all permissions to super-admin
        $adminRole->permissions()->sync(Permission::all());

        // Create the super admin user
        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@lms.com'],
            [
                'password' => Hash::make('password123'),
                'name' => ['en' => 'Super Administrator'],
                'is_active' => true
            ]
        );

        // Create UserInfo if it doesn't exist
        \App\Models\UserInfo::firstOrCreate(
            ['user_id' => $this->superAdmin->id],
            [
                'user_university_id' => 'LMS10000000',
                'user_type' => 'staff',
                'gender' => 'male',
                'phone_number' => '+251911111111',
                'department_id' => $dept->id
            ]
        );

        // Assign role if not already assigned
        if (!$this->superAdmin->roles()->where('role_id', $adminRole->id)->exists()) {
            $this->superAdmin->roles()->attach($adminRole->id, [
                'is_active' => true,
                'start_date' => now(),
                'assigned_by' => $this->superAdmin->id
            ]);
        }
    }
}
