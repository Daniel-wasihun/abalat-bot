<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add course_offering_id to course_enrollments so that an enrollment
     * is scoped to a specific class/grade and academic year (via the offering),
     * rather than just a course. This preserves the existing course_id FK for
     * backward compatibility while enabling the new offering-scoped workflow.
     */
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->foreignId('course_offering_id')
                  ->nullable()
                  ->after('course_id')
                  ->constrained('course_offerings')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropForeign(['course_offering_id']);
            $table->dropColumn('course_offering_id');
        });
    }
};
