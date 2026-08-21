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
        // Change auditable_id from bigint to varchar(255) to support string primary keys
        // like those used in the Setting model.
        Schema::table('audits', function (Blueprint $table) {
            $table->string('auditable_id', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot safely cast varchar back to bigint if it contains string values.
        // If necessary, this would require cleaning the data.
        Schema::table('audits', function (Blueprint $table) {
            // Using raw SQL with USING clause for Postgres to cast back (dangerous if data contains strings)
            DB::statement('ALTER TABLE audits ALTER COLUMN auditable_id TYPE bigint USING NULLIF(regexp_replace(auditable_id, \'\D\', \'\', \'g\'), \'\')::bigint');
        });
    }
};
