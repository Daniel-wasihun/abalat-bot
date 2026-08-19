<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename existing columns or add new ones in payments (Obligations)
        Schema::table('payments', function (Blueprint $table) {
            $table->string('work_status')->nullable()->after('for_month');
            $table->decimal('base_amount', 10, 2)->default(0)->after('work_status');
            $table->decimal('fine_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('total_amount_due', 10, 2)->default(0)->after('fine_amount');
            $table->date('due_date')->nullable()->after('amount_paid');
            $table->enum('status', ['pending', 'partial', 'paid', 'late', 'exempt'])->default('pending')->after('due_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('paid_at')->nullable()->change();
        });

        // 2. Data Migration: Populate new columns based on existing data safely
        DB::table('payments')->update([
            'work_status' => DB::raw("(SELECT sm.work_status FROM senbet_memberships sm WHERE sm.user_id = payments.user_id LIMIT 1)"),
            'base_amount' => DB::raw("amount_paid"),
            'fine_amount' => DB::raw("fine_paid"),
            'total_amount_due' => DB::raw("amount_paid + fine_paid"),
            'status' => 'paid'
        ]);

        // 3. Drop fine_paid column from payments
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('fine_paid');
        });

        // 4. Create Payment Transactions table
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('fine_paid', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->dateTime('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Migrate existing payments into transactions
        $existingPayments = DB::table('payments')->where('amount_paid', '>', 0)->get();
        $transactions = [];
        foreach ($existingPayments as $p) {
            $transactions[] = [
                'payment_id' => $p->id,
                'amount' => $p->amount_paid,
                'fine_paid' => $p->fine_amount, // from the previous update
                'payment_method' => 'cash',
                'paid_at' => $p->paid_at ?: now(),
                'recorded_by' => $p->recorded_by,
                'created_at' => $p->created_at,
                'updated_at' => $p->updated_at,
            ];
        }
        if (!empty($transactions)) {
            DB::table('payment_transactions')->insert($transactions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('fine_paid', 10, 2)->default(0);
        });

        DB::table('payments')->update([
            'fine_paid' => DB::raw('fine_amount')
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'work_status',
                'base_amount',
                'fine_amount',
                'total_amount_due',
                'due_date',
                'status',
                'recorded_by'
            ]);
            $table->timestamp('paid_at')->nullable(false)->change();
        });
    }
};
