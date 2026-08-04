<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('suspicious_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('activity_type'); // e.g., 'honeypot_triggered', 'brute_force_attempt', 'sql_injection_pattern'
            $table->text('request_data')->nullable(); // JSON of request params (excluding sensitive ones)
            $table->string('url');
            $table->string('method');
            $table->integer('severity')->default(1); // 1-5 scale
            $table->timestamps();

            $table->index(['ip_address', 'activity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('suspicious_activities');
    }
};
