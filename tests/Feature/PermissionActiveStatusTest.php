<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests that permission and role temporal / active-status enforcement works correctly.
 *
 * Uses firstOrCreate / updateOrCreate so these tests are safe to run alongside seeded tests
 * (RoleSchedulerTest uses $seed=true which pre-populates roles/permissions).
 */
class PermissionActiveStatusTest extends TestCase {
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void {
        parent::setUp();

        // Idempotent — safe alongside seeded suites
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name'            => ['en' => 'Super Admin', 'am' => 'ከፍተኛ አስተዳዳሪ'],
                'description'     => ['en' => 'Super Admin', 'am' => 'ከፍተኛ አስተዳዳሪ'],
                'hierarchy_level' => 100,
                'is_system_level' => true,
                'is_active'       => true,
            ]
        );

        // Ensure it's active (a seeder might have set it differently)
        $superAdminRole->update(['is_active' => true]);

        $this->superAdmin = User::factory()->create([
            'name'  => 'Super Admin',
            'email' => 'super-' . uniqid() . '@admin.com',
        ]);

        $this->superAdmin->roles()->attach($superAdminRole->id, [
            'assigned_by' => $this->superAdmin->id,
            'is_active'   => true,
            'start_date'  => now()->subMinute(),
        ]);
    }

    // -------------------------------------------------------------------------

    public function test_deactivated_permission_blocks_access(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.edit'],
            ['module' => 'books', 'action' => 'edit', 'name' => ['en' => 'Edit Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'librarian-test'],
            ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['assigned_by' => $this->superAdmin->id, 'is_active' => true, 'start_date' => now()->subMinute()]);

        $this->assertTrue($user->hasPermission('books.edit'));

        // Deactivate the permission globally
        $permission->update(['is_active' => false]);

        $user = $user->fresh(['roles.permissions', 'directPermissions']);

        $this->assertFalse($user->hasPermission('books.edit'));
    }

    public function test_deactivated_role_blocks_inherited_permissions_but_not_direct(): void {
        $editPerm = Permission::firstOrCreate(
            ['slug' => 'books.edit'],
            ['module' => 'books', 'action' => 'edit', 'name' => ['en' => 'Edit Books'], 'is_active' => true]
        );
        $editPerm->update(['is_active' => true]);

        $deletePerm = Permission::firstOrCreate(
            ['slug' => 'books.delete'],
            ['module' => 'books', 'action' => 'delete', 'name' => ['en' => 'Delete Books'], 'is_active' => true]
        );
        $deletePerm->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'librarian-test'],
            ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$editPerm->id, $deletePerm->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['assigned_by' => $this->superAdmin->id, 'is_active' => true, 'start_date' => now()->subMinute()]);
        $user->directPermissions()->attach($deletePerm->id, [
            'granted'     => true,
            'assigned_by' => $this->superAdmin->id,
            'is_active'   => true,
            'start_date'  => now()->subMinute(),
        ]);

        $this->assertTrue($user->hasPermission('books.edit'));
        $this->assertTrue($user->hasPermission('books.delete'));

        $role->update(['is_active' => false]);
        $user = $user->fresh(['roles.permissions', 'directPermissions']);

        $this->assertFalse($user->hasPermission('books.edit'));
        $this->assertTrue($user->hasPermission('books.delete'));
    }

    public function test_active_role_with_deactivated_permission(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.view'],
            ['module' => 'books', 'action' => 'view', 'name' => ['en' => 'View Books'], 'is_active' => false]
        );
        $permission->update(['is_active' => false]);

        $role = Role::firstOrCreate(
            ['slug' => 'reader-test'],
            ['name' => ['en' => 'Reader'], 'hierarchy_level' => 5, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['assigned_by' => $this->superAdmin->id, 'is_active' => true, 'start_date' => now()->subMinute()]);

        $this->assertFalse($user->hasPermission('books.view'));
    }

    public function test_multiple_roles_one_deactivated(): void {
        $viewPerm = Permission::firstOrCreate(
            ['slug' => 'books.view'],
            ['module' => 'books', 'action' => 'view', 'name' => ['en' => 'View Books'], 'is_active' => true]
        );
        $viewPerm->update(['is_active' => true]);

        $editPerm = Permission::firstOrCreate(
            ['slug' => 'books.edit'],
            ['module' => 'books', 'action' => 'edit', 'name' => ['en' => 'Edit Books'], 'is_active' => true]
        );
        $editPerm->update(['is_active' => true]);

        $readerRole = Role::firstOrCreate(
            ['slug' => 'reader-test'],
            ['name' => ['en' => 'Reader'], 'hierarchy_level' => 5, 'is_active' => true]
        );
        $readerRole->update(['is_active' => true]);
        $readerRole->permissions()->syncWithoutDetaching([$viewPerm->id]);

        $librarianRole = Role::firstOrCreate(
            ['slug' => 'librarian-test'],
            ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40, 'is_active' => false]
        );
        $librarianRole->update(['is_active' => false]);
        $librarianRole->permissions()->syncWithoutDetaching([$editPerm->id]);

        $user = User::factory()->create();
        $user->roles()->attach([
            $readerRole->id   => ['assigned_by' => $this->superAdmin->id, 'is_active' => true, 'start_date' => now()->subMinute()],
            $librarianRole->id => ['assigned_by' => $this->superAdmin->id, 'is_active' => true, 'start_date' => now()->subMinute()],
        ]);

        $this->assertTrue($user->hasPermission('books.view'));
        $this->assertFalse($user->hasPermission('books.edit'));
    }

    public function test_direct_revocation_overrides_role_grant(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.delete'],
            ['module' => 'books', 'action' => 'delete', 'name' => ['en' => 'Delete Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'librarian-test'],
            ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, ['assigned_by' => $this->superAdmin->id, 'is_active' => true, 'start_date' => now()->subMinute()]);
        $user->directPermissions()->attach($permission->id, [
            'granted'     => false,
            'assigned_by' => $this->superAdmin->id,
            'is_active'   => true,
            'start_date'  => now()->subMinute(),
        ]);

        $user = $user->fresh(['roles.permissions', 'directPermissions']);

        $this->assertFalse($user->hasPermission('books.delete'));
    }

    public function test_expired_role_assignment_blocks_permission(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.edit'],
            ['module' => 'books', 'action' => 'edit', 'name' => ['en' => 'Edit Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'librarian-test'],
            ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, [
            'assigned_by' => $this->superAdmin->id,
            'is_active'   => true,
            'start_date'  => now()->subDays(10),
            'end_date'    => now()->subDay(), // Expired yesterday
        ]);

        $this->assertFalse($user->hasPermission('books.edit'));
    }

    public function test_future_role_assignment_not_yet_active(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.edit'],
            ['module' => 'books', 'action' => 'edit', 'name' => ['en' => 'Edit Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'librarian-test'],
            ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, [
            'assigned_by' => $this->superAdmin->id,
            'is_active'   => true,
            'start_date'  => now()->addDay(), // Starts tomorrow
        ]);

        $this->assertFalse($user->hasPermission('books.edit'));
    }

    public function test_deactivated_role_pivot_blocks_permission_even_if_model_active(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.edit'],
            ['module' => 'books', 'action' => 'edit', 'name' => ['en' => 'Edit Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'pivot-test-role'],
            ['name' => ['en' => 'Pivot Test Role'], 'hierarchy_level' => 5, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, [
            'assigned_by' => $this->superAdmin->id,
            'is_active'   => false, // Pivot deactivated
        ]);

        $this->assertFalse($user->hasPermission('books.edit'));
    }

    public function test_super_admin_bypasses_everything_including_deactivated_permissions(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'users.delete'],
            ['module' => 'users', 'action' => 'delete', 'name' => ['en' => 'Delete Users'], 'is_active' => false]
        );
        $permission->update(['is_active' => false]);

        // Super Admin should still have access because isSuperAdmin() returns true immediately
        $this->assertTrue($this->superAdmin->fresh(['roles', 'directPermissions'])->hasPermission('users.delete'));
    }

    public function test_expired_direct_override_not_applied(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.view'],
            ['module' => 'books', 'action' => 'view', 'name' => ['en' => 'View Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $user = User::factory()->create();
        $user->directPermissions()->attach($permission->id, [
            'granted'     => true,
            'is_active'   => true,
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->subHour(),
            'end_date'    => now()->subMinute(), // Already expired
        ]);

        $this->assertFalse($user->hasPermission('books.view'));
    }

    public function test_future_direct_override_not_yet_applied(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'books.view'],
            ['module' => 'books', 'action' => 'view', 'name' => ['en' => 'View Books'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $user = User::factory()->create();
        $user->directPermissions()->attach($permission->id, [
            'granted'     => true,
            'is_active'   => true,
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->addHour(), // Starts in an hour
        ]);

        $this->assertFalse($user->hasPermission('books.view'));
    }

    public function test_direct_override_revocation_takes_priority_over_role_grant(): void {
        $permission = Permission::firstOrCreate(
            ['slug' => 'users.create'],
            ['module' => 'users', 'action' => 'create', 'name' => ['en' => 'Create Users'], 'is_active' => true]
        );
        $permission->update(['is_active' => true]);

        $role = Role::firstOrCreate(
            ['slug' => 'direct-override-test-role'],
            ['name' => ['en' => 'Test Role'], 'hierarchy_level' => 5, 'is_active' => true]
        );
        $role->update(['is_active' => true]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id, [
            'is_active'   => true,
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->subMinute(),
        ]);

        // Direct REVOKE — takes priority over the role grant
        $user->directPermissions()->attach($permission->id, [
            'granted'     => false,
            'is_active'   => true,
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->subMinute(),
        ]);

        $this->assertFalse($user->hasPermission('users.create'));
    }
}
