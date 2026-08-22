<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class AuditLogController extends Controller
{
    /**
     * List all audit logs with optional filtering.
     * Exposes rollback state for each record.
     */
    public function index(Request $request)
    {
        $query = Audit::with(['user', 'user.info', 'auditable']);

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->query('auditable_type'));
        }
        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->query('auditable_id'));
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }
        if ($request->has('event')) {
            $query->where('event', $request->query('event'));
        }

        $query->orderBy('created_at', 'desc');

        $audits = $query->paginate($request->query('per_page', 10));

        $formatted = $audits->through(function ($audit) {
            return $this->formatAudit($audit);
        });

        return response()->json($formatted);
    }

    /**
     * Rollback a specific audit record.
     *
     * Rules:
     * - Cannot rollback 'created' events.
     * - Cannot rollback an audit that has already been rolled back.
     * - Rolling back a 'deleted' event restores the record (soft or hard).
     * - Rolling back a 'restored' / 'updated' event re-applies the old state.
     * - The rollback itself is recorded as a new audit entry with is_rollback=true.
     * - The source audit is marked as rolled_back_at = now() + rolled_back_by_audit_id.
     */
    public function rollback($id)
    {
        $audit = Audit::findOrFail($id);

        // Cannot rollback 'created' events
        if ($audit->event === 'created') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot rollback a creation event. Please delete the record manually if needed.',
            ], 400);
        }

        // Cannot rollback an audit that has already been rolled back
        if ($audit->rolled_back_at !== null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This action has already been rolled back and cannot be reversed again.',
            ], 409);
        }

        $modelClass = $audit->auditable_type;
        $model = null;

        // Support soft-deletes
        $usesSoftDeletes = in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($modelClass)
        );

        if ($usesSoftDeletes) {
            $model = $modelClass::withTrashed()->find($audit->auditable_id);
        } else {
            $model = $modelClass::find($audit->auditable_id);
        }

        try {
            DB::transaction(function () use ($audit, $model, $modelClass, $usesSoftDeletes) {
                $latestAuditBefore = Audit::where('auditable_type', $audit->auditable_type)
                                          ->where('auditable_id', $audit->auditable_id)
                                          ->orderBy('id', 'desc')
                                          ->first();
                $latestAuditIdBefore = $latestAuditBefore ? $latestAuditBefore->id : 0;

                // ---- Case 1: Rolling back a DELETED event (restore) ----
                if ($audit->event === 'deleted') {
                    if ($model) {
                        // Soft-deleted: just restore
                        if ($usesSoftDeletes && $model->trashed()) {
                            $model->restore();
                        }
                    } else {
                        // Hard-deleted: recreate the record from old_values
                        $modelClass::unguard();
                        $instance = new $modelClass();
                        $instance->setRawAttributes($audit->old_values ?? []);
                        $keyName = $instance->getKeyName();
                        if (!$instance->getAttribute($keyName)) {
                            $instance->setAttribute($keyName, $audit->auditable_id);
                        }
                        $modelClass::insert($instance->getAttributes());
                        $modelClass::reguard();
                        $model = $modelClass::find($audit->auditable_id);
                    }
                }

                // ---- Case 2: Rolling back a RESTORED event (re-delete) ----
                elseif ($audit->event === 'restored') {
                    if ($model && $usesSoftDeletes && !$model->trashed()) {
                        $model->delete();
                    }
                }

                // ---- Case 3: Rolling back an UPDATED event (revert attributes) ----
                elseif ($audit->event === 'updated' && $model) {
                    // Fix auditable_id type mismatch (varchar vs int)
                    if (is_int($model->getKey())) {
                        $audit->auditable_id = (int) $audit->auditable_id;
                    }
                    $model->transitionTo($audit, true);
                    $model->save();
                }

                // ---- Find automatically generated rollback audit record ----
                $rollbackAudit = Audit::where('auditable_type', $audit->auditable_type)
                                      ->where('auditable_id', $audit->auditable_id)
                                      ->where('id', '>', $latestAuditIdBefore)
                                      ->orderBy('id', 'asc')
                                      ->first();

                if (!$rollbackAudit) {
                    // Fallback: manually create one if auditing was bypassed
                    $rollbackEvent = match ($audit->event) {
                        'deleted'  => 'restored',
                        'restored' => 'deleted',
                        default    => 'updated',
                    };

                    $rollbackAudit = new Audit();
                    $rollbackAudit->forceFill([
                        'user_type'       => $audit->user_type,
                        'user_id'         => Auth::id() ?? $audit->user_id,
                        'event'           => $rollbackEvent,
                        'auditable_type'  => $audit->auditable_type,
                        'auditable_id'    => $audit->auditable_id,
                        'old_values'      => $audit->new_values,
                        'new_values'      => $audit->old_values,
                        'url'             => request()->fullUrl(),
                        'ip_address'      => request()->ip(),
                        'user_agent'      => request()->userAgent(),
                        'tags'            => null,
                        'is_rollback'     => true,
                        'source_audit_id' => $audit->id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    $rollbackAudit->save();
                } else {
                    // Update the automatically generated audit with rollback flags
                    $rollbackAudit->forceFill([
                        'is_rollback'     => true,
                        'source_audit_id' => $audit->id,
                    ])->save();
                }

                // ---- Mark the original audit as rolled back ----
                $audit->forceFill([
                    'rolled_back_at'       => now(),
                    'rolled_back_by_audit_id' => $rollbackAudit->id,
                ])->save();
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Record successfully rolled back.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to rollback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format a single audit record for the frontend.
     */
    protected function formatAudit(Audit $audit): array
    {
        $causerName = 'System';
        $causerId = null;
        $causerRegistrationId = null;

        if ($audit->user) {
            $user = $audit->user;
            $causerId = $user->id;
            
            $nameParts = array_filter([
                $user->name,
                $user->info->father_name ?? null,
                $user->info->grandfather_name ?? null,
            ]);
            $causerName = !empty($nameParts) ? implode(' ', $nameParts) : 'System';
            $causerRegistrationId = $user->info->registration_id ?? null;
        }

        // The new columns come back as raw DB values (not auto-cast by the package model).
        // Parse rolled_back_at safely.
        $rolledBackAt = $audit->getRawOriginal('rolled_back_at');
        $rolledBackAtIso = $rolledBackAt ? Carbon::parse($rolledBackAt)->toIso8601String() : null;

        // is_rollback is stored as boolean/int in the DB
        $isRollback = (bool) $audit->getRawOriginal('is_rollback');
        $isRolledBack = $rolledBackAt !== null;

        $oldValues = $audit->old_values ?? [];
        $newValues = $audit->new_values ?? [];

        $resourceRegistrationId = null;

        // Strip internal IDs from being exposed
        $stripKeys = ['id', 'user_id', 'payment_id', 'recorded_by', 'created_by', 'source_payment_id', 'source_transaction_id'];
        foreach ($stripKeys as $k) {
            unset($oldValues[$k]);
            unset($newValues[$k]);
        }

        // If the auditable is a User, append the father and grandfather names to the 'name' property
        if ($audit->auditable_type === \App\Models\User::class) {
            $auditableUser = $audit->auditable ?? \App\Models\User::withTrashed()->find($audit->auditable_id);
            if ($auditableUser) {
                $auditableInfo = $auditableUser->info;
                if ($auditableInfo) {
                    $resourceRegistrationId = $auditableInfo->registration_id;
                    $suffix = trim(($auditableInfo->father_name ?? '') . ' ' . ($auditableInfo->grandfather_name ?? ''));
                    if ($suffix) {
                        if (isset($oldValues['name'])) {
                            $oldValues['name'] = $oldValues['name'] . ' ' . $suffix;
                        }
                        if (isset($newValues['name'])) {
                            $newValues['name'] = $newValues['name'] . ' ' . $suffix;
                        }
                    }
                }
            }
        } elseif ($audit->auditable_type === \App\Models\PaymentTransaction::class) {
            $transaction = $audit->auditable ?? \App\Models\PaymentTransaction::withTrashed()->find($audit->auditable_id);
            if ($transaction && $transaction->payment && $transaction->payment->user) {
                $user = $transaction->payment->user;
                $resourceRegistrationId = $user->info?->registration_id;
                $newValues['payer_name'] = trim($user->name . ' ' . ($user->info?->father_name ?? ''));
                $newValues['payment_period'] = $transaction->payment->for_month . '/' . $transaction->payment->for_year;
            }
        } elseif ($audit->auditable_type === \App\Models\MemberCredit::class) {
            $credit = $audit->auditable ?? \App\Models\MemberCredit::withTrashed()->find($audit->auditable_id);
            if ($credit && $credit->user) {
                $user = $credit->user;
                $resourceRegistrationId = $user->info?->registration_id;
                $newValues['member_name'] = trim($user->name . ' ' . ($user->info?->father_name ?? ''));
            }
        } elseif ($audit->auditable_type === \App\Models\SchoolDonation::class) {
            $donation = $audit->auditable ?? \App\Models\SchoolDonation::withTrashed()->find($audit->auditable_id);
            if ($donation && $donation->user) {
                $user = $donation->user;
                $resourceRegistrationId = $user->info?->registration_id;
                $newValues['donor_name'] = trim($user->name . ' ' . ($user->info?->father_name ?? ''));
            }
        }

        return [
            'id'                       => $audit->id,
            'event'                    => $audit->event,
            'model_type'               => $audit->auditable_type,
            'model_id'                 => $audit->auditable_id,
            'resource_registration_id' => $resourceRegistrationId,
            'causer_id'                => $causerId,
            'causer_name'              => $causerName,
            'causer_registration_id'   => $causerRegistrationId,
            'old_values'               => $oldValues,
            'new_values'               => $newValues,
            'ip_address'               => $audit->ip_address,
            'created_at'               => $audit->created_at?->toIso8601String(),
            // Rollback state
            'is_rolled_back'           => $isRolledBack,
            'rolled_back_at'           => $rolledBackAtIso,
            'rolled_back_by_audit_id'  => $audit->getRawOriginal('rolled_back_by_audit_id'),
            'is_rollback'              => $isRollback,
            'source_audit_id'          => $audit->getRawOriginal('source_audit_id'),
            // Whether a rollback button should be shown
            'can_rollback'             => $this->canRollback($audit, $isRolledBack, $isRollback),
        ];
    }

    /**
     * Determine if an audit record can currently be rolled back.
     */
    protected function canRollback(Audit $audit, bool $isRolledBack, bool $isRollback): bool
    {
        if ($audit->event === 'created') {
            return false;
        }
        if ($isRolledBack) {
            return false;
        }
        return true;
    }
}
