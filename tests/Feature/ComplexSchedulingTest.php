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

class ComplexSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected $approver;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Test Client', '--no-interaction' => true]);
        
        // Ensure roles exist
        Role::firstOrCreate(['slug' => 'student'], ['name' => ['en' => 'Student'], 'hierarchy_level' => 10]);
        Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 60]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => ['en' => 'Admin'], 'hierarchy_level' => 80]);

        // Ensure permission exists
        Permission::firstOrCreate(['slug' => 'books.view'], ['module' => 'books', 'action' => 'view']);

        $this->approver = User::factory()->create();
    }

    /**
     * Test a sequence of multiple future role assignments with different start dates
     */
    public function test_multiple_role_schedules_sequence()
    {
        $baseTime = now();
        
        // 1. Initial State: User is Student
        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        // Assign current role explicitly
        $user->roles()->attach($studentRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => $baseTime,
        ]);

        // 2. Schedule Role Changes
        // T+1 Week: Become Manager
        $user->roles()->attach($managerRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => $baseTime->copy()->addWeek(),
        ]);

        // T+2 Weeks: Become Admin
        $user->roles()->attach($adminRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => $baseTime->copy()->addWeeks(2),
        ]);

        // Verify Initial State
        $this->assertTrue($user->fresh()->hasRole('student'));
        $this->assertFalse($user->fresh()->hasRole('manager'));
        $this->assertFalse($user->fresh()->hasRole('admin'));

        // --- TIME JUMP 1: T+1 Week + 1 minute ---
        Carbon::setTestNow($baseTime->copy()->addWeek()->addMinute());

        // Run Scheduler
        Artisan::call('roles:process-scheduled');

        // Verify: Student Inactive, Manager Active, Admin Inactive
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $studentRole->id,
            'is_active' => false
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $managerRole->id,
            'is_active' => true
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
            'is_active' => false
        ]);

        // --- TIME JUMP 2: T+2 Weeks + 1 minute ---
        Carbon::setTestNow($baseTime->copy()->addWeeks(2)->addMinute());

        // Run Scheduler
        Artisan::call('roles:process-scheduled');

        // Verify: Manager Inactive, Admin Active
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $managerRole->id,
            'is_active' => false
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $adminRole->id,
            'is_active' => true
        ]);

        Carbon::setTestNow(); // Clean up
    }

    /**
     * Test a sequence of multiple periodic permission overrides
     */
    public function test_multiple_permission_schedules_sequence()
    {
        $baseTime = now();
        $user = User::factory()->create();
        $perm = Permission::where('slug', 'books.view')->first();

        // 1. Schedule Sequence
        // T+1 Day: Grant Permission
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $perm->id,
            'granted' => true,
            'is_active' => false,
            'start_date' => $baseTime->copy()->addDay(),
            'assigned_by' => $this->approver->id,
            'created_at' => $baseTime, 'updated_at' => $baseTime
        ]);

        // T+3 Days: Revoke Permission
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $perm->id,
            'granted' => false,
            'is_active' => false,
            'start_date' => $baseTime->copy()->addDays(3),
            'revoked_by' => $this->approver->id,
            'created_at' => $baseTime, 'updated_at' => $baseTime
        ]);

        // T+5 Days: Grant Permission Again
        DB::table('user_permission')->insert([
            'user_id' => $user->id,
            'permission_id' => $perm->id,
            'granted' => true,
            'is_active' => false,
            'start_date' => $baseTime->copy()->addDays(5),
            'assigned_by' => $this->approver->id,
            'created_at' => $baseTime, 'updated_at' => $baseTime
        ]);

        // Initial State
        $this->assertFalse($user->fresh()->hasPermission('books.view'));

        // --- STEP 1: T+1 Day ---
        Carbon::setTestNow($baseTime->copy()->addDays(1)->addMinute());
        Artisan::call('permissions:process-scheduled');
        
        // Should be GRANTED
        $activePerm = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $perm->id)
            ->where('is_active', true)
            ->first();
            
        $this->assertNotNull($activePerm, "Permission should be active at T+1");
        $this->assertTrue((bool)$activePerm->granted, "Permission should be granted at T+1");

        // --- STEP 2: T+3 Days ---
        Carbon::setTestNow($baseTime->copy()->addDays(3)->addMinute());
        Artisan::call('permissions:process-scheduled');

        // Should be REVOKED (Granted = false)
        $activePerm = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $perm->id)
            ->where('is_active', true)
            ->first();
            
        $this->assertNotNull($activePerm, "Revocation record should be active at T+3");
        $this->assertFalse((bool)$activePerm->granted, "Permission should be explicitly revoked (granted=false) at T+3");

        // --- STEP 3: T+5 Days ---
        Carbon::setTestNow($baseTime->copy()->addDays(5)->addMinute());
        Artisan::call('permissions:process-scheduled');

        // Should be GRANTED AGAIN
        $activePerm = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('permission_id', $perm->id)
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($activePerm, "Permission should be active at T+5");
        $this->assertTrue((bool)$activePerm->granted, "Permission should be granted again at T+5");

        Carbon::setTestNow();
    }
}
