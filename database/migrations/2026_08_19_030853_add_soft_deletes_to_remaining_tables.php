<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tables to add soft deletes to.
     */
    protected array $tables = [
        'assessment_types',
        'assessment_components',
        'attendance_records',
        'attendance_sessions',
        'general_attendance_records',
        'general_attendance_sessions',
        'courses',
        'course_offerings',
        'course_enrollments',
        'teacher_assignments',
        'payments',
        'payment_transactions',
        'senbet_classes',
        'student_marks',
        'student_results',
        'feedback',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
