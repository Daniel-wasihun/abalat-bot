<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration test verifying the complete lifecycle of scheduled permissions:
 * 1. Permission is scheduled with start and end dates
 * 2. Permission activates on the start date
 * 3. Permission terminates on the end date
 */
class PermissionLifecycleIntegrationTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    public function test_complete_permission_lifecycle_from_schedule_to_activation_to_expiration() {
        // Setup: Create super admin and regular user
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole->id, [
            'assigned_by' => null,
            'start_date' => now(),
            'is_active' => true
        ]);

        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->attach($studentRole->id, [
            'assigned_by' => $superAdmin->id,
            'start_date' => now(),
            'is_active' => true
        ]);

        $permission = Permission::where('slug', 'books.create')->first();

        // PHASE 1: Schedule permission for future activation
        $startDate = Carbon::now()->addDays(2);
        $endDate = Carbon::now()->addDays(5);

        $response = $this->actingAs($superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => [$permission->slug => true],
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);

        $response->assertStatus(200);

        // Verify: Permission is scheduled but not active yet
        $user = $user->fresh();
        $this->assertFalse(
            $user->hasPermission($permission->slug),
            "Permission should NOT be active before start date"
        );

        $scheduled = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->whereNull('revoked_at')
            ->first();

        $this->assertNotNull($scheduled, "Scheduled permission record should exist");
        $this->assertFalse((bool)$scheduled->is_active, "Permission should be inactive (scheduled)");
        $this->assertTrue((bool)$scheduled->granted, "Permission should be marked as granted");
        $this->assertEquals($startDate->toDateString(), Carbon::parse($scheduled->start_date)->toDateString());
        $this->assertEquals($endDate->toDateString(), Carbon::parse($scheduled->end_date)->toDateString());

        // PHASE 2: Time travel to start date and activate
        Carbon::setTestNow($startDate->copy()->addMinute());

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Granting '{$permission->slug}' for user {$user->email}")
            ->assertExitCode(0);

        // Verify: Permission is now active
        $user = $user->fresh();
        $this->assertTrue(
            $user->hasPermission($permission->slug),
            "Permission SHOULD be active after start date"
        );

        $active = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();

        $this->assertNotNull($active, "Active permission record should exist");
        $this->assertTrue((bool)$active->granted, "Permission should still be granted");

        // PHASE 3: Time travel to end date and expire
        Carbon::setTestNow($endDate->copy()->addMinute());

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Expiring override for '{$permission->slug}' for user {$user->email}")
            ->assertExitCode(0);

        // Verify: Permission is now expired/terminated
        $user = $user->fresh();
        $this->assertFalse(
            $user->hasPermission($permission->slug),
            "Permission should NOT be active after end date (expired)"
        );

        $expired = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertNotNull($expired, "Permission record should still exist");
        $this->assertFalse((bool)$expired->is_active, "Permission should be inactive (expired)");
        $this->assertNotNull($expired->revoked_at, "Permission should have revoked_at timestamp");

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_multiple_permissions_scheduled_activated_and_expired_together() {
        // Setup
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole->id, [
            'assigned_by' => null,
            'start_date' => now(),
            'is_active' => true
        ]);

        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->attach($studentRole->id, [
            'assigned_by' => $superAdmin->id,
            'start_date' => now(),
            'is_active' => true
        ]);

        // Get 3 permissions
        $permissions = Permission::whereNotIn('slug', ['super-admin'])
            ->take(3)
            ->get();

        $startDate = Carbon::now()->addDays(1);
        $endDate = Carbon::now()->addDays(3);

        // Schedule all 3 permissions at once
        $payload = [];
        foreach ($permissions as $perm) {
            $payload[$perm->slug] = true;
        }

        $response = $this->actingAs($superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => $payload,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]);

        $response->assertStatus(200);

        // Verify all are scheduled
        $user = $user->fresh();
        foreach ($permissions as $perm) {
            $this->assertFalse($user->hasPermission($perm->slug), "Permission {$perm->slug} should not be active yet");
        }

        // Activate all
        Carbon::setTestNow($startDate->copy()->addMinute());
        $this->artisan('permissions:process-scheduled')->assertExitCode(0);

        $user = $user->fresh();
        foreach ($permissions as $perm) {
            $this->assertTrue($user->hasPermission($perm->slug), "Permission {$perm->slug} should be active");
        }

        // Expire all
        Carbon::setTestNow($endDate->copy()->addMinute());
        $this->artisan('permissions:process-scheduled')->assertExitCode(0);

        $user = $user->fresh();
        foreach ($permissions as $perm) {
            $this->assertFalse($user->hasPermission($perm->slug), "Permission {$perm->slug} should be expired");
        }

        Carbon::setTestNow();
    }

    public function test_permission_revocation_scheduled_and_executed() {
        // Setup
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole->id, [
            'assigned_by' => null,
            'start_date' => now(),
            'is_active' => true
        ]);

        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->attach($studentRole->id, [
            'assigned_by' => $superAdmin->id,
            'start_date' => now(),
            'is_active' => true
        ]);

        $permission = Permission::where('slug', 'books.create')->first();

        // Grant the permission immediately
        $response = $this->actingAs($superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => [$permission->slug => true]
            ]);

        $response->assertStatus(200);

        // Verify user has the permission
        $user = $user->fresh();
        $this->assertTrue($user->hasPermission($permission->slug), "User should have the granted permission");

        // Schedule a REVOCATION for tomorrow
        $startDate = Carbon::now()->addDay();

        $response = $this->actingAs($superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => [$permission->slug => false], // false = revoke
                'start_date' => $startDate->toDateString()
            ]);

        $response->assertStatus(200);

        // Check that a scheduled revocation exists
        $scheduledRevoke = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('is_active', false)
            ->where('granted', false)
            ->whereNull('revoked_at')
            ->first();

        $this->assertNotNull($scheduledRevoke, "Scheduled revocation should exist");
        $this->assertEquals($startDate->toDateString(), Carbon::parse($scheduledRevoke->start_date)->toDateString());

        // Activate revocation by time traveling
        Carbon::setTestNow($startDate->copy()->addMinute());

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Revoking '{$permission->slug}'")
            ->assertExitCode(0);

        // Verify permission is now revoked
        $user = $user->fresh();
        $this->assertFalse($user->hasPermission($permission->slug), "User should NOT have permission after revocation");

        // Verify the revocation is now active
        $activeRevoke = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('is_active', true)
            ->where('granted', false)
            ->first();

        $this->assertNotNull($activeRevoke, "Revocation should be active");

        Carbon::setTestNow();
    }

    public function test_scheduler_runs_without_errors_when_no_permissions_to_process() {
        // Just verify the command doesn't crash when there's nothing to do
        $this->artisan('permissions:process-scheduled')
            ->expectsOutput("No permissions to activate")
            ->expectsOutput("No permissions to expire")
            ->assertExitCode(0);
    }
}
