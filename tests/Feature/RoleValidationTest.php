<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Tests\Traits\CreatesSuperAdmin;

class RoleValidationTest extends TestCase {
    use RefreshDatabase, CreatesSuperAdmin;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();
    }

    public function test_role_name_must_be_at_least_4_characters() {
        $response = $this->actingAs($this->superAdmin, 'api')->postJson('/api/system/roles', [
            'name' => 'Bad', // 3 chars
            'hierarchy_level' => 10,
            'permissions' => ['users.view']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_role_name_must_not_exceed_20_characters() {
        $response = $this->actingAs($this->superAdmin, 'api')->postJson('/api/system/roles', [
            'name' => 'ThisNameIsWayTooLongForTheSystem', // >20 chars
            'hierarchy_level' => 10,
            'permissions' => ['users.view']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_hierarchy_level_must_not_exceed_99() {
        // Boost admin level to 200 to bypass authorization check (200 > 100)
        // This allows us to hit the max:99 validation rule
        $this->superAdmin->roles()->first()->update(['hierarchy_level' => 200]);

        $response = $this->actingAs($this->superAdmin, 'api')->postJson('/api/system/roles', [
            'name' => 'ValidName',
            'hierarchy_level' => 100, // Invalid by validation
            'permissions' => ['users.view']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hierarchy_level']);
    }

    public function test_role_creation_fails_if_name_is_duplicated() {
        // Create first role
        $this->actingAs($this->superAdmin, 'api')->postJson('/api/system/roles', [
            'name' => 'UniqueRole',
            'hierarchy_level' => 50,
            'permissions' => ['users.view']
        ])->assertStatus(200);

        // Try to create second role with same name
        $response = $this->actingAs($this->superAdmin, 'api')->postJson('/api/system/roles', [
            'name' => 'UniqueRole',
            'hierarchy_level' => 50,
            'permissions' => ['users.view']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_valid_role_creation_passes() {
        $response = $this->actingAs($this->superAdmin, 'api')->postJson('/api/system/roles', [
            'name' => 'ValidRole',
            'hierarchy_level' => 50,
            'permissions' => ['users.view']
        ]);

        $response->assertStatus(200);
    }
}
