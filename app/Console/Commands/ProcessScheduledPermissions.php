<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Processes scheduled permission grants and revocations.
 *
 * Lifecycle:
 *   1. ACTIVATE — records with is_active=false whose start_date has arrived (and not revoked,
 *                 and whose end_date has not passed yet).
 *      Per (user, permission) pair only the LATEST pending record is activated, and any currently
 *      active record for that same pair is revoked before activation (clean handover).
 *
 *   2. EXPIRE   — records with is_active=true whose end_date has now passed.
 *      Once expired the override is deactivated; the effective permission falls back to
 *      whatever the user's roles grant (no "previous" state to restore for permissions).
 *
 * Granular edge-cases handled:
 *   - Future start_date  → skipped by scheduler / ignored by hasPermission()
 *   - Past end_date      → both expired by scheduler AND ignored by hasPermission()
 *   - Multiple pending   → only the latest-scheduled is activated per pair
 *   - Conflicting active → the old active record is cleanly revoked before new one activates
 */
class ProcessScheduledPermissions extends Command {
    protected $signature   = 'permissions:process-scheduled {--dry-run : Run without making changes}';
    protected $description = 'Process scheduled permission assignment activations and expirations';

    public function handle(): int {
        $isDryRun = $this->option('dry-run');
        $now      = Carbon::now();

        $this->info("Processing scheduled permissions at {$now->toDateTimeString()}");
        if ($isDryRun) {
            $this->warn('DRY RUN MODE — No changes will be made');
        }

        DB::beginTransaction();
        try {
            $this->activateScheduledPermissions($now, $isDryRun);
            $this->expirePermissions($now, $isDryRun);

            if (!$isDryRun) {
                DB::commit();
                $this->info('✓ Permission changes committed');
            } else {
                DB::rollBack();
                $this->info('✓ Dry run completed');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    // -----------------------------------------------------------------------
    // STEP 1 – Activate pending grants/revocations whose start_date has arrived
    // -----------------------------------------------------------------------

    protected function activateScheduledPermissions(Carbon $now, bool $isDryRun): void {
        // All pending (inactive, not-revoked) records whose window has opened and not yet closed.
        $pending = DB::table('user_permission')
            ->where('is_active', false)
            ->whereNull('revoked_at')
            ->where('start_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $now);
            })
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No permissions to activate');
            return;
        }

        // Group by (user_id, permission_id) and process each pair independently.
        $grouped = $pending->groupBy(fn($r) => $r->user_id . '-' . $r->permission_id);

        foreach ($grouped as $key => $records) {
            // Winner = most recently scheduled (latest start_date; break ties by created_at / id)
            $winner = $records
                ->sortByDesc('start_date')
                ->sortByDesc('created_at')
                ->sortByDesc('id')
                ->first();

            $user       = User::find($winner->user_id);
            $permission = DB::table('permissions')->find($winner->permission_id);
            if (!$user || !$permission) {
                continue;
            }

            $action = $winner->granted ? 'Granting' : 'Revoking';
            $this->line("  → {$action} '{$permission->slug}' for user {$user->email}");

            if (!$isDryRun) {
                // Revoke ALL currently active overrides for this (user, permission) pair
                DB::table('user_permission')
                    ->where('user_id', $winner->user_id)
                    ->where('permission_id', $winner->permission_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $winner->id)
                    ->update([
                        'is_active'  => false,
                        'revoked_at' => $now,
                        'updated_at' => $now,
                    ]);

                // Revoke any OTHER pending records that lost the race
                $loserIds = $records->filter(fn($r) => $r->id !== $winner->id)->pluck('id');
                if ($loserIds->isNotEmpty()) {
                    DB::table('user_permission')
                        ->whereIn('id', $loserIds)
                        ->update([
                            'is_active'  => false,
                            'revoked_at' => $now,
                            'updated_at' => $now,
                        ]);
                }

                // Activate the winner
                DB::table('user_permission')
                    ->where('id', $winner->id)
                    ->update(['is_active' => true, 'updated_at' => $now]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // STEP 2 – Expire active overrides whose end_date has passed
    // -----------------------------------------------------------------------

    protected function expirePermissions(Carbon $now, bool $isDryRun): void {
        $expiring = DB::table('user_permission')
            ->where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $now)
            ->get();

        if ($expiring->isEmpty()) {
            $this->info('No permissions to expire');
            return;
        }

        foreach ($expiring as $assignment) {
            $user       = User::find($assignment->user_id);
            $permission = DB::table('permissions')->find($assignment->permission_id);
            if (!$user || !$permission) {
                continue;
            }

            $this->line("  → Expiring override for '{$permission->slug}' for user {$user->email}");

            if (!$isDryRun) {
                DB::table('user_permission')
                    ->where('id', $assignment->id)
                    ->update([
                        'is_active'  => false,
                        'revoked_at' => $now,
                        'updated_at' => $now,
                    ]);
            }
        }
    }
}
