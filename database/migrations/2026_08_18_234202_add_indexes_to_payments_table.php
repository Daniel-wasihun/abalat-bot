<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Prevent duplicate monthly obligations for the same member
            $table->unique(['user_id', 'for_year', 'for_month'], 'payments_user_year_month_unique');

            // Speed up common dashboard queries
            $table->index(['for_year', 'for_month'], 'payments_period_index');
            $table->index('status', 'payments_status_index');
            $table->index(['user_id', 'status'], 'payments_user_status_index');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index('payment_id', 'payment_transactions_payment_index');
            $table->index('paid_at', 'payment_transactions_paid_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_user_year_month_unique');
            $table->dropIndex('payments_period_index');
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_user_status_index');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_transactions_payment_index');
            $table->dropIndex('payment_transactions_paid_at_index');
        });
    }
};
