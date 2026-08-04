<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserValidationTest extends TestCase {
    use RefreshDatabase;
    use \Tests\Traits\CreatesSuperAdmin;
    protected $seed = true;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();
        \App\Models\Role::firstOrCreate(['slug' => 'student'], ['name' => ['en' => 'Student'], 'hierarchy_level' => 10]);
    }

    public function test_gender_validation_in_registration() {
        $school = \App\Models\School::factory()->create();
        $dept = \App\Models\Department::firstOrCreate(['slug' => 'test-dept-1'], [
            'name' => ['en' => 'Test'],
            'school_id' => $school->id,
            'short_code' => 'T1',
            'total_year' => 4,
        ]);
        $response = $this->actingAs($this->superAdmin, 'api')
            ->postJson('/api/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'Pass@1234',
                'password_confirmation' => 'Pass@1234',
                'gender' => 'invalid_gender', // Invalid
                'user_university_id' => 'LMS12345678',
                'role' => 'student',
                'department_id' => $dept->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_password_complexity() {
        $school = \App\Models\School::factory()->create();
        $dept = \App\Models\Department::firstOrCreate(['slug' => 'test-dept-1'], [
            'name' => ['en' => 'Test'],
            'school_id' => $school->id,
            'short_code' => 'T1',
            'total_year' => 4,
        ]);
        $weakPasswords = [
            'short',
            'no_num!',
            '12345678!', // No letters
            'lettersonly!', // No numbers
            'letters123', // No symbols
            'no_upper1!', // No uppercase
            'NO_LOWER1!', // No lowercase
        ];

        foreach ($weakPasswords as $i => $pass) {
            $response = $this->actingAs($this->superAdmin, 'api')
                ->postJson('/api/register', [
                    'name' => 'Test User Registration',
                    'email' => "test_pass_{$i}@example.com",
                    'password' => $pass,
                    'password_confirmation' => $pass,
                    'gender' => 'male',
                    'user_university_id' => 'LMS' . (10000000 + $i),
                    'role' => 'student',
                    'department_id' => $dept->id,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        }
    }

    public function test_inactive_department_during_registration() {
        // Create an inactive department
        $campus = \App\Models\Campus::factory()->create();
        $college = \App\Models\College::factory()->create(['campus_id' => $campus->id]);
        $school = \App\Models\School::factory()->create(['college_id' => $college->id]);
        $dept = \App\Models\Department::factory()->create([
            'school_id' => $school->id,
            'is_active' => false
        ]);

        $response = $this->actingAs($this->superAdmin, 'api')
            ->postJson('/api/register', [
                'name' => 'Inactive Dept User',
                'email' => 'inactive_dept@example.com',
                'password' => 'Pass@1234',
                'password_confirmation' => 'Pass@1234',
                'gender' => 'male',
                'user_university_id' => 'LMS' . rand(10000000, 99999999),
                'role' => 'student',
                'user_type' => 'student',
                'department_id' => $dept->id,
            ]);


        $response->assertStatus(422);

        $this->assertEquals(\App\Services\BackMessage::get('department_inactive_error'), $response->json('message'));
    }

    public function test_inactive_department_during_update() {
        $user = User::factory()->create();

        $initialDept = \App\Models\Department::where('slug', 'default-department')->first();

        \App\Models\UserInfo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'user_university_id' => 'LMS' . rand(10000000, 99999999),
                'user_type' => 'student',
                'department_id' => $initialDept->id
            ]
        );

        // Create an inactive department
        $campus = \App\Models\Campus::factory()->create();
        $college = \App\Models\College::factory()->create(['campus_id' => $campus->id]);
        $school = \App\Models\School::factory()->create(['college_id' => $college->id]);
        $dept = \App\Models\Department::factory()->create([
            'school_id' => $school->id,
            'is_active' => false
        ]);

        $response = $this->actingAs($this->superAdmin, 'api')
            ->putJson("/api/system/users/{$user->id}", [
                'email' => $user->email,
                'department_id' => $dept->id,
                'gender' => 'male',
            ]);

        $response->assertStatus(422);
        $this->assertEquals(\App\Services\BackMessage::get('department_inactive_error'), $response->json('message'));
    }
}
