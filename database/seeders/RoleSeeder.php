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
        $this->command->info('Cleaning up unrelated roles...');
        // Force delete any roles that do not belong to Senbet School
        Role::whereNotIn('slug', ['super-admin', 'admin', 'teacher', 'student'])->forceDelete();

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
                    'users.view',
                    'users.create',
                    'users.edit',
                    'roles.*',
                    'permissions.*',
                    'reports.view',
                    'security.view',
                    'settings.*',
                    'attendance.view',
                    'attendance.create',
                    'attendance.edit',
                    'attendance.delete',
                    'attendance.report',
                ],
            ],

            // ─────────────────────────────────────────────
            // TEACHER – Sunday School Teacher
            // ─────────────────────────────────────────────
            'teacher' => [
                'hierarchy_level' => 20,
                'description'     => 'Sunday School Teacher',
                'permissions'     => [
                    'dashboard.view',
                    'attendance.view',
                    'attendance.create',
                ],
            ],

            // ─────────────────────────────────────────────
            // STUDENT – Sunday School Student
            // ─────────────────────────────────────────────
            'student' => [
                'hierarchy_level' => 10,
                'description'     => 'Sunday School Student',
                'permissions'     => [
                    'dashboard.view',
                    'attendance.view',
                ],
            ],
        ];

        foreach ($roles as $name => $data) {
            $enName = ucfirst(str_replace('_', ' ', $name));
            $slug   = Str::slug($name);

            // Find by slug first (most reliable), fall back to name match
            $role = Role::withTrashed()->where('slug', $slug)->first()
                ?? Role::withTrashed()->whereRaw("name->>'en' = ?", [$enName])->first();

            $baseData = [
                'slug'            => $slug,          // always enforce correct slug
                'name'            => [
                    'en' => $enName,
                    'am' => $enName,
                ],
                'description'     => [
                    'en' => $data['description'],
                    'am' => $data['description'],
                ],
                'hierarchy_level' => $data['hierarchy_level'],
                'is_system_level' => true,
                'is_active'       => true,
            ];

            $role = Role::withoutEvents(function () use ($role, $baseData) {
                if ($role) {
                    $role->update($baseData);
                    if ($role->trashed()) {
                        $role->restore();
                    }
                    return $role;
                } else {
                    return Role::create($baseData);
                }
            });

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
