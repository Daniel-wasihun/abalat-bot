<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->string('telegram_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('username')->nullable();
            $table->string('language')->nullable();
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->nullable();
            $table->text('message');
            $table->string('type')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('attachment_type')->nullable();
            $table->string('file_name')->nullable();
            $table->string('telegram_message_id')->nullable();
            $table->timestamps();

            $table->foreign('telegram_user_id')->references('id')->on('telegram_users')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('feedback');
    }
};
