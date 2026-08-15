<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_results', function (Blueprint $table) {
            $table->json('scores')->nullable()->after('course_offering_id');
        });

        // Migrate existing data to JSON before dropping columns
        \Illuminate\Support\Facades\DB::table('student_results')->orderBy('id')->chunk(100, function ($results) {
            foreach ($results as $result) {
                $scores = [];
                if ($result->quiz_ca_score !== null) {
                    $scores['quiz'] = $result->quiz_ca_score;
                }
                if ($result->midterm_score !== null) {
                    $scores['midterm'] = $result->midterm_score;
                }
                if ($result->final_exam_score !== null) {
                    $scores['final'] = $result->final_exam_score;
                }
                \Illuminate\Support\Facades\DB::table('student_results')
                    ->where('id', $result->id)
                    ->update(['scores' => json_encode($scores)]);
            }
        });

        // Seed default assessment types
        \Illuminate\Support\Facades\DB::table('assessment_types')->insert([
            ['name' => 'Assignment / Quiz', 'code' => 'quiz', 'max_score' => 20.00, 'order' => 1, 'is_active' => true],
            ['name' => 'Mid Exam', 'code' => 'midterm', 'max_score' => 30.00, 'order' => 2, 'is_active' => true],
            ['name' => 'Final Exam', 'code' => 'final', 'max_score' => 50.00, 'order' => 3, 'is_active' => true],
        ]);

        // Seed some default classes based on what existed in the system
        $classesToInsert = [];
        $existingClasses = \Illuminate\Support\Facades\DB::table('senbet_memberships')->select('senbet_class')->whereNotNull('senbet_class')->distinct()->pluck('senbet_class')->toArray();
        if (empty($existingClasses)) {
            $existingClasses = ['child', 'post_12', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        }
        foreach ($existingClasses as $c) {
            $name = $c;
            if ($c === 'child') $name = 'Child';
            else if ($c === 'post_12') $name = 'Post-12';
            else if (is_numeric($c)) $name = 'Grade ' . $c;
            $classesToInsert[] = ['name' => $name, 'code' => $c, 'intake_capacity_per_section' => 50, 'number_of_sections' => 1, 'is_active' => true];
        }
        \Illuminate\Support\Facades\DB::table('senbet_classes')->insertOrIgnore($classesToInsert);

        Schema::table('student_results', function (Blueprint $table) {
            $table->dropColumn(['quiz_ca_score', 'midterm_score', 'final_exam_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_results', function (Blueprint $table) {
            $table->dropColumn('scores');
            $table->decimal('quiz_ca_score', 5, 2)->nullable()->comment('Score out of 20');
            $table->decimal('midterm_score', 5, 2)->nullable()->comment('Score out of 30');
            $table->decimal('final_exam_score', 5, 2)->nullable()->comment('Score out of 50');
        });
    }
};
