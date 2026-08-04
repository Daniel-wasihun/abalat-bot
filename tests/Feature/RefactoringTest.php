<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\UserInfo;

class RefactoringTest extends TestCase {
    use RefreshDatabase, \Tests\Traits\CreatesSuperAdmin;
    protected $seed = true;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();

        // Ensure roles used in tests exist
        Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 60]);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => ['en' => 'Staff'], 'hierarchy_level' => 1]);

        // Ensure department exists
        $school = \App\Models\School::factory()->create();
        \App\Models\Department::firstOrCreate(['slug' => 'test-department'], [
            'name' => ['en' => 'Test Department'],
            'school_id' => $school->id,
            'short_code' => 'TD',
            'total_year' => 4,
        ]);

        // Create Personal Access Client for Passport
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Test Client', '--no-interaction' => true]);
    }

    /**
     * Test Auth Flow (Login/Logout) using AuthRequest
     */
    public function test_login_works() {
        $response = $this->postJson('/api/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        // Assert access_token exists. Structure might vary (data.user vs root user)
        $response->assertJsonStructure(['access_token']);
    }

    public function test_login_fails_with_invalid_credentials() {
        $response = $this->postJson('/api/login', [
            'email' => $this->superAdmin->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_works() {
        $user = $this->superAdmin;
        Passport::actingAs($user);

        $response = $this->postJson('/api/logout', ['token' => 'dummy']);

        $response->assertStatus(200);
    }

    /**
     * Test Roles (RoleRequest)
     */
    /**
     * Test Roles (RoleRequest)
     */
    public function test_create_role() {
        $user = $this->superAdmin;
        Passport::actingAs($user);

        $suffix = rand(1000, 9999);
        $response = $this->postJson('/api/system/roles', [
            'name' => "Role $suffix",
            'description' => 'Test Description',
            'hierarchy_level' => 10,
            'permissions' => ['books.view', 'books.create']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['slug' => "role-$suffix"]);
    }

    public function test_update_role() {
        $user = $this->superAdmin;
        Passport::actingAs($user);

        // Create a role to update
        $suffix1 = rand(1000, 9999);
        $role = Role::create([
            'name' => ['en' => "Old $suffix1"],
            'slug' => "old-$suffix1",
            'hierarchy_level' => 10
        ]);

        $suffix = rand(1000, 9999);
        $response = $this->putJson("/api/system/roles/{$role->slug}", [
            'name' => 'Upd ' . $suffix,
            'description' => 'Updated Description',
            'hierarchy_level' => 45
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['slug' => 'upd-' . $suffix]);
    }

    /**
     * Test Permissions (PermissionRequest)
     */
    public function test_create_permission() {
        $user = $this->superAdmin;
        Passport::actingAs($user);

        // Cleanup potential collisions (since module+action = slug)
        Permission::where('slug', 'reports.view')->forceDelete();
        Permission::where('slug', 'settings.edit')->forceDelete();

        // Uses valid Module and Action constants
        $response = $this->postJson('/api/system/permissions', [
            'module' => 'reports',
            'action' => 'view',
            'name' => "View Reports", // Unique suffix not needed if we expect specific slug
            'description' => 'Can view reports'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('permissions', ['slug' => 'reports.view']);
    }

    /**
     * Test User Registration/Update (UserRequest)
     */
    public function test_register_user() {
        $user = $this->superAdmin;
        Passport::actingAs($user);

        // Ensure role exists
        $role = Role::firstOrCreate(['slug' => 'staff'], ['name' => ['en' => 'Staff'], 'hierarchy_level' => 1]);
        $dept = \App\Models\Department::where('slug', 'test-department')->first();

        $suffix = rand(1000, 9999);
        $response = $this->postJson('/api/register', [
            'name' => 'New User Registration',
            'email' => "newuser$suffix@lms.com",
            'password' => 'Pass@123!',
            'password_confirmation' => 'Pass@123!',
            'role' => 'staff',
            'user_type' => 'staff',
            'user_university_id' => "LMS" . rand(100000, 999999),
            'gender' => 'male',
            'department_id' => $dept->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => "newuser$suffix@lms.com"]);
    }

    public function test_update_user() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        // Find target user. Might be librarian1 or modified.
        // Let's create a fresh user to update, to avoid dependency on seeder user state.
        $targetUser = User::create([
            'name' => ['en' => 'Temp User'],
            'email' => 'tempuser' . time() . '@lms.com',
            'password' => 'password',
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $targetUser->id, 'user_university_id' => 'TEMP-' . time(), 'user_type' => 'staff', 'gender' => 'male']);

        $newEmail = 'tempuser_updated' . time() . '@lms.com';
        $response = $this->putJson("/api/system/users/{$targetUser->id}", [
            'name' => 'Temp User Updated',
            'email' => $newEmail,
            'gender' => 'male',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => $newEmail]);
    }

    /**
     * Test Assignments (AssignmentRequest)
     */
    public function test_assign_role() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        // Create fresh user
        $targetUser = User::create([
            'name' => ['en' => 'Assign Role User'],
            'email' => 'assignrole' . time() . '@lms.com',
            'password' => 'password',
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $targetUser->id, 'user_university_id' => 'AR-' . time(), 'user_type' => 'staff', 'gender' => 'male']);

        $newRole = Role::where('slug', 'manager')->first();

        // Ensure user has permissions/role to modify (admin > manager? No, admin < super_admin).
        // Admin hierarchy: 2. Manager: 3? 
        // Wait, RoleSeeder hierarchy: super-admin(100), admin(50), manager(20), librarian(10).
        // Can admin(50) assign manager(20)? Yes.

        $response = $this->postJson("/api/system/users/{$targetUser->id}/assign-role", [
            'role' => 'manager',
            'start_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        // Verify pivot
        $this->assertTrue($targetUser->roles()->where('slug', 'manager')->exists());
    }

    public function test_grant_permission() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $targetUser = User::create([
            'name' => ['en' => 'Grant Perm User'],
            'email' => 'grantperm' . time() . '@lms.com',
            'password' => 'password',
            'is_active' => true
        ]);
        \App\Models\UserInfo::create(['user_id' => $targetUser->id, 'user_university_id' => 'GP-' . time(), 'user_type' => 'staff', 'gender' => 'male']);

        $permission = Permission::first();

        $response = $this->postJson("/api/system/users/{$targetUser->id}/grant-permission", [
            'permission' => $permission->slug,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($targetUser->directPermissions()->where('slug', $permission->slug)->exists());
    }

    public function test_update_profile_works() {
        $user = User::factory()->create();
        // Factory creates UserInfo via afterCreating
        Passport::actingAs($user);

        $dob = '1990-01-01';
        $response = $this->postJson('/api/profile', [
            '_method' => 'PUT',
            'name' => 'Updated Name',
            'date_of_birth' => $dob,
            'address' => '123 Main St',
            'gender' => 'male',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_info', [
            'user_id' => $user->id,
            'date_of_birth' => $dob,
            'address' => '123 Main St'
        ]);

        // Refetch user to check name
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name__localized);
    }

    public function test_update_profile_creates_info_with_uid_if_missing() {
        $user = User::factory()->create();
        $user->info()->forceDelete(); // Ensure no info

        Passport::actingAs($user);

        $dob = '2000-01-01';
        $response = $this->postJson('/api/profile', [
            '_method' => 'PUT',
            'name' => 'Profile User',
            'date_of_birth' => $dob,
            'address' => '456 Another St',
            'gender' => 'male',
        ]);

        $response->assertStatus(200);

        // Since user cannot set UID, it should trigger the fallback
        $this->assertDatabaseHas('user_info', [
            'user_id' => $user->id,
            'date_of_birth' => $dob,
            'address' => '456 Another St'
        ]);

        $this->assertStringStartsWith('EXT-' . $user->id, $user->fresh()->info->user_university_id);
    }
}
