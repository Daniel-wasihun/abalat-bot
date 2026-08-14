<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Student results are stored with fixed assessment columns per the
     * Ethiopian grading structure: Quiz/CA, Midterm, and Final exam.
     * Total is auto-calculated. Letter grade is derived from total.
     *
     * Default weights (configurable via GradingService):
     *   Quiz/CA  : 20%   (max raw score 20)
     *   Midterm  : 30%   (max raw score 30)
     *   Final    : 50%   (max raw score 50)
     *   Total    : 100%
     */
    public function up(): void
    {
        Schema::create('student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->cascadeOnDelete();

            // Fixed assessment columns (scores out of their respective weights)
            $table->decimal('quiz_ca_score', 5, 2)->nullable()->comment('Score out of 20');
            $table->decimal('midterm_score', 5, 2)->nullable()->comment('Score out of 30');
            $table->decimal('final_exam_score', 5, 2)->nullable()->comment('Score out of 50');

            // Computed fields (updated automatically on save)
            $table->decimal('total_score', 5, 2)->nullable()->comment('Auto-calculated total out of 100');
            $table->string('letter_grade', 5)->nullable()->comment('A, B, C, D, F or NG');
            $table->text('remarks')->nullable();

            // Admin/finalization controls
            $table->boolean('is_finalized')->default(false)->comment('Finalized results cannot be edited by teachers');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'course_offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
