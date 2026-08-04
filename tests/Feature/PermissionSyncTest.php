<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Constants\Module;
use App\Constants\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionSyncTest extends TestCase {
    use RefreshDatabase;
    protected $seed = true;
    protected $superAdmin;

    protected function setUp(): void {
        parent::setUp();

        // Create super admin role
        $adminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => ['en' => 'Super Admin'], 'hierarchy_level' => 100]
        );

        // Create super admin user
        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@lms.com'],
            [
                'password' => Hash::make('password123'),
                'name' => ['en' => 'Super Admin'],
                'is_active' => true
            ]
        );

        // Assign role if not already assigned
        if (!$this->superAdmin->roles()->where('role_id', $adminRole->id)->exists()) {
            $this->superAdmin->roles()->attach($adminRole->id, ['is_active' => true, 'start_date' => now()]);
        }
    }

    /**
     * Test: Targeted sync only creates records for changed permissions.
     */
    public function test_targeted_sync_only_creates_changed_records() {
        Passport::actingAs($this->superAdmin);

        $p_role = Permission::firstOrCreate(['module' => Module::BOOKS, 'action' => Action::VIEW], ['name' => ['en' => 'View Books']]);
        $p_target = Permission::firstOrCreate(['module' => Module::BOOKS, 'action' => Action::CREATE], ['name' => ['en' => 'Create Books']]);

        $role = Role::create(['name' => ['en' => 'Test Role'], 'slug' => 'test-role', 'hierarchy_level' => 10]);
        $role->permissions()->attach($p_role->id);

        $user = User::forceCreate([
            'email' => 'hybrid_test@lms.com',
            'password' => Hash::make('password'),
            'name' => ['en' => 'Hybrid User'],
            'is_active' => true
        ]);
        $user->roles()->attach($role->id, ['is_active' => true]);

        // 1. Initial State: Inherits p_role. No records in user_permission.
        $this->assertFalse(DB::table('user_permission')->where('user_id', $user->id)->exists());
        $this->assertTrue($user->hasPermission($p_role->slug));

        // 2. Targeted Sync: Grant p_target WITH a schedule
        $this->postJson("/api/system/users/{$user->id}/sync-permissions", [
            'permissions' => [
                $p_role->slug => true, // Already has it via role
                $p_target->slug => true // This is the change
            ],
            'start_date' => Carbon::now()->addDay()->toDateString()
        ])->assertStatus(200);

        // 3. Verify: ONLY p_target has a record. p_role (inherited) DOES NOT.
        $records = DB::table('user_permission')->where('user_id', $user->id)->get();
        $this->assertEquals(1, $records->count(), 'Should ONLY have 1 record for the status change, ignoring the bundle schedule for the inherited one');

        $this->assertTrue($records->where('permission_id', $p_target->id)->isNotEmpty());
        $this->assertTrue($records->where('permission_id', $p_role->id)->isEmpty(), 'Inherited permissions should not get records');
    }

    /**
     * Test: Scheduling only applies to changed permissions.
     */
    public function test_scheduling_only_applies_to_changed_permissions() {
        Passport::actingAs($this->superAdmin);

        $p_active = Permission::firstOrCreate(['module' => Module::USERS, 'action' => Action::VIEW], ['name' => ['en' => 'View Users']]);
        $p_scheduled = Permission::firstOrCreate(['module' => Module::USERS, 'action' => Action::CREATE], ['name' => ['en' => 'Create Users']]);

        $user = User::forceCreate([
            'email' => 'schedule_diff@lms.com',
            'password' => Hash::make('password'),
            'name' => ['en' => 'Diff User'],
            'is_active' => true
        ]);

        // 1. Grant p_active immediately (permanent)
        $this->postJson("/api/system/users/{$user->id}/grant-permission", ['permission' => $p_active->slug])
            ->assertStatus(200);

        // 2. Schedule p_scheduled for next week
        $start = Carbon::now()->addWeek()->toDateString();
        $this->postJson("/api/system/users/{$user->id}/sync-permissions", [
            'permissions' => [
                $p_active->slug => true, // Sent as part of the "Current state"
                $p_scheduled->slug => true // This is the change
            ],
            'start_date' => $start
        ])->assertStatus(200);

        // 3. Verify: p_active remains immediate (no record change), p_scheduled is pending
        $records = DB::table('user_permission')->where('user_id', $user->id)->get();

        $activeRec = $records->where('permission_id', $p_active->id)->first();
        // It should either be null or less than/equal to now (not the future date)
        if ($activeRec->start_date) {
            $this->assertTrue(Carbon::parse($activeRec->start_date)->lte(Carbon::now()));
        }

        $scheduledRec = $records->where('permission_id', $p_scheduled->id)->first();
        $this->assertNotNull($scheduledRec->start_date);
        $this->assertEquals($start, Carbon::parse($scheduledRec->start_date)->toDateString());
    }

    /**
     * Test: Updating schedule of existing override.
     */
    public function test_updating_schedule_of_existing_override() {
        Passport::actingAs($this->superAdmin);

        $perm = Permission::firstOrCreate(['module' => Module::BOOKS, 'action' => Action::VIEW], ['name' => ['en' => 'View Books']]);
        $user = User::factory()->create();

        // 1. Initial Grant (Immediate)
        $this->postJson("/api/system/users/{$user->id}/grant-permission", ['permission' => $perm->slug])
            ->assertStatus(200);

        $firstRec = DB::table('user_permission')->where('user_id', $user->id)->first();
        $this->assertNull($firstRec->end_date);

        // 2. Reschedule to have an end date
        $end = Carbon::now()->addMonth()->toDateString();
        $this->postJson("/api/system/users/{$user->id}/sync-permissions", [
            'permissions' => [$perm->slug => true],
            'end_date' => $end
        ])->assertStatus(200);

        // 3. Verify: Record updated/replaced with new schedule
        $records = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->get();

        $this->assertCount(1, $records);
        $this->assertNotNull($records[0]->end_date);
        $this->assertEquals($end, Carbon::parse($records[0]->end_date)->toDateString());
    }

    /**
     * Test: Role change is naturally inherited in hybrid mode.
     */
    public function test_role_change_is_naturally_inherited() {
        Passport::actingAs($this->superAdmin);

        $p_orig = Permission::firstOrCreate(['module' => Module::REPORTS, 'action' => Action::VIEW], ['name' => ['en' => 'View Reports']]);
        $p_new = Permission::firstOrCreate(['module' => Module::REPORTS, 'action' => Action::CREATE], ['name' => ['en' => 'Create Reports']]);

        $role = Role::create(['name' => ['en' => 'Flex Role'], 'slug' => 'flex-role', 'hierarchy_level' => 10]);
        $role->permissions()->attach($p_orig->id);

        $user = User::forceCreate([
            'email' => 'flex@lms.com',
            'password' => Hash::make('password'),
            'name' => ['en' => 'Flex User'],
            'is_active' => true
        ]);
        $user->roles()->attach($role->id, ['is_active' => true]);

        // Assert initial
        $this->assertTrue($user->hasPermission($p_orig->slug));
        $this->assertFalse($user->hasPermission($p_new->slug));

        // Update Role
        $role->permissions()->attach($p_new->id);

        // Assert natural inheritance (No sync needed for non-overridden users)
        $this->assertTrue($user->hasPermission($p_new->slug), 'New role permission should be visible immediately in hybrid mode');
    }
}
