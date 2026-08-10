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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('credit_hours')->default(1);
            $table->string('senbet_class'); // matches senbet_memberships.senbet_class (e.g. child, 1, 2, 3...)
            $table->enum('semester', [1, 2]);
            $table->json('prerequisites')->nullable(); // array of course codes
            $table->integer('duration_weeks')->nullable();
            $table->integer('teaching_hours')->nullable();
            $table->string('schedule_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
