<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiplePermissionUpdateTest extends TestCase {
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        // Ensure super-admin role exists, idempotent
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name'            => ['en' => 'Super Admin', 'am' => 'ከፍተኛ አስተዳዳሪ'],
                'description'     => ['en' => 'Super Admin'],
                'hierarchy_level' => 100,
                'is_system_level' => true,
                'is_active'       => true,
            ]
        );
        $superAdminRole->update(['is_active' => true]);

        // Give super-admin all permissions
        $superAdminRole->permissions()->sync(Permission::pluck('id'));

        // Ensure student role exists, idempotent
        Role::firstOrCreate(
            ['slug' => 'student'],
            [
                'name'            => ['en' => 'Student', 'am' => 'ተማሪ'],
                'hierarchy_level' => 10,
                'is_active'       => true,
            ]
        );

        // Create a super-admin user for this test class
        $this->superAdmin = User::factory()->create(['email' => 'superadmin_' . uniqid() . '@test.com']);
        $this->superAdmin->roles()->attach($superAdminRole->id, [
            'assigned_by' => null,
            'start_date'  => now()->subMinute(),
            'is_active'   => true,
        ]);
    }

    public function test_updating_multiple_permissions_simultaneously_works_correctly(): void {
        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->attach($studentRole->id, [
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->subMinute(),
            'is_active'   => true,
        ]);

        // Get some permissions the student doesn't have
        $permissions = Permission::whereNotIn('slug', ['super-admin'])->take(5)->get();

        $payload = [];
        foreach ($permissions as $perm) {
            $payload[$perm->slug] = true;
        }

        $response = $this->actingAs($this->superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => $payload,
            ]);

        $response->assertStatus(200);

        $user = $user->fresh();
        foreach ($permissions as $perm) {
            $this->assertTrue(
                $user->hasPermission($perm->slug),
                "User should have permission: {$perm->slug}"
            );
        }

        $this->assertEquals(
            count($payload),
            DB::table('user_permission')
                ->where('user_id', $user->id)
                ->where('granted', true)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->count()
        );
    }

    public function test_toggling_multiple_permissions_on_and_off_works(): void {
        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->attach($studentRole->id, [
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->subMinute(),
            'is_active'   => true,
        ]);

        // Get permissions that do not have implicit dependencies on each other
        $perms = Permission::whereIn('slug', ['books.create', 'users.create', 'colleges.create'])->take(3)->get();

        // First: Grant all 3 permissions
        $payload1 = [];
        foreach ($perms as $p) {
            $payload1[$p->slug] = true;
        }

        $this->actingAs($this->superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => $payload1,
            ])
            ->assertStatus(200);

        $user = $user->fresh();
        foreach ($perms as $p) {
            $this->assertTrue($user->hasPermission($p->slug));
        }

        // Second: Revoke 2, keep 1
        $payload2 = [
            $perms[0]->slug => false,  // Revoke
            $perms[1]->slug => false,  // Revoke
            $perms[2]->slug => true,   // Keep
        ];

        $this->actingAs($this->superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => $payload2,
            ])
            ->assertStatus(200);

        $user = $user->fresh();
        $this->assertFalse($user->hasPermission($perms[0]->slug), "Permission 0 should be revoked");
        $this->assertFalse($user->hasPermission($perms[1]->slug), "Permission 1 should be revoked");
        $this->assertTrue($user->hasPermission($perms[2]->slug), "Permission 2 should still be granted");
    }

    public function test_multiple_permissions_with_scheduling_works(): void {
        $user = User::factory()->create();
        $studentRole = Role::where('slug', 'student')->first();
        $user->roles()->attach($studentRole->id, [
            'assigned_by' => $this->superAdmin->id,
            'start_date'  => now()->subMinute(),
            'is_active'   => true,
        ]);

        $perms = Permission::whereNotIn('slug', ['super-admin'])->take(3)->get();

        $payload = [];
        foreach ($perms as $p) {
            $payload[$p->slug] = true;
        }

        $futureDate = now()->addDays(7)->toDateString();

        $this->actingAs($this->superAdmin, 'api')
            ->postJson("/api/system/users/{$user->id}/sync-permissions", [
                'permissions' => $payload,
                'start_date'  => $futureDate,
            ])
            ->assertStatus(200);

        $user = $user->fresh();

        // Permissions should NOT be active yet (future-dated)
        foreach ($perms as $p) {
            $this->assertFalse(
                $user->hasPermission($p->slug),
                "Permission {$p->slug} should not be active yet (scheduled for future)"
            );
        }

        // Verify scheduled records exist
        $scheduledCount = DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('is_active', false)
            ->whereNull('revoked_at')
            ->count();

        $this->assertEquals(count($payload), $scheduledCount);
    }
}
