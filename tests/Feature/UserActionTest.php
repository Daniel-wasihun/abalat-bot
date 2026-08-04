<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Constants\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Helpers\Permission as PermissionHelper;
use Illuminate\Support\Facades\DB;

class UserActionTest extends TestCase {
    use RefreshDatabase;

    protected $admin;
    protected $manager;
    protected $student;

    protected function setUp(): void {
        parent::setUp();

        // Seed basic roles
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => ['en' => 'Super Admin'], 'hierarchy_level' => 100, 'is_system_level' => true]);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => ['en' => 'Admin'], 'hierarchy_level' => 80]);
        $managerRole = Role::firstOrCreate(['slug' => 'manager'], ['name' => ['en' => 'Manager'], 'hierarchy_level' => 60]);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => ['en' => 'Student'], 'hierarchy_level' => 10]);

        // Create permissions
        $viewUsers = Permission::firstOrCreate(['slug' => 'users.view'], ['module' => 'users', 'action' => 'view']);
        $editUsers = Permission::firstOrCreate(['slug' => 'users.edit'], ['module' => 'users', 'action' => 'edit']);
        $deleteUsers = Permission::firstOrCreate(['slug' => 'users.delete'], ['module' => 'users', 'action' => 'delete']);

        $adminRole->permissions()->syncWithoutDetaching([$viewUsers->id, $editUsers->id, $deleteUsers->id]);
        $managerRole->permissions()->syncWithoutDetaching([$viewUsers->id, $editUsers->id]);

        $this->admin = User::factory()->create(['email' => 'admin@test.com', 'is_active' => true]);
        $this->admin->roles()->attach($adminRole->id, ['start_date' => now(), 'is_active' => true]);

        $this->manager = User::factory()->create(['email' => 'manager@test.com', 'is_active' => true]);
        $this->manager->roles()->attach($managerRole->id, ['assigned_by' => $this->admin->id, 'start_date' => now(), 'is_active' => true]);

        $this->student = User::factory()->create(['email' => 'student@test.com', 'is_active' => true]);
        $this->student->roles()->attach($studentRole->id, ['assigned_by' => $this->manager->id, 'start_date' => now(), 'is_active' => true]);

        $this->student->info()->update([
            'user_university_id' => 'STU-123',
            'user_type' => Type::STUDENT,
        ]);

        $this->manager->info()->update([
            'user_university_id' => 'MGR-123',
            'user_type' => Type::STAFF,
        ]);
    }

    public function test_toggle_user_status() {
        $this->actingAs($this->admin, 'api');

        // Deactivate
        $response = $this->patchJson("/api/system/users/{$this->student->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertFalse($this->student->fresh()->is_active);

        // Activate
        $response = $this->patchJson("/api/system/users/{$this->student->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertTrue($this->student->fresh()->is_active);
    }

    public function test_reset_user_permissions() {
        $this->actingAs($this->admin, 'api');

        $perm = Permission::firstOrCreate(['slug' => 'books.create'], ['module' => 'books', 'action' => 'create']);

        // Grant direct permission
        $this->student->directPermissions()->attach($perm->id, [
            'granted' => true,
            'is_active' => true,
            'start_date' => now()
        ]);

        $this->assertTrue($this->student->fresh()->hasPermission('books.create'));

        // Reset
        $response = $this->postJson("/api/system/users/{$this->student->id}/reset-permissions");
        $response->assertStatus(200);

        $this->assertFalse($this->student->fresh()->hasPermission('books.create'));
        $this->assertEquals(0, $this->student->directPermissions()->count());
    }

    public function test_bulk_activation_deactivation() {
        $this->actingAs($this->admin, 'api');

        $u1 = User::factory()->create();
        $u1->roles()->attach(Role::where('slug', 'student')->first()->id, ['assigned_by' => $this->admin->id, 'is_active' => true]);

        $u2 = User::factory()->create();
        $u2->roles()->attach(Role::where('slug', 'student')->first()->id, ['assigned_by' => $this->admin->id, 'is_active' => true]);

        // Bulk Deactivate
        $response = $this->postJson("/api/system/users/bulk-action", [
            'ids' => [$u1->id, $u2->id],
            'action' => 'deactivate'
        ]);
        $response->assertStatus(200);
        $this->assertFalse($u1->fresh()->is_active);
        $this->assertFalse($u2->fresh()->is_active);

        // Bulk Activate
        $response = $this->postJson("/api/system/users/bulk-action", [
            'ids' => [$u1->id, $u2->id],
            'action' => 'activate'
        ]);
        $response->assertStatus(200);
        $this->assertTrue($u1->fresh()->is_active);
        $this->assertTrue($u2->fresh()->is_active);
    }

    public function test_bulk_delete() {
        $this->actingAs($this->admin, 'api');

        $u1 = User::factory()->create();
        $u1->roles()->attach(Role::where('slug', 'student')->first()->id, ['assigned_by' => $this->admin->id, 'is_active' => true]);

        $response = $this->postJson("/api/system/users/bulk-action", [
            'ids' => [$u1->id],
            'action' => 'delete'
        ]);
        $response->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $u1->id]);
    }

    public function test_bulk_action_hierarchy_protection() {
        // Manager tries to bulk deactivate Admin (should fail/skip)
        $this->actingAs($this->manager, 'api');

        $response = $this->postJson("/api/system/users/bulk-action", [
            'ids' => [$this->admin->id],
            'action' => 'deactivate'
        ]);

        // Controller returns 403 if NO users were allowed
        $response->assertStatus(403);
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_user_index_sorting_by_role() {
        $this->actingAs($this->admin, 'api');

        // Create specific users for sorting test locally to ensure no interference
        // 1. Student (Level 10)
        $s = User::factory()->create(['email' => 'sort_student@test.com', 'is_active' => true]);
        $s->roles()->attach(Role::where('slug', 'student')->first()->id, ['is_active' => true, 'start_date' => now()]);

        // 2. Manager (Level 60)
        $m = User::factory()->create(['email' => 'sort_manager@test.com', 'is_active' => true]);
        $m->roles()->attach(Role::where('slug', 'manager')->first()->id, ['is_active' => true, 'start_date' => now()]);

        // 3. Admin (Level 80)
        $a = User::factory()->create(['email' => 'sort_admin@test.com', 'is_active' => true]);
        $a->roles()->attach(Role::where('slug', 'admin')->first()->id, ['is_active' => true, 'start_date' => now()]);

        // role_asc (Low to High): Student, Manager, Admin
        $response = $this->getJson("/api/system/users?sort_by=role&sort_order=asc&per_page=100");
        $response->assertStatus(200);
        $data = $response->json('data');

        $emails = collect($data)->pluck('email')->toArray();
        $relevantEmails = array_values(array_intersect($emails, ['sort_student@test.com', 'sort_manager@test.com', 'sort_admin@test.com']));

        $this->assertEquals(['sort_student@test.com', 'sort_manager@test.com', 'sort_admin@test.com'], $relevantEmails);

        // role_desc (High to Low): Admin, Manager, Student
        $response = $this->getJson("/api/system/users?sort_by=role&sort_order=desc&per_page=100");
        $data = $response->json('data');
        $emails = collect($data)->pluck('email')->toArray();
        $relevantEmails = array_values(array_intersect($emails, ['sort_student@test.com', 'sort_manager@test.com', 'sort_admin@test.com']));

        $this->assertEquals(['sort_admin@test.com', 'sort_manager@test.com', 'sort_student@test.com'], $relevantEmails);
    }

    public function test_cancel_scheduled_role() {
        $role = Role::where('slug', 'student')->first();
        $targetUser = User::factory()->create();

        // Create a scheduled role (inactive, future start date)
        $targetUser->roles()->attach($role->id, [
            'start_date' => now()->addDays(5)->toDateString(),
            'is_active' => false,
            'assigned_by' => $this->admin->id
        ]);

        $pivotId = DB::table('user_role')->where('user_id', $targetUser->id)->first()->id;

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/system/users/{$targetUser->id}/cancel-scheduled-role/{$pivotId}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_role', [
            'id' => $pivotId,
            'revoked_by' => $this->admin->id
        ]);
    }

    public function test_cancel_scheduled_permission() {
        $permission = Permission::firstOrCreate(['slug' => 'books.create'], ['module' => 'books', 'action' => 'create']);
        $targetUser = User::factory()->create();

        // Create a scheduled permission (inactive, future start date)
        $targetUser->directPermissions()->attach($permission->id, [
            'start_date' => now()->addDays(5)->toDateString(),
            'is_active' => false,
            'granted' => true,
            'assigned_by' => $this->admin->id
        ]);

        $pivotId = DB::table('user_permission')->where('user_id', $targetUser->id)->first()->id;

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/system/users/{$targetUser->id}/cancel-scheduled-permission/{$pivotId}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_permission', [
            'id' => $pivotId,
            'revoked_by' => $this->admin->id
        ]);
    }

    public function test_update_scheduled_role() {
        $role = Role::where('slug', 'student')->first();
        $targetUser = User::factory()->create();

        $targetUser->roles()->attach($role->id, [
            'start_date' => now()->addDays(5)->toDateString(),
            'is_active' => false,
            'assigned_by' => $this->admin->id
        ]);

        $pivotId = DB::table('user_role')->where('user_id', $targetUser->id)->first()->id;
        $newStartDate = now()->addDays(10)->toDateString();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/system/users/{$targetUser->id}/update-scheduled-role/{$pivotId}", [
                'start_date' => $newStartDate
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_role', [
            'id' => $pivotId,
            'start_date' => $newStartDate . ' 00:00:00'
        ]);
    }

    public function test_update_scheduled_permission() {
        $permission = Permission::firstOrCreate(['slug' => 'books.edit'], ['module' => 'books', 'action' => 'edit']);
        $targetUser = User::factory()->create();

        $targetUser->directPermissions()->attach($permission->id, [
            'start_date' => now()->addDays(5)->toDateString(),
            'is_active' => false,
            'granted' => true,
            'assigned_by' => $this->admin->id
        ]);

        $pivotId = DB::table('user_permission')->where('user_id', $targetUser->id)->first()->id;
        $newEndDate = now()->addDays(20)->toDateString();

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/system/users/{$targetUser->id}/update-scheduled-permission/{$pivotId}", [
                'end_date' => $newEndDate
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_permission', [
            'id' => $pivotId,
            'end_date' => $newEndDate . ' 23:59:59',
            'start_date' => now()->addDays(5)->toDateString() . ' 00:00:00'
        ]);
    }

    public function test_update_scheduled_role_activates_immediate() {
        $role = Role::where('slug', 'student')->first();
        $targetUser = User::factory()->create();

        // Create inactive future assignment
        $targetUser->roles()->attach($role->id, [
            'start_date' => now()->addDays(5),
            'is_active' => false,
            'assigned_by' => $this->admin->id
        ]);

        $pivotId = DB::table('user_role')->where('user_id', $targetUser->id)->first()->id;

        // Update start_date to TODAY (should activate)
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/system/users/{$targetUser->id}/update-scheduled-role/{$pivotId}", [
                'start_date' => now()->toDateString()
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_role', [
            'id' => $pivotId,
            'is_active' => true
        ]);
    }

    public function test_update_scheduled_permission_activates_immediate() {
        $permission = Permission::firstOrCreate(['slug' => 'books.delete'], ['module' => 'books', 'action' => 'delete']);
        $targetUser = User::factory()->create();

        // Create inactive future assignment
        $targetUser->directPermissions()->attach($permission->id, [
            'start_date' => now()->addDays(5),
            'is_active' => false,
            'granted' => true,
            'assigned_by' => $this->admin->id
        ]);

        $pivotId = DB::table('user_permission')->where('user_id', $targetUser->id)->first()->id;

        // Update start_date to TODAY (should activate)
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/system/users/{$targetUser->id}/update-scheduled-permission/{$pivotId}", [
                'start_date' => now()->toDateString()
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_permission', [
            'id' => $pivotId,
            'is_active' => true
        ]);
    }
}
