<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Services\BackMessage;

use Illuminate\Foundation\Testing\RefreshDatabase;

class FinalEdgeCaseTest extends TestCase {
    use RefreshDatabase, \Tests\Traits\CreatesSuperAdmin;
    protected $seed = true;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();
    }

    /**
     * Test resource wrapping in the final standardized response
     */
    public function test_api_responses_are_properly_wrapped() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        // Test User Wrapping
        $response = $this->getJson("/api/system/users/{$admin->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['id', 'name', 'email']]);

        // Test Role Wrapping
        $role = Role::first();
        $response = $this->getJson("/api/system/roles/{$role->slug}");
        $response->assertStatus(200);
        $response->assertJsonStructure(['role' => ['id', 'name', 'slug']]);
    }

    /**
     * Test soft-deleted user cannot login
     */
    public function test_soft_deleted_user_cannot_login() {
        $email = 'soft_deleted_' . uniqid() . '@lms.com';
        $user = User::forceCreate([
            'name' => ['en' => 'Deleted User'],
            'email' => $email,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $user->delete(); // Soft delete

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        // Logic: User::where('email', $email)->first() in AuthController will not find soft deleted users by default
        $response->assertStatus(401);
        $this->assertStringContainsString(BackMessage::get('invalid_credentials'), $response->json('message'));
    }

    /**
     * Test multi-language name fallback
     */
    public function test_localizable_trait_fallback_logic() {
        $user = User::forceCreate([
            'name' => ['en' => 'English Name'], // No Amharic
            'email' => 'lang_test' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Force Amharic locale
        app()->setLocale('am');

        // __localized should return English because Amharic is missing
        $this->assertEquals('English Name', $user->name__localized);

        // Reset locale
        app()->setLocale('en');
    }

    /**
     * Test hierarchy: Manager cannot modify Admin (strictly higher)
     */
    public function test_manager_cannot_modify_admin() {
        // 1. Setup Roles
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => ['en' => 'Admin'], 'hierarchy_level' => 80]);
        $managerRole = Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 60]);

        // Grant permissions to manager
        $editPerm = Permission::firstOrCreate(['slug' => 'users.edit']);
        $managerRole->permissions()->syncWithoutDetaching([$editPerm->id]);

        // 2. Setup Users
        $adminUser = User::forceCreate([
            'name' => ['en' => 'Admin Boss'],
            'email' => 'boss' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $adminUser->roles()->attach($adminRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);

        $managerUser = User::forceCreate([
            'name' => ['en' => 'Manager X'],
            'email' => 'manager' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $managerUser->roles()->attach($managerRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);

        Passport::actingAs($managerUser);

        // 3. Attempt to modify Admin
        $response = $this->putJson("/api/system/users/{$adminUser->id}", [
            'name' => 'Hacked Name',
            'email' => $adminUser->email
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test department assignment edge case (non-existent department)
     */
    public function test_cannot_assign_non_existent_department() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $response = $this->postJson('/api/register', [
            'name' => 'New Staff',
            'email' => 'staff' . uniqid() . '@lms.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_university_id' => 'STF-' . uniqid(),
            'user_type' => 'staff',
            'role' => 'librarian',
            'department_id' => 999999, // Non-existent
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['department_id']);
    }

    /**
     * Test self-deletion prevention via direct destroy endpoint
     */
    public function test_user_cannot_delete_themselves_directly() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $response = $this->deleteJson("/api/system/users/{$admin->id}");
        $response->assertStatus(403);
    }

    /**
     * Test cancelling an already active role fails
     */
    public function test_cancel_active_role_fails() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $user = User::factory()->create();
        $role = Role::first();

        // Assign an ACTIVE role
        $user->roles()->attach($role->id, [
            'is_active' => true,
            'start_date' => now()->subDay(),
            'assigned_by' => $admin->id
        ]);

        $pivotId = \Illuminate\Support\Facades\DB::table('user_role')->where('user_id', $user->id)->first()->id;

        $response = $this->deleteJson("/api/system/users/{$user->id}/cancel-scheduled-role/{$pivotId}");

        // Should be 404 because controller filters for is_active => false
        $response->assertStatus(404);
    }

    /**
     * Test validation: end_date must be after start_date in update
     */
    public function test_update_scheduled_role_validates_dates() {
        $admin = $this->superAdmin;
        Passport::actingAs($admin);

        $user = User::factory()->create();
        $role = Role::first();

        // Assign a PENDING role
        $user->roles()->attach($role->id, [
            'is_active' => false,
            'start_date' => now()->addDays(5),
            'assigned_by' => $admin->id
        ]);

        $pivotId = \Illuminate\Support\Facades\DB::table('user_role')->where('user_id', $user->id)->first()->id;

        $response = $this->patchJson("/api/system/users/{$user->id}/update-scheduled-role/{$pivotId}", [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(), // Invalid: before start_date
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }
}
