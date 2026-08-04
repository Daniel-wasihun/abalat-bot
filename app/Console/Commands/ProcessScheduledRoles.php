<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Processes scheduled role assignments and expirations.
 *
 * Lifecycle:
 *   1. ACTIVATE  — records with is_active=false whose start_date has passed (and not revoked).
 *   2. EXPIRE    — records with is_active=true whose end_date has passed.
 *   3. REVERT    — when a temporary role expires, restore the most recent permanent baseline role.
 *
 * A "winner" for activation is the most-recently-starting pending assignment, chosen per user.
 * Once the winner is found, all currently-active role records for that user are revoked before
 * the winner is activated, giving a clean single-active-role guarantee.
 */
class ProcessScheduledRoles extends Command {
    protected $signature   = 'roles:process-scheduled {--dry-run : Run without making changes}';
    protected $description = 'Process scheduled role assignments and expirations with precise temporal enforcement';

    public function handle(): int {
        $isDryRun = $this->option('dry-run');
        $now      = Carbon::now();

        $this->info("Processing scheduled roles at {$now->toDateTimeString()}");
        if ($isDryRun) {
            $this->warn('DRY RUN MODE — No changes will be made');
        }

        DB::beginTransaction();
        try {
            $this->activateScheduledRoles($now, $isDryRun);
            $this->expireRoles($now, $isDryRun);

            if (!$isDryRun) {
                DB::commit();
                $this->info('✓ Role changes committed');
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
    // STEP 1 – Activate pending assignments whose start_date has arrived
    // -----------------------------------------------------------------------

    protected function activateScheduledRoles(Carbon $now, bool $isDryRun): void {
        // Find every user who has at least one pending (inactive, not revoked) role
        // whose window has opened (start_date <= now) and has not yet closed (end_date > now OR null).
        $usersWithPending = DB::table('user_role')
            ->where('is_active', false)
            ->whereNull('revoked_at')
            ->where('start_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $now);
            })
            ->pluck('user_id')
            ->unique();

        if ($usersWithPending->isEmpty()) {
            $this->info('No roles to activate');
            return;
        }

        foreach ($usersWithPending as $userId) {
            // Pick the LATEST pending assignment for this user (most recently scheduled wins).
            $winner = DB::table('user_role')
                ->where('user_id', $userId)
                ->where('is_active', false)
                ->whereNull('revoked_at')
                ->where('start_date', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>', $now);
                })
                ->orderBy('start_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if (!$winner) {
                continue;
            }

            $user = User::find($userId);
            $role = DB::table('roles')->find($winner->role_id);
            if (!$user || !$role) {
                continue;
            }

            $roleName = is_array($role->name) ? ($role->name['en'] ?? $role->name) : json_decode($role->name, true)['en'] ?? $role->name;
            $this->line("  → Activating '{$roleName}' ({$role->slug}) for user {$user->email}");

            if (!$isDryRun) {
                // Revoke all currently-active role records for this user
                DB::table('user_role')
                    ->where('user_id', $userId)
                    ->where('is_active', true)
                    ->where('id', '!=', $winner->id)
                    ->update([
                        'is_active'  => false,
                        'revoked_by' => $winner->assigned_by,
                        'revoked_at' => $now,
                        'updated_at' => $now,
                    ]);

                // Activate the winner
                DB::table('user_role')
                    ->where('id', $winner->id)
                    ->update(['is_active' => true, 'updated_at' => $now]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // STEP 2 – Expire active assignments whose end_date has passed
    // -----------------------------------------------------------------------

    protected function expireRoles(Carbon $now, bool $isDryRun): void {
        $expiring = DB::table('user_role')
            ->where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $now)
            ->get();

        if ($expiring->isEmpty()) {
            $this->info('No roles to expire');
            return;
        }

        foreach ($expiring as $assignment) {
            $user = User::find($assignment->user_id);
            $role = DB::table('roles')->find($assignment->role_id);
            if (!$user || !$role) {
                continue;
            }

            $roleName = is_array($role->name) ? ($role->name['en'] ?? $role->name) : json_decode($role->name, true)['en'] ?? $role->name;
            $this->line("  → Expiring '{$roleName}' ({$role->slug}) for user {$user->email}");

            if (!$isDryRun) {
                DB::table('user_role')
                    ->where('id', $assignment->id)
                    ->update(['is_active' => false, 'revoked_at' => $now, 'updated_at' => $now]);

                $this->revertToPreviousRole($user, $assignment, $now);
            }
        }
    }

    // -----------------------------------------------------------------------
    // STEP 3 – Revert to the most recent permanent (no end_date) baseline role
    // -----------------------------------------------------------------------

    protected function revertToPreviousRole(User $user, object $expired, Carbon $now): void {
        // A baseline role is older than the expired record, still not revoked, and has no end_date.
        $prev = DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('id', '!=', $expired->id)
            ->whereNull('revoked_at')
            ->where(function ($q) use ($expired) {
                $q->where('start_date', '<', $expired->start_date)
                    ->orWhereNull('start_date');
            })
            ->whereNull('end_date')
            ->orderByRaw('start_date DESC NULLS LAST')
            ->orderBy('id', 'desc')
            ->first();

        if ($prev) {
            $role = DB::table('roles')->find($prev->role_id);
            $roleName = $role ? (is_array($role->name) ? ($role->name['en'] ?? '') : (json_decode($role->name, true)['en'] ?? '')) : '';
            $this->line("    ↳ Reverting to previous role: {$roleName}");
            DB::table('user_role')
                ->where('id', $prev->id)
                ->update(['is_active' => true, 'updated_at' => $now]);
        } else {
            $this->line("    ↳ No baseline role to revert to");
        }
    }
}
