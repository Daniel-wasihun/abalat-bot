<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInfo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding test users with varied roles...');

        $roles = Role::all()->keyBy('slug');

        if ($roles->isEmpty()) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        $createFullUser = function ($email, $first, $father, $password, $roleSlug, $assignerId = null, $userType = 'staff') use ($roles) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $first,
                    'password'  => Hash::make($password),
                    'is_active' => true,
                ]
            );

            $role = $roles[$roleSlug] ?? $roles[Str::slug($roleSlug)] ?? null;

            if ($role) {
                $user->roles()->syncWithoutDetaching([
                    $role->id => [
                        'assigned_by' => $assignerId,
                        'start_date'  => Carbon::now(),
                        'end_date'    => null,
                        'is_active'   => true,
                    ]
                ]);
            }

            UserInfo::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'registration_id'       => UserInfo::generateNextRegistrationId(),
                    'gender'                => collect(['male', 'female'])->random(),
                    'phone_number'          => collect(['7', '9'])->random() . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'father_name'           => $father,
                    'grandfather_name'      => 'Alemu',
                    'christian_name'        => 'Woldemariam',
                    'spiritual_father_name' => 'Aba Gebre Selassie',
                    'sub_city'              => collect(['Bole', 'Kirkos', 'Yeka', 'Arada', 'Nifas Silk', 'Gulele', 'Lideta', 'Kolfe Keranio'])->random(),
                    'woreda'                => str_pad(rand(1, 15), 2, '0', STR_PAD_LEFT),
                    'house_number'          => (string)rand(100, 999),
                    'address'               => 'Addis Ababa, Ethiopia',
                ]
            );

            return $user;
        };

        // Create Super Admin User
        $createFullUser(
            'superadmin@lms.com',
            'Yared',
            'Alemayehu',
            'password123',
            'super-admin',
            null,
            'staff'
        );

        // Create Admin User
        $createFullUser(
            'admin@lms.com',
            'Teklehaimanot',
            'Belay',
            'password123',
            'admin',
            null,
            'staff'
        );

        // Additional Admins
        $createFullUser('admin2@lms.com', 'Fasil', 'Kebede', 'password123', 'admin', null, 'staff');
        $createFullUser('admin3@lms.com', 'Mahlet', 'Tadesse', 'password123', 'admin', null, 'staff');

        // General fallback teacher and student
        $createFullUser(
            'teacher@lms.com',
            'Kidanemariam',
            'Zewde',
            'password123',
            'teacher',
            null,
            'teacher'
        );

        $createFullUser(
            'student@lms.com',
            'Gebremeskel',
            'Tessema',
            'password123',
            'student',
            null,
            'student'
        );

        // A user with MULTIPLE roles (Admin + Teacher)
        $multiUser = $createFullUser(
            'multi@lms.com',
            'Zenamarkos',
            'Abebe',
            'password123',
            'admin', // primary
            null,
            'staff'
        );
        if (isset($roles['teacher'])) {
             $multiUser->roles()->syncWithoutDetaching([
                $roles['teacher']->id => [
                    'assigned_by' => null,
                    'start_date'  => Carbon::now(),
                    'end_date'    => null,
                    'is_active'   => true,
                ]
            ]);
        }

        // A user with MULTIPLE roles (Student + Teacher)
        $multiUser2 = $createFullUser(
            'student.teacher@lms.com',
            'Ephrem',
            'Haile',
            'password123',
            'student', // primary
            null,
            'student'
        );
        if (isset($roles['teacher'])) {
             $multiUser2->roles()->syncWithoutDetaching([
                $roles['teacher']->id => [
                    'assigned_by' => null,
                    'start_date'  => Carbon::now(),
                    'end_date'    => null,
                    'is_active'   => true,
                ]
            ]);
        }


        $this->command->info('Users seeded successfully! Default password for all is: password123');
    }
}
