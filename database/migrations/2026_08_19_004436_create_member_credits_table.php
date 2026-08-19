<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Credit amount and how much is still available
            $table->decimal('amount', 10, 2);
            $table->decimal('remaining', 10, 2);

            // Where the credit came from
            $table->string('source_type')->default('overpayment'); // overpayment | manual
            $table->foreignId('source_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('source_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Every time a credit is applied to reduce an obligation, record it here
        Schema::create('member_credit_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained('member_credits')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->decimal('amount_applied', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_credit_applications');
        Schema::dropIfExists('member_credits');
    }
};
