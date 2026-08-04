<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\ImportResult;
use App\Jobs\PerformImport;
use App\Http\Controllers\UserController;
use App\Services\BackMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Laravel\Passport\Passport;

class ExtremeEdgeCaseTest extends TestCase {
    use RefreshDatabase, \Tests\Traits\CreatesSuperAdmin;

    protected function setUp(): void {
        parent::setUp();
        $this->createSuperAdmin();

        // Ensure standard roles exist
        Role::firstOrCreate(['slug' => 'student'], ['name' => ['en' => 'Student'], 'hierarchy_level' => 10]);
        Role::firstOrCreate(['slug' => 'librarian'], ['name' => ['en' => 'Librarian'], 'hierarchy_level' => 40]);
    }

    /**
     * Test User Import Job with mixed valid and invalid rows
     */
    public function test_user_import_job_handles_malformed_and_duplicate_rows() {
        $diskName = config('filesystems.default');
        Storage::fake($diskName);
        Mail::fake();

        $dept = Department::where('slug', 'default-department')->first();

        $csvContent = "name,email,user_university_id,gender\n" .
            "John Doe,john@test.com,UNI10001,male\n" .
            ",,,\n" .
            "Dup User,dup@test.com,UNI10001,female\n" .
            ",missing@test.com,UNI20002,male";

        $filePath = 'imports/test.csv';
        // We must use the disk instance to put the file so Storage::path() works in Job
        Storage::disk($diskName)->put($filePath, $csvContent);

        $importResult = ImportResult::create([
            'type' => 'user_import',
            'status' => 'pending',
            'total_rows' => 4,
            'user_id' => $this->superAdmin->id,
        ]);

        $config = [
            'type' => 'user_import',
            'handler_class' => UserController::class,
            'handler_method' => 'processImportRow',
            'required_columns' => ['name', 'email'],
            'attributes' => [],
            'locale' => 'en',
        ];

        $context = [
            'role' => 'student',
            'department_id' => $dept->id,
        ];

        $job = new PerformImport($importResult->id, $filePath, $config, $context);
        $job->handle();

        $importResult->refresh();

        if ($importResult->status !== 'completed') {
            fwrite(STDERR, print_r($importResult->errors, true));
        }

        $this->assertEquals('completed', $importResult->status);
        $this->assertEquals(1, $importResult->imported_count);
        $this->assertEquals(3, $importResult->processed_rows);
        $this->assertCount(2, $importResult->errors);

        $this->assertDatabaseHas('users', ['email' => 'john@test.com']);
        $this->assertFalse(Storage::disk($diskName)->exists($filePath));
    }

    /**
     * Test User Import Job fails if role slug is invalid
     */
    public function test_user_import_job_fails_if_role_invalid() {
        $diskName = config('filesystems.default');
        Storage::fake($diskName);

        $filePath = 'imports/fail.csv';
        Storage::disk($diskName)->put($filePath, "name,email,user_university_id,gender\nTest User,test@test.com,ID10001,male");

        $importResult = ImportResult::create([
            'type' => 'user_import',
            'status' => 'pending',
            'user_id' => $this->superAdmin->id,
        ]);

        $config = [
            'type' => 'user_import',
            'handler_class' => UserController::class,
            'handler_method' => 'processImportRow',
            'required_columns' => ['name', 'email'],
            'attributes' => [],
            'locale' => 'en',
        ];

        $context = [
            'role' => 'ghost-role',
            'department_id' => 1,
        ];

        $job = new PerformImport($importResult->id, $filePath, $config, $context);
        $job->handle();

        $importResult->refresh();
        $this->assertEquals('failed', $importResult->status);
        $this->assertStringContainsString('No query results for model', $importResult->errors[0]);
    }

    /**
     * Test department activity check logic fully
     */
    public function test_department_can_accept_users_only_when_both_dept_and_school_are_active() {
        $dept = Department::where('slug', 'default-department')->first();
        $school = $dept->school;

        // Both Active
        $dept->update(['is_active' => true]);
        $school->update(['is_active' => true]);
        $this->assertTrue($dept->canAcceptUsers());

        // Dept Inactive
        $dept->update(['is_active' => false]);
        $this->assertFalse($dept->canAcceptUsers());

        // School Inactive
        $dept->update(['is_active' => true]);
        $school->update(['is_active' => false]);
        $this->assertFalse($dept->canAcceptUsers());
    }

    /**
     * Test localized name fallback when no language keys match
     */
    public function test_localized_trait_handles_empty_name_object() {
        $user = User::factory()->create(['name' => []]);
        $this->assertEquals('', $user->name__localized);

        $user->update(['name' => ['de' => 'Gerhard']]);
        $this->assertEquals('Gerhard', $user->name__localized);
    }

    /**
     * Test removal of profile picture via UserController update
     */
    public function test_user_update_can_successfully_remove_profile_picture_file() {
        $diskName = 'public';
        Storage::fake($diskName);

        $user = User::factory()->create();
        $path = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg')->store('profile_pictures', $diskName);
        $user->info->update(['profile_picture' => $path]);

        Passport::actingAs($this->superAdmin);

        $response = $this->putJson("/api/system/users/{$user->id}", [
            'remove_profile_picture' => true,
            'email' => $user->email,
            'gender' => 'male',
        ]);

        $response->assertStatus(200);
        $this->assertNull($user->fresh()->info->profile_picture);
        $this->assertFalse(Storage::disk($diskName)->exists($path));
    }
}
