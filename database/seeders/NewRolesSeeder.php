<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;

class NewRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['slug' => 'student', 'name_en' => 'Student'],
            ['slug' => 'teacher', 'name_en' => 'Teacher'],
            ['slug' => 'administrator', 'name_en' => 'Administrator'],
            ['slug' => 'executive', 'name_en' => 'Executive'],
            ['slug' => 'finance', 'name_en' => 'Finance'],
            ['slug' => 'librarian', 'name_en' => 'Librarian'],
            ['slug' => 'secretary', 'name_en' => 'Secretary'],
            ['slug' => 'other', 'name_en' => 'Other'],
        ];

        $level = 20; // Starting at 20, keeping higher level for existing admin if needed.

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => [
                        'en' => $roleData['name_en'],
                        'am' => $roleData['name_en'], // Defaulting to english name if translation isn't provided
                    ],
                    'description' => [
                        'en' => $roleData['name_en'] . ' Role',
                        'am' => $roleData['name_en'] . ' Role',
                    ],
                    'hierarchy_level' => $level,
                    'is_system_level' => false,
                    'is_active' => true,
                ]
            );
            $level--; // Increment or decrement depending on logic, let's keep it simple
        }
    }
}
