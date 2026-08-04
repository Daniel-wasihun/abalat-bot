<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * Permission wildcard format: 'module.*'  → grants all actions
     * Exact format:               'module.action'
     */
    public function run(): void {
        $this->command->info('Seeding hierarchical roles...');

        $roles = [
            // ─────────────────────────────────────────────
            // SUPER ADMIN – every permission in the system
            // ─────────────────────────────────────────────
            'super_admin' => [
                'hierarchy_level' => 100,
                'description'     => 'Full system access',
                'permissions'     => Permission::all()->pluck('slug')->toArray(),
            ],

            // ─────────────────────────────────────────────
            // ADMIN – broad system management
            // ─────────────────────────────────────────────
            'admin' => [
                'hierarchy_level' => 80,
                'description'     => 'System administrator',
                'permissions'     => [
                    'dashboard.*',
                    'books.*',
                    'users.view',
                    'users.create',
                    'users.edit',
                    'roles.*',
                    'permissions.*',
                    'borrows.*',
                    'returns.*',
                    'fines.*',
                    'spot_readings.*',
                    'circulation_policies.*',
                    'libraries.*',
                    'shelves.*',
                    'categories.*',
                    'reports.view',
                    'security.view',
                    'settings.*',
                    'cataloging.*',
                    'campuses.*',
                    'colleges.*',
                    'schools.*',
                    'academic_years.*',
                    'courses.*',
                    'lecture_notes.*',
                    'course_outlines.*',
                    'worksheets.*',
                    'video_lectures.*',
                    'assignments.*',
                    'reference_books.*',
                    'wishlists.*',
                    // Attendance: full access including reporting and deletion
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'attendance.delete',
                    'attendance.report',
                ],
            ],

            // ─────────────────────────────────────────────
            // LIBRARY DIRECTOR – strategic oversight
            // ─────────────────────────────────────────────
            'library_director' => [
                'hierarchy_level' => 75,
                'description'     => 'Strategic management and library leadership',
                'permissions'     => [
                    'dashboard.view',
                    'reports.view',
                    'books.view',
                    'wishlists.*',
                    'libraries.view',
                    'borrows.view',
                    'fines.view',
                    'attendance.view',
                    'attendance.report',
                ],
            ],

            // ─────────────────────────────────────────────
            // MANAGER – operational oversight
            // ─────────────────────────────────────────────
            'manager' => [
                'hierarchy_level' => 65,
                'description'     => 'Library manager',
                'permissions'     => [
                    'dashboard.view',
                    'books.*',
                    'borrows.*',
                    'returns.*',
                    'fines.*',
                    'spot_readings.*',
                    'circulation_policies.*',
                    'libraries.view',
                    'shelves.*',
                    'categories.*',
                    'reports.view',
                    'lecture_notes.*',
                    'course_outlines.*',
                    'worksheets.*',
                    'video_lectures.*',
                    'assignments.*',
                    'reference_books.*',
                    'cataloging.*',
                    'wishlists.*',
                    // Manager: full attendance access including reports and deletion
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'attendance.delete',
                    'attendance.report',
                ],
            ],

            // ─────────────────────────────────────────────
            // ACQUISITION MANAGER – manages library acquisitions
            // ─────────────────────────────────────────────
            'acquisition_manager' => [
                'hierarchy_level' => 60,
                'description'     => 'Manages library acquisitions',
                'permissions'     => [
                    'dashboard.view',
                    'books.view',
                    'books.create',
                    'books.edit',
                    'books.delete',
                    'cataloging.*',
                    'wishlists.*',
                    'circulation_policies.view',
                    'libraries.view',
                    'shelves.view',
                    'categories.view',
                    'reports.view',
                ],
            ],

            // ─────────────────────────────────────────────
            // WISHLIST ADMINISTRATOR – full wishlist management
            // ─────────────────────────────────────────────
            'wishlist_administrator' => [
                'hierarchy_level' => 55,
                'description'     => 'Strategic management of library acquisition wishlist',
                'permissions'     => [
                    'dashboard.view',
                    'wishlists.*',
                    'books.view',
                    'categories.view',
                    'libraries.view',
                ],
            ],

            // ─────────────────────────────────────────────
            // LIBRARIAN – day-to-day operations
            // ─────────────────────────────────────────────
            'librarian' => [
                'hierarchy_level' => 40,
                'description'     => 'Library staff',
                'permissions'     => [
                    'dashboard.view',
                    'books.view',
                    'books.create',
                    'books.edit',
                    'borrows.view',
                    'borrows.create',
                    'borrows.edit',
                    'returns.view',
                    'returns.create',
                    'returns.edit',
                    'fines.view',
                    'spot_readings.view',
                    'spot_readings.create',
                    'spot_readings.edit',
                    'libraries.view',
                    'shelves.view',
                    'shelves.create',
                    'shelves.edit',
                    'categories.view',
                    'lecture_notes.view',
                    'course_outlines.view',
                    'worksheets.view',
                    'video_lectures.view',
                    'assignments.view',
                    'reference_books.view',
                    'cataloging.*',
                    'wishlists.*',
                    // Librarian: can log and view attendance, can delete within 2 min, CANNOT generate reports
                    'attendance.view',
                    'attendance.create',
                    'attendance.delete',
                ],
            ],

            // ─────────────────────────────────────────────
            // TEACHER – academic material management
            // ─────────────────────────────────────────────
            'teacher' => [
                'hierarchy_level' => 20,
                'description'     => 'Teacher / Academic staff',
                'permissions'     => [
                    'books.view',
                    'borrows.view',
                    'borrows.create',
                    'lecture_notes.*',
                    'course_outlines.*',
                    'worksheets.*',
                    'video_lectures.*',
                    'assignments.*',
                    'reference_books.*',
                    'categories.view',
                    'libraries.view',
                    'shelves.view',
                    'campuses.view',
                    'colleges.view',
                    'schools.view',
                    'academic_years.view',
                    'courses.view',
                    'wishlists.view',
                    'wishlists.create',
                    'wishlists.edit',
                    'wishlists.delete',
                ],
            ],

            // ─────────────────────────────────────────────
            // STUDENT – read-only access
            // ─────────────────────────────────────────────
            'student' => [
                'hierarchy_level' => 10,
                'description'     => 'University student',
                'permissions'     => [
                    'books.view',
                    'borrows.view',
                    'lecture_notes.view',
                    'course_outlines.view',
                    'worksheets.view',
                    'video_lectures.view',
                    'assignments.view',
                    'reference_books.view',
                    'categories.view',
                    'libraries.view',
                    'shelves.view',
                    'campuses.view',
                    'colleges.view',
                    'schools.view',
                    'academic_years.view',
                    'courses.view',
                ],
            ],
        ];

        foreach ($roles as $name => $data) {
            $enName = ucfirst(str_replace('_', ' ', $name));

            $role = Role::whereRaw("name->>'en' = ?", [$enName])->withTrashed()->first();

            $baseData = [
                'name' => [
                    'en' => $enName,
                    'am' => $enName,
                ],
                'description' => [
                    'en' => $data['description'],
                    'am' => $data['description'],
                ],
                'hierarchy_level' => $data['hierarchy_level'],
                'is_system_level' => true,
                'is_active'       => true,
            ];

            if ($role) {
                $role->update($baseData);
                if ($role->trashed()) {
                    $role->restore();
                }
            } else {
                $role = Role::create(array_merge(['slug' => Str::slug($name)], $baseData));
            }

            $permissionIds = collect();

            foreach ($data['permissions'] as $perm) {
                if (Str::endsWith($perm, '.*')) {
                    $prefix = Str::before($perm, '.*');
                    $ids = Permission::where('slug', 'like', "{$prefix}.%")->pluck('id');
                } else {
                    $ids = Permission::where('slug', $perm)->pluck('id');
                }
                $permissionIds = $permissionIds->merge($ids);
            }

            $role->permissions()->sync($permissionIds->unique());
        }

        $this->command->info('Roles seeded successfully!');
    }
}
