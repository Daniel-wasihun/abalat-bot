<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInfo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $this->command->info('Seeding test users...');

        $roles = Role::all()->keyBy('slug');

        if ($roles->isEmpty()) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        $createFullUser = function ($email, $name, $password, $roleSlug, $assignerId = null, $userType = 'staff') use ($roles) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => ['en' => $name, 'am' => $name],
                    'password' => Hash::make($password),
                    'is_active' => true,
                ]
            );

            $role = $roles[$roleSlug] ?? $roles[Str::slug($roleSlug)] ?? null;

            if ($role) {
                $user->roles()->syncWithoutDetaching([
                    $role->id => [
                        'assigned_by' => $assignerId,
                        'start_date' => Carbon::now(),
                        'end_date' => null,
                        'is_active' => true,
                    ]
                ]);
            }

            $prefix = strtoupper(substr($userType, 0, 2));
            $digits = rand(10000000, 99999999);

            UserInfo::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'registration_id' => UserInfo::generateNextRegistrationId(),
                    'gender' => collect(['male', 'female'])->random(),
                    'phone_number' => '+251' . collect(['7', '9'])->random() . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'father_name' => 'Father ' . $name,
                    'grandfather_name' => 'Grandfather ' . $name,
                    'christian_name' => 'Christian ' . $name,
                    'spiritual_father_name' => 'Aba ' . $name,
                    'sub_city' => 'Bole',
                    'woreda' => '03',
                    'house_number' => '123',
                    'address' => 'Addis Ababa, Ethiopia',
                ]
            );

            return $user;
        };

        // Create Super Admin User
        $createFullUser(
            'superadmin@lms.com',
            'Super Administrator',
            'password123',
            'super_admin',
            null,
            'staff'
        );

        // Create Admin User
        $createFullUser(
            'admin@lms.com',
            'School Administrator',
            'password123',
            'admin',
            null,
            'staff'
        );

        // Create Teacher User
        $createFullUser(
            'teacher@lms.com',
            'Sunday School Teacher',
            'password123',
            'teacher',
            null,
            'teacher'
        );

        // Create Student User
        $createFullUser(
            'student@lms.com',
            'Sunday School Student',
            'password123',
            'student',
            null,
            'student'
        );

        $this->command->info('Users seeded successfully!');
    }
}
