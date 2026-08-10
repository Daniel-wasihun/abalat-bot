<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            // Add missing is_active column
            $table->boolean('is_active')->default(true)->after('status');
        });

        // Fix status enum to include 'logged_out' value (model references it)
        DB::statement("ALTER TABLE user_sessions DROP CONSTRAINT IF EXISTS user_sessions_status_check");
        DB::statement("ALTER TABLE user_sessions ADD CONSTRAINT user_sessions_status_check CHECK (status IN ('active', 'terminated', 'expired', 'logged_out'))");

        // Sync is_active with current status values
        DB::statement("UPDATE user_sessions SET is_active = CASE WHEN status = 'active' THEN true ELSE false END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        DB::statement("ALTER TABLE user_sessions DROP CONSTRAINT IF EXISTS user_sessions_status_check");
        DB::statement("ALTER TABLE user_sessions ADD CONSTRAINT user_sessions_status_check CHECK (status IN ('active', 'terminated', 'expired'))");
    }
};
