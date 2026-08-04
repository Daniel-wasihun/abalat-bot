<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSchedulerTest extends TestCase {
    use RefreshDatabase;

    protected $approver;

    protected function setUp(): void {
        parent::setUp();
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Test Client', '--no-interaction' => true]);

        // Ensure roles exist
        Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);
        Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 60]);

        // Create an approver user
        $this->approver = User::factory()->create();
    }

    public function test_scheduled_role_activates_on_start_date() {
        // Create a user with a current role
        $user = User::factory()->create();
        $currentRole = Role::where('slug', 'librarian')->first();
        $futureRole = Role::where('slug', 'manager')->first();

        // Assign current role
        $user->roles()->attach($currentRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
        ]);

        // Assign future role (starts tomorrow, but we'll manipulate time)
        $user->roles()->attach($futureRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subHour(), // Should activate
        ]);

        // Run the scheduler
        Artisan::call('roles:process-scheduled');

        // Verify future role is now active
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $futureRole->id,
            'is_active' => true,
        ]);

        // Verify old role is now inactive
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $currentRole->id,
            'is_active' => false,
        ]);
    }

    public function test_role_expires_and_reverts_to_previous() {
        $user = User::factory()->create();
        $originalRole = Role::where('slug', 'librarian')->first();
        $temporaryRole = Role::where('slug', 'manager')->first();

        // Assign original role (started 10 days ago)
        $user->roles()->attach($originalRole->id, [
            'is_active' => false, // Will be reactivated
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(10),
        ]);

        // Assign temporary role (started 5 days ago, expires now)
        $user->roles()->attach($temporaryRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->subHour(), // Should expire
        ]);

        // Run the scheduler
        Artisan::call('roles:process-scheduled');

        // Verify temporary role is now inactive
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $temporaryRole->id,
            'is_active' => false,
        ]);

        // Verify original role is reactivated
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $originalRole->id,
            'is_active' => true,
        ]);
    }

    public function test_role_does_not_activate_before_start_date() {
        $user = User::factory()->create();
        $currentRole = Role::where('slug', 'librarian')->first();
        $futureRole = Role::where('slug', 'manager')->first();

        // Assign current role
        $user->roles()->attach($currentRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
        ]);

        // Assign future role (starts tomorrow)
        $user->roles()->attach($futureRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->addDay(), // Future date
        ]);

        // Run the scheduler
        Artisan::call('roles:process-scheduled');

        // Verify future role is still inactive
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $futureRole->id,
            'is_active' => false,
        ]);

        // Verify current role is still active
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $currentRole->id,
            'is_active' => true,
        ]);
    }

    public function test_role_does_not_expire_before_end_date() {
        $user = User::factory()->create();
        $role = Role::where('slug', 'manager')->first();

        // Assign role with future end date
        $user->roles()->attach($role->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDay(), // Future end date
        ]);

        // Run the scheduler
        Artisan::call('roles:process-scheduled');

        // Verify role is still active
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_multiple_users_processed_correctly() {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $librarianRole = Role::where('slug', 'librarian')->first();
        $managerRole = Role::where('slug', 'manager')->first();

        // User 1: Should activate scheduled role
        $user1->roles()->attach($librarianRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
        ]);
        $user1->roles()->attach($managerRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subHour(),
        ]);

        // User 2: Should expire role
        $user2->roles()->attach($managerRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->subHour(),
        ]);
        $user2->roles()->attach($librarianRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(10),
        ]);

        // Run the scheduler
        Artisan::call('roles:process-scheduled');

        // Verify user1's manager role is active
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user1->id,
            'role_id' => $managerRole->id,
            'is_active' => true,
        ]);

        // Verify user2's manager role expired and librarian reactivated
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user2->id,
            'role_id' => $managerRole->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user2->id,
            'role_id' => $librarianRole->id,
            'is_active' => true,
        ]);
    }

    public function test_dry_run_does_not_make_changes() {
        $user = User::factory()->create();
        $currentRole = Role::where('slug', 'librarian')->first();
        $futureRole = Role::where('slug', 'manager')->first();

        $user->roles()->attach($currentRole->id, [
            'is_active' => true,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subDays(5),
        ]);
        $user->roles()->attach($futureRole->id, [
            'is_active' => false,
            'assigned_by' => $this->approver->id,
            'start_date' => Carbon::now()->subHour(),
        ]);

        // Run with dry-run flag
        Artisan::call('roles:process-scheduled', ['--dry-run' => true]);

        // Verify nothing changed
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $futureRole->id,
            'is_active' => false, // Still inactive
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $currentRole->id,
            'is_active' => true, // Still active
        ]);
    }
}
