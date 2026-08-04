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

            $prefix = $userType === 'student' ? 'STU' : ($userType === 'teacher' ? 'TCH' : 'STF');
            $digits = rand(10000000, 99999999);

            UserInfo::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_university_id' => $prefix . $digits,
                    'user_type' => $userType,
                    'gender' => collect(['male', 'female'])->random(),
                    'phone_number' => '+251' . collect(['7', '9'])->random() . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'date_of_birth' => Carbon::now()->subYears(rand($userType === 'student' ? 18 : 25, 50))->format('Y-m-d'),
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

        $this->command->info('Users seeded successfully!');
    }
}
