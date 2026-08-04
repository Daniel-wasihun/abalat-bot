<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Services\BackMessage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EdgeCaseTest extends TestCase {
    use RefreshDatabase, \Tests\Traits\CreatesSuperAdmin;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();

        // Ensure roles used in tests exist
        Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 60]);
        Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);
        Role::firstOrCreate(['slug' => 'student'], ['name' => ['en' => 'Student'], 'hierarchy_level' => 10]);

        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Test Client', '--no-interaction' => true]);
    }

    /**
     * Auth Edge Cases
     */
    public function test_login_fails_if_user_is_inactive() {
        // Create an inactive user
        $user = User::forceCreate([
            'name' => ['en' => 'Inactive User'],
            'email' => 'inactive' . uniqid() . '@lms.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Should be 403 Forbidden
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Account inactive. Please contact system administration.']);
    }

    /**
     * Role Hierarchy Edge Cases
     */
    /**
     * Role Hierarchy Edge Cases
     */
    public function test_user_cannot_delete_role_higher_than_their_level() {
        // 1. Create a "Low Manager" user
        $managerRole = Role::create(['slug' => 'low-manager-' . uniqid(), 'name' => ['en' => 'Low Manager'], 'hierarchy_level' => 20]);

        // Grant permission so they pass middleware and hit controller logic
        $deleteRolePerm = Permission::firstOrCreate(
            ['slug' => 'roles.delete'],
            ['module' => 'roles', 'action' => 'delete', 'name' => ['en' => 'Delete Roles']]
        );
        $managerRole->permissions()->syncWithoutDetaching([$deleteRolePerm->id]);

        $manager = User::forceCreate([
            'name' => ['en' => 'Manager User'],
            'email' => 'manager_edge' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $manager->roles()->attach($managerRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);

        Passport::actingAs($manager);

        // 2. Create an "High Role" role (Level 50)
        $adminRole = Role::create(['slug' => 'high-role-' . uniqid(), 'name' => ['en' => 'High Role'], 'hierarchy_level' => 50]);

        // 3. Attempt to delete Admin Role
        $response = $this->deleteJson("/api/system/roles/{$adminRole->slug}");

        // Should be Forbidden (403) specifically due to hierarchy
        $response->assertStatus(403);
    }

    public function test_user_cannot_assign_role_higher_than_their_level() {
        // Scenario: Low Level User (20) tries to assign High Level Role (50)
        $suffix = uniqid();

        $lowRole = Role::create([
            'name' => ['en' => "Low Level $suffix"],
            'slug' => "low-level-$suffix",
            'hierarchy_level' => 20
        ]);

        // Grant permission so they pass middleware
        $editUserPerm = Permission::firstOrCreate(
            ['slug' => 'users.edit'],
            ['module' => 'users', 'action' => 'edit', 'name' => ['en' => 'Edit Users']]
        );
        $lowRole->permissions()->attach($editUserPerm->id);

        $lowUser = User::forceCreate([
            'name' => ['en' => 'Low User'],
            'email' => "low_assigner$suffix@lms.com",
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $lowUser->roles()->attach($lowRole->id, ['is_active' => true]);

        Passport::actingAs($lowUser);

        $targetUser = User::forceCreate([
            'name' => ['en' => 'Target User'],
            'email' => "target_edge$suffix@lms.com",
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $targetUser->id, 'user_university_id' => "EDGE-$suffix", 'gender' => 'male']);

        $highRole = Role::create([
            'name' => ['en' => "High Level $suffix"],
            'slug' => "high-level-$suffix",
            'hierarchy_level' => 50
        ]);

        $response = $this->postJson("/api/system/users/{$targetUser->id}/assign-role", [
            'role' => $highRole->slug,
            'start_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(403);
    }

    /**
     * System Level Protection
     */
    /**
     * System Level Protection
     */
    public function test_cannot_delete_system_level_role() {
        $superAdmin = $this->superAdmin;
        Passport::actingAs($superAdmin);

        $suffix = uniqid();
        // Create system level role
        $systemRole = Role::forceCreate([
            'name' => ['en' => "System Helper $suffix"],
            'slug' => "system-helper-$suffix",
            'hierarchy_level' => 1,
            'is_system_level' => true
        ]);

        $response = $this->deleteJson("/api/system/roles/{$systemRole->slug}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_cannot_delete_system_level_permission() {
        $superAdmin = $this->superAdmin;
        Passport::actingAs($superAdmin);

        // Cleanup to ensure we can create this specific permission
        Permission::where('slug', 'settings.delete')->forceDelete();
        $suffix = uniqid();

        // Use valid Module/Action. logic forces slug = module.action
        $sysPerm = Permission::forceCreate([
            'name' => ['en' => "Sys Perm $suffix"],
            'module' => 'settings',
            'action' => 'delete', // settings.delete
            'is_system_level' => true
        ]);

        $response = $this->deleteJson("/api/system/permissions/{$sysPerm->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('permissions', ['id' => $sysPerm->id]);
    }

    /**
     * Expanded Hierarchy Tests
     */
    public function test_user_can_modify_user_of_same_level_if_assigned_by_them() {
        // 1. Setup Permissions
        $editPerm = Permission::firstOrCreate(['slug' => 'users.edit'], ['module' => 'users', 'action' => 'edit', 'name' => ['en' => 'Edit Users']]);

        // 2. Create a Manager
        $managerRole = Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 20]);
        $managerRole->permissions()->syncWithoutDetaching([$editPerm->id]);

        $manager = User::forceCreate([
            'name' => ['en' => 'Manager Self'],
            'email' => 'mgr_creator' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $manager->roles()->attach($managerRole->id, ['is_active' => true]); // Manager has permission via role

        Passport::actingAs($manager);

        // 3. Create Sub-Manager assigned by THIS manager
        $subManager = User::forceCreate([
            'name' => ['en' => 'Sub Manager'],
            'email' => 'sub_mgr' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $subManager->id, 'user_university_id' => 'SUB-' . uniqid(), 'gender' => 'male']);

        $subManager->roles()->attach($managerRole->id, [
            'is_active' => true,
            'assigned_by' => $manager->id // Key factor
        ]);

        // 4. Manager tries to update Sub-Manager
        $response = $this->putJson("/api/system/users/{$subManager->id}", [
            'name' => 'Sub Manager Updated',
            'email' => $subManager->email,
            'gender' => 'male',
        ]);

        $response->assertStatus(200);
    }

    public function test_user_cannot_modify_user_of_same_level_if_NOT_assigned_by_them() {
        $editPerm = Permission::firstOrCreate(['slug' => 'users.edit'], ['module' => 'users', 'action' => 'edit', 'name' => ['en' => 'Edit Users']]);

        $managerRole = Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 20]);
        $managerRole->permissions()->syncWithoutDetaching([$editPerm->id]);

        $admin = $this->superAdmin; // Assigner for others

        // Manager A
        $managerA = User::forceCreate([
            'name' => ['en' => 'Manager A'],
            'email' => 'mgr_a' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $managerA->roles()->attach($managerRole->id, ['is_active' => true, 'assigned_by' => $admin ? $admin->id : 1]); // Fallback 1 if admin null

        // Manager B (Peer, not created by A)
        $managerB = User::forceCreate([
            'name' => ['en' => 'Manager B'],
            'email' => 'mgr_b' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $managerB->roles()->attach($managerRole->id, ['is_active' => true, 'assigned_by' => $admin ? $admin->id : 1]); // Assigned by admin

        Passport::actingAs($managerA);

        // Attempt update
        $response = $this->putJson("/api/system/users/{$managerB->id}", [
            'name' => 'Manager B Hacked',
            'email' => $managerB->email
        ]);

        // Should be forbidden
        $response->assertStatus(403);
    }

    public function test_assigning_non_existent_role_returns_422() {
        $superAdmin = $this->superAdmin;
        Passport::actingAs($superAdmin);

        $targetUser = User::forceCreate([
            'name' => ['en' => 'Target'],
            'email' => 'target' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $targetUser->id, 'user_university_id' => 'TG-' . uniqid(), 'gender' => 'male']);

        $response = $this->postJson("/api/system/users/{$targetUser->id}/assign-role", [
            'role' => 'ghost-role-xyz',
            'start_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(422); // Validation error
    }

    public function test_cannot_register_with_email_of_soft_deleted_user() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $email = 'soft_del_' . uniqid() . '@lms.com';

        $user = User::forceCreate([
            'name' => ['en' => 'To Be Deleted'],
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $user->delete();

        // Attempt to register same email
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // 'role' => 'manager', // Optional in validation rules unless user_type is staff?
            // Let's check logic: UserRequest: if($isRegister) rules['role'] required_if:user_type,staff
            'user_university_id' => 'SD-' . uniqid(),
            'gender' => 'male',
        ]);

        // Expect 422 Validation Error "Email already taken"
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_deactivate_themselves() {
        $admin = User::forceCreate([
            'name' => ['en' => 'Self Destruct'],
            'email' => 'kamikaze' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        // Give admin role just in case logic depends on it, though check is usually identity based
        $adminRole = Role::where('slug', 'super-admin')->first() ?? Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);
        }

        Passport::actingAs($admin);

        $response = $this->putJson("/api/system/users/{$admin->id}", [
            'is_active' => false,
            'email' => $admin->email,
            'gender' => 'male',
        ]);

        // Should be 403 Forbidden to prevent lockout
        $response->assertStatus(403);
    }

    public function test_granting_duplicate_permission_is_idempotent() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $user = User::forceCreate([
            'name' => ['en' => 'Perm User'],
            'email' => 'perm_dup' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $user->id, 'user_university_id' => 'PD-' . uniqid(), 'gender' => 'male']);

        $perm = Permission::first();

        // First Grant
        $this->postJson("/api/system/users/{$user->id}/grant-permission", ['permission' => $perm->slug])
            ->assertStatus(200);

        // Second Grant (Duplicate)
        $response = $this->postJson("/api/system/users/{$user->id}/grant-permission", ['permission' => $perm->slug]);

        $response->assertStatus(200);
        // Ensure count is still 1 (pivot unique constraint usually handled by syncWithoutDetaching)
        $this->assertCount(1, $user->directPermissions()->where('slug', $perm->slug)->get());
    }

    public function test_user_cannot_update_their_own_profile() {
        $user = User::forceCreate([
            'name' => ['en' => 'Profile User'],
            'email' => 'prof_' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $user->id, 'user_university_id' => 'PROF-' . uniqid(), 'gender' => 'male']);

        Passport::actingAs($user);

        // Attempt to update only name
        $response = $this->putJson("/api/system/users/{$user->id}", [
            'name' => 'Profile Updated',
            'email' => $user->email,
            'gender' => 'male',
        ]);

        // Should be 403 Forbidden as per "user cannot modify himself" requirement
        $response->assertStatus(403);
    }

    /**
     * Additional Validation & Logic Edge Cases
     */
    public function test_assignment_fails_if_end_date_before_start_date() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $role = Role::first();
        $user = User::factory()->create(); // Or forceCreate if factory not reliable
        if (!$user->info) \App\Models\UserInfo::create(['user_id' => $user->id, 'user_university_id' => 'DT-' . uniqid(), 'gender' => 'male']);

        $response = $this->postJson("/api/system/users/{$user->id}/assign-role", [
            'role' => $role->slug,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'), // Earlier
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }

    public function test_role_creation_fails_if_name_is_duplicate() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $roleName = "Dup " . rand(100, 999);
        $perm = Permission::first(); // Need at least one

        // First Create
        $this->postJson('/api/system/roles', [
            'name' => $roleName,
            'hierarchy_level' => 10,
            'permissions' => [$perm->slug]
        ])->assertStatus(200);

        // Second Create (Duplicate)
        $response = $this->postJson('/api/system/roles', [
            'name' => $roleName,
            'hierarchy_level' => 10,
            'permissions' => [$perm->slug]
        ]);

        $response->assertStatus(422);
    }

    public function test_role_creation_fails_if_level_is_higher_than_self() {
        // 1. Create a unique Manager role to ensure hierarchy isolation
        $suffix = uniqid();
        $managerRole = Role::create([
            'name' => ['en' => "Mgr " . $suffix],
            'slug' => "mgr-$suffix",
            'hierarchy_level' => 20
        ]);

        $roleCreatePerm = Permission::firstOrCreate(['slug' => 'roles.create'], ['module' => 'roles', 'action' => 'create', 'name' => ['en' => 'Create Roles']]);
        $managerRole->permissions()->syncWithoutDetaching([$roleCreatePerm->id]);

        $manager = User::forceCreate([
            'name' => ['en' => 'Low Level Creator'],
            'email' => "creator_$suffix@lms.com",
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $manager->roles()->attach($managerRole->id, ['is_active' => true]);

        Passport::actingAs($manager);

        // 2. Try to create Role Level 30 (Higher than self level 20)
        $response = $this->postJson('/api/system/roles', [
            'name' => "Over " . $suffix,
            'hierarchy_level' => 30,
            'permissions' => [$roleCreatePerm->slug]
        ]);

        // Should be forbidden by RoleRequest authorize() check
        $response->assertStatus(403);
    }

    public function test_active_user_becomes_inactive_denied_access() {
        // 1. Create and login user
        $user = User::forceCreate([
            'name' => ['en' => 'Active Now'],
            'email' => 'active_now' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $token = $user->createToken('TestToken')->accessToken;

        // 2. Deactivate user
        $user->update(['is_active' => false]);

        // 3. Attempt request with valid token but inactive account
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/me');

        // Should be 403 Forbidden due to ApiAuthenticate middleware check
        $response->assertStatus(403);
        $response->assertJson(['message' => BackMessage::get('inactive_account')]);
    }

    public function test_admin_cannot_register_user_with_higher_role() {
        // 1. Manager (Level 20)
        $manager = User::forceCreate([
            'name' => ['en' => 'Manager X'],
            'email' => 'manager_x' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        // Give manager role directly
        $managerRole = Role::where('slug', 'manager')->first();
        $manager->roles()->attach($managerRole->id, ['is_active' => true]);

        Passport::actingAs($manager);

        // 2. Try to register a Super Admin (Level 100)
        $response = $this->postJson('/api/register', [
            'name' => 'Fake Admin',
            'email' => 'fake_admin' . uniqid() . '@lms.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'user_university_id' => 'ID-' . uniqid(),
            'role' => 'super-admin',
            'gender' => 'male',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_themselves() {
        $user = User::forceCreate([
            'name' => ['en' => 'Suicidal User'],
            'email' => 'suicide' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/system/users/{$user->id}");
        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_higher_level_user() {
        // 1. Librarian (Level 10)
        $librarian = User::forceCreate([
            'name' => ['en' => 'Librarian'],
            'email' => 'lib_' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $libRole = Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);
        $librarian->roles()->attach($libRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);

        // 2. Admin (Level 60)
        $admin = User::forceCreate([
            'name' => ['en' => 'Admin Boss'],
            'email' => 'boss_' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => ['en' => 'Admin'], 'hierarchy_level' => 80]);
        $admin->roles()->attach($adminRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);

        Passport::actingAs($librarian);

        $response = $this->deleteJson("/api/system/users/{$admin->id}");
        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_register_others() {
        // 1. A user without 'users.create' permission
        $student = User::forceCreate([
            'name' => ['en' => 'Just a Student'],
            'email' => 'student_' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        // No roles, no permissions

        Passport::actingAs($student);

        // 2. Try to register someone
        $response = $this->postJson('/api/register', [
            'name' => 'New Guy',
            'email' => 'newguy' . uniqid() . '@lms.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'user_university_id' => 'ID-' . uniqid(),
            'gender' => 'male',
        ]);

        // Should be 403 because CheckPermissionMiddleware (via constructor middleware) blocks it
        $response->assertStatus(403);
    }
}
