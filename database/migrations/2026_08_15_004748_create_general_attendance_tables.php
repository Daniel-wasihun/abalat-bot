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
        Schema::create('general_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('senbet_class');
            $table->string('section')->nullable();
            $table->date('date');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Only one session per class/section per date
            $table->unique(['date', 'senbet_class', 'section'], 'unique_general_session');
        });

        Schema::create('general_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('general_attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('present'); // present, absent, permission
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_attendance_records');
        Schema::dropIfExists('general_attendance_sessions');
    }
};
