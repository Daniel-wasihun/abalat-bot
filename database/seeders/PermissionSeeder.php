<?php

namespace Database\Seeders;

use App\Constants\Action;
use App\Constants\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder {
    /**
     * Create all possible permissions from Module + Action constants.
     */
    public function run(): void {
        $this->command->info('Creating all permissions from constants...');

        $count = 0;

        foreach (Module::all() as $module) {
            // Default CRUD actions
            $actions = [Action::VIEW, Action::CREATE, Action::EDIT, Action::DELETE];

            // Bot-specific actions
            if ($module === Module::BOT) {
                $actions[] = Action::MANAGE;
                $actions[] = Action::NOTIFY;
                $actions[] = Action::EXPORT;
            }

            // Academic course-management actions
            if ($module === Module::ACADEMIC_COURSES) {
                $actions[] = Action::MANAGE; // manage teachers/assignments
            }

            // Academic class access (teacher gradebook, attendance)
            if ($module === Module::ACADEMIC_CLASSES) {
                $actions[] = Action::MANAGE; // enter marks, take attendance
            }

            foreach ($actions as $action) {
                $permission = Permission::withTrashed()->where('module', $module)->where('action', $action)->first();

                if ($permission) {
                    if ($permission->trashed()) {
                        $permission->restore();
                    }
                    $permission->is_system_level = true;
                    $permission->is_active = true;
                    $permission->name = null;
                    $permission->save();
                } else {
                    Permission::create([
                        'module' => $module,
                        'action' => $action,
                        'is_system_level' => true,
                        'is_active' => true,
                    ]);
                }

                $count++;
            }
        }

        $this->command->info("Successfully created {$count} permissions.");
    }
}
