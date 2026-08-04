<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class PermissionSchedulerTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->seed([\Database\Seeders\PermissionSeeder::class, \Database\Seeders\RoleSeeder::class]);
    }

    /**
     * Test that a scheduled permission grant activates on the start date
     */
    public function test_scheduled_permission_grant_activates_on_start_date() {
        $user = User::factory()->create();
        $permission = Permission::where('slug', 'books.create')->first();

        // Schedule a grant for "today"
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'granted' => true,
            'is_active' => false,
            'start_date' => Carbon::now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Granting 'books.create' for user {$user->email}")
            ->assertExitCode(0);

        $record = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertTrue((bool)$record->is_active);
        $this->assertTrue((bool)$record->granted);
    }

    /**
     * Test that scheduled permission revoke activates on the start date
     */
    public function test_scheduled_permission_revoke_activates_on_start_date() {
        $user = User::factory()->create();
        $permission = Permission::where('slug', 'books.create')->first();

        // Schedule a revoke (granted = false) for "today"
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'granted' => false,
            'is_active' => false,
            'start_date' => Carbon::now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Revoking 'books.create' for user {$user->email}")
            ->assertExitCode(0);

        $record = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertTrue((bool)$record->is_active);
        $this->assertFalse((bool)$record->granted);
    }

    /**
     * Test that a permission override expires on the end date
     */
    public function test_permission_expires_on_end_date() {
        $user = User::factory()->create();
        $permission = Permission::where('slug', 'books.create')->first();

        // Active grant that should have expired yesterday
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'granted' => true,
            'is_active' => true,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Expiring override for 'books.create' for user {$user->email}")
            ->assertExitCode(0);

        $record = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertFalse((bool)$record->is_active);
        $this->assertNotNull($record->revoked_at);
    }

    /**
     * Test that a future grant does not affect current permissions
     */
    public function test_permission_does_not_activate_before_start_date() {
        $user = User::factory()->create();
        $permission = Permission::where('slug', 'books.create')->first();

        // Grant scheduled for tomorrow
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'granted' => true,
            'is_active' => false,
            'start_date' => Carbon::now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('permissions:process-scheduled')
            ->expectsOutput("No permissions to activate")
            ->assertExitCode(0);

        $record = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertFalse((bool)$record->is_active);
    }

    /**
     * Test latest assignment precedes others for the same permission
     */
    public function test_latest_assignment_takes_precedence() {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->first(), [
            'start_date' => now(),
            'is_active' => true
        ]);
        $user = User::factory()->create();
        $permission = Permission::where('slug', 'books.create')->first();

        $this->actingAs($admin, 'api');

        // 1. Grant permission now (Permanent)
        $response = $this->postJson("/api/system/users/{$user->id}/sync-permissions", [
            'permissions' => ['books.create' => true]
        ]);

        $response->assertSuccessful();


        $this->assertTrue(DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('is_active', true)
            ->exists());



        // 2. Later, schedule a Revocation for next week
        $start = Carbon::now()->addWeek()->toDateString();
        $this->postJson("/api/system/users/{$user->id}/sync-permissions", [
            'permissions' => ['books.create' => false],
            'start_date' => $start
        ]);

        // Should have 2 records: one active (grant), one pending (revoke)
        $this->assertEquals(2, DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->count());

        $pending = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('is_active', false)
            ->whereNull('revoked_at') // Pick the non-revoked one
            ->first();


        $this->assertFalse((bool)$pending->granted);
        $this->assertEquals($start, Carbon::parse($pending->start_date)->toDateString());

        // Now move time to "next week" and process
        Carbon::setTestNow(Carbon::now()->addWeek()->addMinute());

        $this->artisan('permissions:process-scheduled')
            ->expectsOutputToContain("Revoking 'books.create'")
            ->assertExitCode(0);

        // The old active grant should be deactivated (revoked_at set)
        // because the new one was processed.
        $oldRecord = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('id', '!=', $pending->id)
            ->first();

        $this->assertFalse((bool)$oldRecord->is_active);
        $this->assertNotNull($oldRecord->revoked_at);

        // The new revocation should be active
        $newRecord = DB::table('user_permission')->find($pending->id);
        $this->assertTrue((bool)$newRecord->is_active);

        Carbon::setTestNow(); // Reset time
    }

    /**
     * Test dry run functionality
     */
    public function test_dry_run_does_not_make_changes() {
        $user = User::factory()->create();
        $permission = Permission::where('slug', 'books.create')->first();

        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'granted' => true,
            'is_active' => false,
            'start_date' => Carbon::now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('permissions:process-scheduled --dry-run')
            ->expectsOutputToContain("DRY RUN MODE")
            ->assertExitCode(0);

        $record = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        $this->assertFalse((bool)$record->is_active);
    }
}
