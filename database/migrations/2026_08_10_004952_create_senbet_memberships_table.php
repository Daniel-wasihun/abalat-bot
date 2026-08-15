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
        Schema::create('senbet_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('education_level')->nullable();
            $table->string('emergency_name')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('emergency_sub_city')->nullable();
            $table->string('emergency_woreda')->nullable();
            $table->string('emergency_house_number')->nullable();
            $table->text('emergency_address')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('senbet_class')->nullable();
            $table->string('section', 50)->nullable();
            $table->boolean('previous_participation')->default(false);
            $table->string('previous_participation_document')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senbet_memberships');
    }
};
