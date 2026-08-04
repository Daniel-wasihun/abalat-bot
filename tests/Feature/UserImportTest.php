<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserImportTest extends TestCase {
    use RefreshDatabase, \Tests\Traits\CreatesSuperAdmin;
    protected $seed = true;

    protected function setUp(): void {
        parent::setUp();
        config(['queue.default' => 'sync']);
        $this->createSuperAdmin();
        Artisan::call('passport:client', ['--personal' => true, '--name' => 'Test Client', '--no-interaction' => true]);
    }

    public function test_import_users_requires_department_id() {
        $superAdmin = $this->superAdmin;
        Passport::actingAs($superAdmin);

        // Create a CSV file
        Storage::fake('local');
        $csvContent = "ID,name,email,user_university_id,gender\n1,John Doe,john@example.com,UNI-001,male\n2,Jane Smith,jane@example.com,UNI-002,female";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $role = Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);

        // Test without department_id - should fail
        $response = $this->postJson('/api/system/users/import', [
            'file' => $file,
            'role' => $role->slug,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['department_id']);
    }

    public function test_import_users_with_valid_data() {
        $superAdmin = $this->superAdmin;
        Passport::actingAs($superAdmin);

        // Create a CSV file with required fields
        Storage::fake('local');
        $csvContent = "ID,name,email,user_university_id,gender\n1,Test User,testuser" . time() . "@example.com,UNI" . rand(10000, 99999) . ",male";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $role = Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);
        $department = Department::first();

        // Test with all required fields
        $response = $this->postJson('/api/system/users/import', [
            'file' => $file,
            'role' => $role->slug,
            'department_id' => $department->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'import_id', 'status']);
    }

    public function test_import_users_applies_department_to_all_users() {
        $superAdmin = $this->superAdmin;
        Passport::actingAs($superAdmin);

        $timestamp = time();
        // Create a CSV file with multiple users
        Storage::fake('local');
        $csvContent = "ID,name,email,user_university_id,gender\n";
        $csvContent .= "1,User One,user1{$timestamp}@example.com,UNI" . rand(10000, 99999) . ",male\n";
        $csvContent .= "2,User Two,user2{$timestamp}@example.com,UNI" . rand(10000, 99999) . ",female";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $role = Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);
        $department = Department::first();

        $response = $this->postJson('/api/system/users/import', [
            'file' => $file,
            'role' => $role->slug,
            'department_id' => $department->id,
        ]);

        $response->assertStatus(200);

        // Verify both users have the same department
        $user1 = User::where('email', "user1{$timestamp}@example.com")->first();
        $user2 = User::where('email', "user2{$timestamp}@example.com")->first();

        $this->assertNotNull($user1);
        $this->assertNotNull($user2);
        $this->assertEquals($department->id, $user1->info->department_id);
        $this->assertEquals($department->id, $user2->info->department_id);
    }
}
