<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->string('telegram_id')->nullable();
            $table->string('status')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('bot_notifications')->onDelete('cascade');
            $table->foreign('telegram_user_id')->references('id')->on('telegram_users')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('notification_delivery_logs');
    }
};
