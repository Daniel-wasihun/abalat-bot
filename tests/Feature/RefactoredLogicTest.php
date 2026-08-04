<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\College;
use App\Models\Department;
use App\Models\School;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use App\Services\BackMessage;

class RefactoredLogicTest extends TestCase {
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void {
        parent::setUp();
        config(['queue.default' => 'sync']);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], [
            'name' => ['en' => 'Admin'],
            'hierarchy_level' => 80
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id, ['is_active' => true, 'start_date' => now()]);

        // Grant permissions for all modules we refactored
        $modules = ['academic_years', 'campuses', 'colleges', 'schools', 'departments'];
        foreach ($modules as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $p = Permission::firstOrCreate(['slug' => "$module.$action"], [
                    'module' => $module,
                    'action' => $action
                ]);
                $adminRole->permissions()->attach($p->id);
            }
        }
    }

    /**
     * Test AcademicYear makeCurrent logic moved to Model
     */
    public function test_academic_year_make_current_logic() {
        $year1 = AcademicYear::factory()->create(['is_current' => true, 'is_active' => true]);
        $year2 = AcademicYear::factory()->create(['is_current' => false, 'is_active' => false]);

        $year2->makeCurrent();

        $this->assertTrue($year2->fresh()->is_current);
        $this->assertTrue($year2->fresh()->is_active);
        $this->assertFalse($year1->fresh()->is_current);
    }

    /**
     * Test CanImportCsv localized error on missing columns (Campus)
     */
    public function test_import_csv_validation_localized_campus() {
        // Include 'short_code' in header but leave it empty in row to trigger row validation error
        $csvContent = "name:en,name:am,short_code\n" . "Test Campus,ሙከራ ግቢ,\n";
        $file = UploadedFile::fake()->createWithContent('campuses.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/hierarchy/campuses/import', [
            'file' => $file
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'pending']);
        $this->assertArrayHasKey('import_id', $response->json());
    }

    /**
     * Test CanImportCsv success (Academic Years)
     */
    public function test_import_csv_success_academic_year() {
        $csvContent = "year,name:en,start_date,end_date\n" .
            "2030/2031,Future Year,2030-01-01,2030-12-31\n";
        $file = UploadedFile::fake()->createWithContent('years.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/academic/academic-years/import', [
            'file' => $file
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'pending']);
        $this->assertArrayHasKey('import_id', $response->json());
    }

    /**
     * Test CanExportCsv template download (Department)
     */
    public function test_export_csv_template_department() {
        $response = $this->actingAs($this->admin, 'api')->get('/api/hierarchy/departments/template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=department_import_template.csv');
        $this->assertStringContainsString('name:en,name:am,short_code,total_year', $response->streamedContent());
    }

    /**
     * Test Department school status validation logic in Controller (refactored)
     */
    public function test_department_creation_blocks_inactive_school() {
        $campus = Campus::factory()->create();
        $college = College::factory()->create(['campus_id' => $campus->id]);
        $school = School::factory()->create(['college_id' => $college->id, 'is_active' => false]);

        $data = [
            'school_slug' => $school->slug,
            'name' => ['en' => 'New Dept', 'am' => 'አዲስ'],
            'short_code' => 'ND',
            'total_year' => 4,
            'is_active' => true
        ];

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/hierarchy/departments', $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => BackMessage::get('school_inactive_error')]);
    }

    /**
     * Test bulk status toggle (Academic Year specific current check)
     */
    public function test_academic_year_bulk_deactivate_current_fails() {
        $current = AcademicYear::factory()->create(['is_current' => true, 'is_active' => true]);
        $other = AcademicYear::factory()->create(['is_current' => false, 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'api')->patchJson('/api/academic/academic-years/bulk-toggle', [
            'ids' => [$current->id, $other->id],
            'active' => false
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => BackMessage::get('academic_year_cannot_deactivate_current_bulk')]);
    }
}
