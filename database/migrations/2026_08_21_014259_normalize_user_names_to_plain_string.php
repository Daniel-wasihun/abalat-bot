<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration: Convert user.name from JSON {"en":"...", "am":"..."} to a plain string.
 * We extract the English value first, then Amharic, then Oromiffa, then any available value.
 * After this migration, all user names are stored as plain strings.
 */
return new class extends Migration {

    public function up(): void
    {
        $users = DB::table('users')->select('id', 'name')->get();

        foreach ($users as $user) {
            if (!$user->name) continue;

            $plain = $user->name;

            // Attempt to parse as JSON
            $decoded = json_decode($user->name, true);
            if (is_array($decoded)) {
                // Priority: en → am → or → om → first available
                $plain = $decoded['en']
                    ?? $decoded['am']
                    ?? $decoded['or']
                    ?? $decoded['om']
                    ?? array_values(array_filter($decoded))[0]
                    ?? $user->name;
            }

            // Only update if it was JSON (avoid unnecessary writes)
            if ($plain !== $user->name) {
                DB::table('users')->where('id', $user->id)->update(['name' => $plain]);
            }
        }
    }

    public function down(): void
    {
        // Cannot reverse data migration — names are now plain strings
        // You would need a backup to restore the original JSON format
    }
};
