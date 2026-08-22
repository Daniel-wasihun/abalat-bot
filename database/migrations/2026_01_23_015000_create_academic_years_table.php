<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('academic_years', function (Blueprint $row) {
            $row->id();
            $row->string('year')->unique(); // e.g., "2024/2025"
            $row->string('name'); // Translatable name
            $row->date('start_date');
            $row->date('end_date');
            $row->boolean('is_current')->default(false);
            $row->boolean('is_active')->default(true);
            $row->timestamps();
            $row->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('academic_years');
    }
};
