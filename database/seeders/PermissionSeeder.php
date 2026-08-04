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
            $actions = [Action::VIEW, Action::CREATE, Action::EDIT, Action::DELETE];

            if ($module === Module::BOT) {
                $actions[] = Action::MANAGE;
                $actions[] = Action::NOTIFY;
                $actions[] = Action::EXPORT;
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
