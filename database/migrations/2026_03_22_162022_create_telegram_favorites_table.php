<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('telegram_favorites', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id');
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->timestamps();

            // A user can only favorite a specific resource once
            $table->unique(['telegram_id', 'resource_type', 'resource_id'], 'user_fav_unique');

            $table->foreign('telegram_id')->references('telegram_id')->on('telegram_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('telegram_favorites');
    }
};
