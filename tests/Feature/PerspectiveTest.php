<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Services\BackMessage;
use Carbon\Carbon;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class PerspectiveTest extends TestCase {
    use RefreshDatabase, \Tests\Traits\CreatesSuperAdmin;
    protected $seed = true;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Test Client', '--no-interaction' => true]);
    }

    /**
     * Test Token Extraction from Query String (Unified Middleware Logic)
     */
    public function test_token_extraction_from_query_string() {
        $token = $this->superAdmin->createToken('QueryToken')->accessToken;

        // Try to access /api/me without Authorization header, but with query token
        $response = $this->getJson("/api/me?token={$token}");

        $response->assertStatus(200);
        $response->assertJsonPath('user.email', $this->superAdmin->email);
    }

    /**
     * Test Authorization Precedence: Header vs Query (Localization)
     */
    public function test_localization_precedence_in_middleware() {
        // Set header to 'am' and query to 'en'. Header should usually take priority in my implementation
        // Actually, my implementation was: query ?? header ?? accept-language
        $response = $this->getJson("/api/languages?lang=am", [
            'lang' => 'en'
        ]);

        // If query?lang=am is used, the locale should be 'am'
        $this->assertEquals('am', app()->getLocale());
    }

    /**
     * Test Permission Merging & Precedence: Direct permissions specific overrides
     */
    public function test_permission_merging_and_precedence() {
        // 1. Create Role with permission A
        $roleName = 'Role X ' . uniqid();
        $role = Role::create(['name' => ['en' => $roleName], 'hierarchy_level' => 10]);
        $permA = Permission::firstOrCreate([
            'slug' => \App\Constants\Module::BOOKS . '.' . \App\Constants\Action::VIEW
        ], [
            'name' => ['en' => 'Perm A'],
            'module' => \App\Constants\Module::BOOKS,
            'action' => \App\Constants\Action::VIEW
        ]);
        $role->permissions()->attach($permA->id);

        $user = User::forceCreate([
            'name' => ['en' => 'Test User'],
            'email' => 'hybrid' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]);

        // Initially, user should have Perm A via Role
        $this->assertTrue($user->hasPermission($permA->slug));

        // 2. Give user a "Direct Permission" B (Merging)
        $permB = Permission::firstOrCreate([
            'slug' => \App\Constants\Module::USERS . '.' . \App\Constants\Action::VIEW
        ], [
            'name' => ['en' => 'Perm B'],
            'module' => \App\Constants\Module::USERS,
            'action' => \App\Constants\Action::VIEW
        ]);
        $user->directPermissions()->attach($permB->id, ['granted' => true, 'assigned_by' => $this->superAdmin->id, 'is_active' => true]);

        // Now, user has BOTH Perm A (Role) and Perm B (Direct)
        $this->assertTrue($user->hasPermission($permB->slug));
        $this->assertTrue($user->hasPermission($permA->slug), "Inherited permission should still be active (Merging)");

        // 3. Explicitly Revoke Perm A (Precedence)
        $user->directPermissions()->attach($permA->id, ['granted' => false, 'assigned_by' => $this->superAdmin->id, 'is_active' => true]);

        // Now Perm A should be FALSE because Direct Revoke takes precedence over Role Grant in the current flow
        $this->assertFalse($user->hasPermission($permA->slug), "Direct revoke should take precedence over role grant (Current Flow)");
    }


    /**
     * Test Permission Expiration (Carbon date check)
     */
    public function test_permission_expiration_logic() {
        $user = User::forceCreate([
            'name' => ['en' => 'Expiry User'],
            'email' => 'expiry' . uniqid() . '@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $perm = Permission::firstOrCreate([
            'slug' => \App\Constants\Module::BOOKS . '.' . \App\Constants\Action::VIEW
        ], [
            'name' => ['en' => 'Test Permission'],
            'module' => \App\Constants\Module::BOOKS,
            'action' => \App\Constants\Action::VIEW
        ]);

        // Grant permission that expired yesterday
        $user->directPermissions()->attach($perm->id, [
            'granted' => true,
            'assigned_by' => $this->superAdmin->id,
            'is_active' => true,
            'end_date' => Carbon::now()->subDay()
        ]);

        $this->assertFalse($user->hasPermission($perm->slug), "Expired permission should return false");
    }

    /**
     * Test Handled Exception for non-existent model (404 API Formatting)
     */
    public function test_custom_404_handler_for_api() {
        Passport::actingAs($this->superAdmin);

        // Request a non-existent role slug
        $response = $this->getJson("/api/system/roles/does-not-exist-ever-123");

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => 'The selected administrative role is invalid or does not exist.'
        ]);
    }

    /**
     * Test Super Admin Bypass Logic
     */
    public function test_super_admin_bypass_logic() {
        $superAdmin = $this->superAdmin;

        // 1. Verify Super Admin has random permission it doesn't explicitly have
        $this->assertTrue($superAdmin->hasPermission('some.fake.permission'), "Super admin should have all permissions by default");

        // 2. Create another admin at same level (100) or higher
        $otherAdmin = User::forceCreate([
            'name' => ['en' => 'Peer Admin'],
            'email' => 'peer@lms.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $saRole = Role::where('slug', 'super-admin')->first();
        if (!$saRole) {
            $saRole = Role::create(['slug' => 'super-admin', 'name' => ['en' => 'Super Admin'], 'hierarchy_level' => 100]);
        }
        $otherAdmin->roles()->attach($saRole->id, ['is_active' => true, 'assigned_by' => $this->superAdmin->id]); // Assigned by someone

        // 3. Verify super admin can modify this peer admin despite same level and not being assigner
        $this->assertTrue($superAdmin->canModifyUser($otherAdmin), "Super admin should be able to modify any user including peers");
    }

    /**
     * Test Unauthorized access (401 API Formatting)
     */
    public function test_custom_401_handler_for_api() {
        // No passport actingAs
        $response = $this->getJson("/api/me");

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Session expired. Please sign in to continue.'
        ]);
    }
}
