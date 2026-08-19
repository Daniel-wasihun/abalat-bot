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
        Schema::table('senbet_memberships', function (Blueprint $table) {
            $table->enum('work_status', ['student', 'worker'])->default('student')->after('education_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('senbet_memberships', function (Blueprint $table) {
            $table->dropColumn('work_status');
        });
    }
};
