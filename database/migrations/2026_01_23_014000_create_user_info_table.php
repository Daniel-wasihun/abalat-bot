<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('user_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('registration_id')->unique();
            $table->enum('gender', ['male', 'female']);
            $table->string('phone_number')->nullable();
            
            $table->string('father_name')->nullable();
            $table->string('grandfather_name')->nullable();
            $table->string('christian_name')->nullable();
            $table->string('spiritual_father_name')->nullable();
            $table->string('sub_city')->nullable();
            $table->string('woreda')->nullable();
            $table->string('house_number')->nullable();

            $table->text('address')->nullable();
            $table->string('profile_picture')->nullable();
            $table->enum('status', ['active', 'suspended', 'restricted'])->default('active');
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('user_info');
    }
};
