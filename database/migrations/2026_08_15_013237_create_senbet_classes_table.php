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
        Schema::create('senbet_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Grade 1"
            $table->string('code')->unique(); // e.g. "1" (legacy identifier)
            $table->integer('intake_capacity_per_section')->default(50);
            $table->integer('number_of_sections')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senbet_classes');
    }
};
