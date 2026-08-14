<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A CourseOffering represents a specific course being taught to a specific
     * grade/class in a specific academic year and semester.
     * This allows the same course (e.g. "Mathematics") to be offered to
     * multiple classes independently, with separate teacher assignments,
     * enrollments, and results for each.
     */
    public function up(): void
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            // Grade/class identifier — matches senbet_memberships.senbet_class (e.g. 'child','1','2'...)
            $table->string('senbet_class');
            $table->enum('semester', ['1', '2']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // A course can only have one active offering per class per year per semester
            $table->unique(['course_id', 'senbet_class', 'academic_year_id', 'semester'], 'unique_course_offering');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};
