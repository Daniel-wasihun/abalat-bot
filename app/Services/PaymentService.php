<?php

namespace App\Services;

use App\Models\MemberCredit;
use App\Models\MemberCreditApplication;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private SettingRepositoryInterface $settingRepo)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings
    // ─────────────────────────────────────────────────────────────────────────

    public function getSettings(): array
    {
        return [
            'student_amount'          => (float) $this->settingRepo->get('payment.student_amount',           100),
            'worker_amount'           => (float) $this->settingRepo->get('payment.worker_amount',             150),
            'student_fine_per_month'  => (float) $this->settingRepo->get('payment.student_fine_per_month',    10),
            'worker_fine_per_month'   => (float) $this->settingRepo->get('payment.worker_fine_per_month',     20),
            'deadline_type'           =>         $this->settingRepo->get('payment.deadline_type', 'defined_days'),
            'deadline_day'            => (int)   $this->settingRepo->get('payment.deadline_day',              10),
            'minimum_grade_level'     => (int)   $this->settingRepo->get('payment.minimum_grade_level',        7),
            'minimum_age'             => (int)   $this->settingRepo->get('payment.minimum_age',               13),
            'calendar_type'           =>         $this->settingRepo->get('payment.calendar_type', 'ethiopian'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Eligibility
    // ─────────────────────────────────────────────────────────────────────────

    public function exemptReason($membership, array $settings): ?string
    {
        if (! $membership) {
            return 'no_membership';
        }

        $grade    = is_numeric($membership->senbet_class) ? (int) $membership->senbet_class : null;
        $minGrade = $settings['minimum_grade_level'];
        $hasGrade = $grade !== null && $grade > 0;
        $failsGrade = $hasGrade && $grade < $minGrade;

        $age = null;
        if ($membership->date_of_birth) {
            $age = Carbon::parse($membership->date_of_birth)->age;
        }
        $minAge = $settings['minimum_age'];
        $hasAge = $age !== null;
        $failsAge = $hasAge && $age < $minAge;

        // If user fails BOTH criteria, they are exempt
        if ($failsGrade && $failsAge) {
            return "grade_and_age:{$minGrade},{$minAge}";
        }

        // If user only has grade and fails it, they are exempt
        if ($failsGrade && !$hasAge) {
            return "grade:{$minGrade}";
        }

        // If user only has age and fails it, they are exempt
        if ($failsAge && !$hasGrade) {
            return "age:{$minAge}";
        }

        // If they pass at least one criteria (or have no data), they are eligible and must pay
        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fine calculation — separate rates for student/worker
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compute fine for a given obligation period based on work_status.
     * Student and worker fine rates are independent.
     */
    public function computeFine(int $ethYear, int $ethMonth, array $settings, string $workStatus = 'student'): float
    {
        $finePerMonth = $workStatus === 'worker'
            ? (float) $settings['worker_fine_per_month']
            : (float) $settings['student_fine_per_month'];

        $deadlineType = $settings['deadline_type'] ?? 'defined_days';
        $deadlineDaySetting = (int) $settings['deadline_day'];

        // Determine deadline day based on type
        if ($deadlineType === 'end_of_month') {
            if ($ethMonth === 13) {
                $isLeap = ($ethYear % 4 === 3);
                $deadlineDay = $isLeap ? 6 : 5;
            } else {
                $deadlineDay = 30;
            }
        } else {
            // defined_days
            if ($ethMonth === 13) {
                $isLeap = ($ethYear % 4 === 3);
                $maxDays = $isLeap ? 6 : 5;
                $deadlineDay = min($deadlineDaySetting, $maxDays);
            } else {
                $deadlineDay = min($deadlineDaySetting, 30);
            }
        }

        // Current Ethiopian Date
        [$currYear, $currMonth, $currDay] = $this->currentEthiopianDate();

        // Convert both dates to total days from Ethiopian epoch
        $deadlineTotalDays = $this->ethToTotalDays($ethYear, $ethMonth, $deadlineDay);
        $currentTotalDays  = $this->ethToTotalDays($currYear, $currMonth, $currDay);

        if ($currentTotalDays <= $deadlineTotalDays) {
            return 0.0; // Not late
        }

        // Calculate how many months late
        $monthDiff = ($currYear - $ethYear) * 13 + ($currMonth - $ethMonth);
        if ($currDay < $deadlineDay) {
            $monthDiff--; // didn't complete the full month past the deadline day yet
        }
        
        $monthsLate = max(1, $monthDiff);

        return round($monthsLate * $finePerMonth, 2);
    }

    /**
     * Re-apply fine to an existing unpaid obligation (uses its own work_status).
     * Does NOT save — caller must save if needed.
     */
    public function applyFine(Payment $payment, array $settings): void
    {
        if ($payment->for_month === 13) {
            $payment->fine_amount = 0;
            $payment->total_amount_due = 0;
            $payment->status = 'paid';
            return;
        }

        $fine = $this->computeFine(
            (int) $payment->for_year,
            (int) $payment->for_month,
            $settings,
            $payment->work_status ?? 'student'
        );

        $payment->fine_amount     = $fine;
        $payment->total_amount_due = (float) $payment->base_amount + $fine;

        // Also account for credit that was already applied
        $creditApplied = (float) ($payment->creditApplications()->sum('amount_applied') ?? 0);
        $effectivePaid = (float) $payment->amount_paid + $creditApplied;

        if ($effectivePaid >= $payment->total_amount_due) {
            $payment->status = 'paid';
        } elseif ($effectivePaid > 0) {
            $payment->status = 'partial';
        } else {
            $payment->status = $fine > 0 ? 'late' : 'pending';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Obligation — Retrieve or generate
    // ─────────────────────────────────────────────────────────────────────────

    public function isBeforeRegistrationDate(User $user, int $year, int $month): bool
    {
        $membership = $user->senbetMembership;
        if (!$membership || !$membership->registration_date) {
            return false;
        }

        $regCarbon = Carbon::parse($membership->registration_date);
        [$regYear, $regMonth] = $this->gregToEth($regCarbon);

        if ($year < $regYear) {
            return true;
        }
        if ($year === $regYear && $month < $regMonth) {
            return true;
        }

        return false;
    }

    public function getOrGenerateObligation(User $user, int $year, int $month, array $settings): ?Payment
    {
        if ($this->isBeforeRegistrationDate($user, $year, $month)) {
            return null; // A member must only be charged from their registration/start date
        }

        $membership = $user->senbetMembership;
        if (! $membership) {
            return null; // Not a member, no payment obligation.
        }

        $exempt     = $this->exemptReason($membership, $settings);

        // Try to load existing saved obligation
        $payment = $user->payments()
            ->where('for_year', $year)
            ->where('for_month', $month)
            ->first();

        if ($payment) {
            if (! in_array($payment->status, ['paid', 'exempt'])) {
                $this->applyFine($payment, $settings);
                if ($payment->isDirty()) {
                    $payment->save();
                }
            }
            return $payment;
        }

        // Exempt — return unsaved model for display only
        if ($exempt) {
            return $this->unsavedObligation($user->id, $year, $month, $membership?->work_status, 0, 0, 0, 'exempt');
        }

        // Create and persist new obligation
        $workStatus = $membership->work_status ?? 'student';
        
        if ($month === 13) {
            // Pagume (month 13) is automatically free/paid
            $baseAmount = 0.0;
            $fineAmount = 0.0;
            $totalDue   = 0.0;
            $status     = 'paid';
        } else {
            $baseAmount = $this->baseAmount($workStatus, $settings);
            $fineAmount = $this->computeFine($year, $month, $settings, $workStatus);
            $totalDue   = $baseAmount + $fineAmount;
            $status     = $fineAmount > 0 ? 'late' : 'pending';
        }

        $payment = $this->unsavedObligation($user->id, $year, $month, $workStatus, $baseAmount, $fineAmount, $totalDue, $status);
        $payment->save();

        if ($month !== 13 && $totalDue > 0) {
            $this->applyPendingCredits($user->id, $user->id);
            $payment->refresh();
        }

        return $payment;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Record single-month payment (with credit/overpayment handling)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Record a payment transaction. If amount > balance, surplus becomes credit.
     * Credit is then automatically applied to the next unpaid obligations.
     * Returns the created transaction.
     */
    public function recordPayment(
        Payment $payment,
        float   $amount,
        float   $finePaid,
        int     $recordedBy,
        string  $method = 'cash',
        ?string $reference = null,
        bool    $giftSurplus = false
    ): PaymentTransaction {
        return DB::transaction(function () use ($payment, $amount, $finePaid, $recordedBy, $method, $reference, $giftSurplus) {

            // Persist obligation first if new
            if (! $payment->exists) {
                $payment->save();
            }

            // Lock row to prevent concurrent updates
            $locked = Payment::lockForUpdate()->find($payment->id);

            // How much of the payment goes to this obligation vs becomes credit/gift
            $balance     = max(0, (float) $locked->total_amount_due - (float) $locked->amount_paid);
            $appliedHere = min($amount, $balance);
            $surplus     = $amount - $appliedHere;

            // Record the transaction for the full amount paid by the member
            $transaction = PaymentTransaction::create([
                'payment_id'       => $locked->id,
                'amount'           => $amount,
                'fine_paid'        => $finePaid,
                'is_gift'          => $giftSurplus && $surplus > 0.005,
                'payment_method'   => $method,
                'reference_number' => $reference,
                'paid_at'          => now(),
                'recorded_by'      => $recordedBy,
            ]);

            // Recalculate amount_paid from all transactions (source of truth)
            $totalPaid       = PaymentTransaction::where('payment_id', $locked->id)->sum('amount');
            $creditApplied   = (float) MemberCreditApplication::where('payment_id', $locked->id)->sum('amount_applied');
            $effectivePaid   = (float) $totalPaid + $creditApplied;

            $locked->amount_paid = min((float) $totalPaid, (float) $locked->total_amount_due);

            if ($effectivePaid >= $locked->total_amount_due) {
                $locked->status  = 'paid';
                $locked->paid_at = now();
            } elseif ($effectivePaid > 0) {
                $locked->status = 'partial';
            }

            $locked->save();

            // Handle surplus → create gift donation OR credit
            if ($surplus > 0.005) {
                if ($giftSurplus) {
                    \App\Models\SchoolDonation::create([
                        'user_id'     => $locked->user_id,
                        'amount'      => round($surplus, 2),
                        'recorded_by' => $recordedBy,
                        'payment_id'  => $locked->id,
                        'note'        => "Gift/donation surplus on payment for {$locked->for_year}/{$locked->for_month}",
                    ]);
                } else {
                    $credit = MemberCredit::create([
                        'user_id'               => $locked->user_id,
                        'amount'                => round($surplus, 2),
                        'remaining'             => round($surplus, 2),
                        'source_type'           => 'overpayment',
                        'source_payment_id'     => $locked->id,
                        'source_transaction_id' => $transaction->id,
                        'created_by'            => $recordedBy,
                        'note'                  => "Overpayment on {$locked->for_year}/{$locked->for_month}",
                    ]);

                    $this->applyPendingCredits($locked->user_id, $recordedBy);
                }
            }

            return $transaction;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Bulk multi-month payment
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pay for multiple consecutive months at once.
     * Returns breakdown of how the amount was distributed across months.
     */
    public function recordBulkPayment(
        User    $user,
        int     $fromYear,
        int     $fromMonth,
        int     $numMonths,  // 1, 3, 6, or 12
        float   $totalAmount,
        int     $recordedBy,
        string  $method = 'cash',
        ?string $reference = null,
        bool    $giftSurplus = false
    ): array {
        return DB::transaction(function () use ($user, $fromYear, $fromMonth, $numMonths, $totalAmount, $recordedBy, $method, $reference, $giftSurplus) {
            $settings   = $this->getSettings();
            $remaining  = $totalAmount;
            $breakdown  = [];
            $months     = $this->consecutiveMonths($fromYear, $fromMonth, $numMonths);
            $lastTransaction = null;
            $lastObligation = null;

            foreach ($months as [$year, $month]) {
                $obligation = $this->getOrGenerateObligation($user, $year, $month, $settings);

                if (! $obligation || $obligation->status === 'exempt') {
                    $breakdown[] = $this->breakdownRow($year, $month, $obligation, 0, 'exempt');
                    continue;
                }

                if ($remaining <= 0.005) {
                    $breakdown[] = $this->breakdownRow($year, $month, $obligation, 0);
                    continue;
                }

                // Lock obligation
                if ($obligation->exists) {
                    $obligation = Payment::lockForUpdate()->find($obligation->id);
                }

                $balance     = max(0, (float) $obligation->total_amount_due - (float) $obligation->amount_paid);
                $appliedHere = min($remaining, $balance);
                $surplus     = $remaining - $appliedHere;

                // Calculate fine paid
                $previouslyPaidFine = (float) PaymentTransaction::where('payment_id', $obligation->id)->sum('fine_paid');
                $fineRemaining      = max(0, (float) $obligation->fine_amount - $previouslyPaidFine);
                $finePaidHere       = min($appliedHere, $fineRemaining);

                // Record transaction for this month
                $lastTransaction = PaymentTransaction::create([
                    'payment_id'       => $obligation->id,
                    'amount'           => $appliedHere,
                    'fine_paid'        => $finePaidHere,
                    'is_gift'          => false,
                    'payment_method'   => $method,
                    'reference_number' => $reference,
                    'paid_at'          => now(),
                    'recorded_by'      => $recordedBy,
                ]);

                $lastObligation = $obligation;

                $totalPaid       = PaymentTransaction::where('payment_id', $obligation->id)->sum('amount');
                $creditApplied   = (float) MemberCreditApplication::where('payment_id', $obligation->id)->sum('amount_applied');
                $effectivePaid   = (float) $totalPaid + $creditApplied;

                $obligation->amount_paid = min((float) $totalPaid, (float) $obligation->total_amount_due);

                if ($effectivePaid >= $obligation->total_amount_due) {
                    $obligation->status  = 'paid';
                    $obligation->paid_at = now();
                } elseif ($effectivePaid > 0) {
                    $obligation->status = 'partial';
                }

                $obligation->save();

                $breakdown[] = $this->breakdownRow($year, $month, $obligation, $appliedHere);
                $remaining   = round($surplus, 2);
            }

            // If there is still a surplus after all months, create donation OR credit
            if ($remaining > 0.005) {
                // Attach the surplus to the last recorded transaction so the exact amount paid is stored
                if ($lastTransaction) {
                    $lastTransaction->amount += $remaining;
                    $lastTransaction->is_gift = $giftSurplus;
                    $lastTransaction->save();
                }

                if ($giftSurplus) {
                    \App\Models\SchoolDonation::create([
                        'user_id'     => $user->id,
                        'amount'      => round($remaining, 2),
                        'recorded_by' => $recordedBy,
                        'payment_id'  => $lastObligation?->id,
                        'note'        => "Gift/donation surplus on bulk payment ({$fromYear}/{$fromMonth}, {$numMonths} months)",
                    ]);
                } else {
                    $credit = MemberCredit::create([
                        'user_id'            => $user->id,
                        'amount'             => round($remaining, 2),
                        'remaining'          => round($remaining, 2),
                        'source_type'        => 'overpayment',
                        'source_payment_id'  => $lastObligation?->id,
                        'created_by'         => $recordedBy,
                        'note'               => "Bulk overpayment surplus ({$fromYear}/{$fromMonth}, {$numMonths} months)",
                    ]);

                    $this->applyPendingCredits($user->id, $recordedBy);
                }
            }

            return [
                'breakdown'       => $breakdown,
                'total_applied'   => round($totalAmount - $remaining, 2),
                'surplus_credit'  => round(max(0, $remaining), 2),
            ];
        });
    }

    /**
     * Preview a bulk payment breakdown WITHOUT writing anything.
     */
    public function previewBulkPayment(User $user, int $fromYear, int $fromMonth, int $numMonths, float $totalAmount): array
    {
        $settings  = $this->getSettings();
        $remaining = $totalAmount;
        $rows      = [];
        $months    = $this->consecutiveMonths($fromYear, $fromMonth, $numMonths);

        foreach ($months as [$year, $month]) {
            $obligation = $this->getOrGenerateObligation($user, $year, $month, $settings);

            $base   = $obligation ? (float) $obligation->base_amount       : 0;
            $fine   = $obligation ? (float) $obligation->fine_amount        : 0;
            $total  = $obligation ? (float) $obligation->total_amount_due   : 0;
            $paid   = $obligation ? (float) $obligation->amount_paid        : 0;
            $bal    = max(0, $total - $paid);
            $status = $obligation ? $obligation->status                     : 'pending';

            if ($status === 'exempt') {
                $rows[] = ['year' => $year, 'month' => $month, 'base' => 0, 'fine' => 0, 'total_due' => 0, 'already_paid' => 0, 'balance' => 0, 'amount_applied' => 0, 'status' => 'exempt'];
                continue;
            }

            $applied    = $remaining > 0 ? min($remaining, $bal) : 0;
            $remaining  = round($remaining - $applied, 2);

            $rows[] = [
                'year'          => $year,
                'month'         => $month,
                'base'          => $base,
                'fine'          => $fine,
                'total_due'     => $total,
                'already_paid'  => $paid,
                'balance'       => $bal,
                'amount_applied'=> $applied,
                'status'        => $status,
            ];
        }

        return [
            'rows'           => $rows,
            'total_applied'  => round($totalAmount - max(0, $remaining), 2),
            'surplus_credit' => round(max(0, $remaining), 2),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Credit application
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apply any available credit for a user to their oldest unpaid obligations.
     * Call after any payment or credit creation event.
     */
    public function applyPendingCredits(int $userId, int $appliedBy): void
    {
        $credits = MemberCredit::where('user_id', $userId)
            ->where('remaining', '>', 0)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        if ($credits->isEmpty()) {
            return;
        }

        $unpaid = Payment::where('user_id', $userId)
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->orderBy('for_year')
            ->orderBy('for_month')
            ->lockForUpdate()
            ->get();

        foreach ($unpaid as $obligation) {
            $balance = max(0, (float) $obligation->total_amount_due - (float) $obligation->amount_paid
                - (float) MemberCreditApplication::where('payment_id', $obligation->id)->sum('amount_applied'));

            if ($balance <= 0.005) {
                continue;
            }

            foreach ($credits as $credit) {
                if ($credit->remaining <= 0.005) {
                    continue;
                }

                $apply = min($credit->remaining, $balance);

                MemberCreditApplication::create([
                    'credit_id'      => $credit->id,
                    'payment_id'     => $obligation->id,
                    'amount_applied' => round($apply, 2),
                ]);

                $credit->remaining = round($credit->remaining - $apply, 2);
                $credit->save();
                $balance -= $apply;

                // Recalculate obligation status
                $totalPaid     = PaymentTransaction::where('payment_id', $obligation->id)->sum('amount');
                $totalCredit   = MemberCreditApplication::where('payment_id', $obligation->id)->sum('amount_applied');
                $effectivePaid = (float) $totalPaid + (float) $totalCredit;

                if ($effectivePaid >= (float) $obligation->total_amount_due) {
                    $obligation->status  = 'paid';
                    $obligation->paid_at = now();
                } elseif ($effectivePaid > 0) {
                    $obligation->status = 'partial';
                }

                $obligation->save();

                if ($balance <= 0.005) {
                    break;
                }
            }
        }
    }

    /**
     * Return available credit total for a user.
     */
    public function availableCredit(int $userId): float
    {
        return (float) MemberCredit::where('user_id', $userId)
            ->where('remaining', '>', 0)
            ->sum('remaining');
    }

    public function ensureObligationsGenerated(int $year, int $month): void
    {
        $settings = $this->getSettings();
        $users = User::whereHas('senbetMembership', function ($q) {
            $q->whereNull('deleted_at');
        })->get();

        foreach ($users as $user) {
            if ($this->isBeforeRegistrationDate($user, $year, $month)) {
                $user->payments()->where('for_year', $year)->where('for_month', $month)->delete();
                continue;
            }
            $this->getOrGenerateObligation($user, $year, $month, $settings);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Statistics
    // ─────────────────────────────────────────────────────────────────────────

    public function getStatistics(int $year, int $month): array
    {
        $this->ensureObligationsGenerated($year, $month);

        $base = Payment::where('for_year', $year)
            ->where('for_month', $month)
            ->whereHas('user.senbetMembership', function($q) {
                $q->whereNull('deleted_at');
            });

        $statusCounts = (clone $base)->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totals = (clone $base)->select([
            DB::raw('COALESCE(SUM(amount_paid), 0) as collected'),
            DB::raw('COALESCE(SUM(GREATEST(total_amount_due - amount_paid, 0)), 0) as outstanding'),
            DB::raw('COALESCE(SUM(fine_amount), 0) as fines'),
        ])->first();

        // Member credit aggregations
        $totalCreditsHeld = (float) MemberCredit::where('remaining', '>', 0)->sum('remaining');
        $totalCreditsUsed = (float) MemberCreditApplication::sum('amount_applied');
        $membersWithCreditCount = MemberCredit::where('remaining', '>', 0)->distinct('user_id')->count('user_id');

        // School financial statistics
        $totalDonations = (float) \App\Models\SchoolDonation::sum('amount');
        $totalPaymentIncome = (float) PaymentTransaction::sum('amount');
        $totalSchoolAssets = $totalPaymentIncome + $totalDonations;

        return [
            // Member statistics
            'eligible'               => array_sum(array_filter($statusCounts, fn($k) => $k !== 'exempt', ARRAY_FILTER_USE_KEY)),
            'paid'                   => $statusCounts['paid']    ?? 0,
            'pending'                => $statusCounts['pending'] ?? 0,
            'partial'                => $statusCounts['partial'] ?? 0,
            'late'                   => $statusCounts['late']    ?? 0,
            'exempt'                 => $statusCounts['exempt']  ?? 0,
            'collected'              => round((float) $totals->collected,   2),
            'outstanding'            => round((float) $totals->outstanding, 2),
            'fines'                  => round((float) $totals->fines,       2),
            'total_credits_held'     => round($totalCreditsHeld, 2),
            'total_credits_used'     => round($totalCreditsUsed, 2),
            'members_with_credit'    => $membersWithCreditCount,

            // School financial statistics
            'total_donations'        => round($totalDonations, 2),
            'total_payment_income'   => round($totalPaymentIncome, 2),
            'total_school_assets'    => round($totalSchoolAssets, 2),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function baseAmount(?string $workStatus, array $settings): float
    {
        return $workStatus === 'worker'
            ? $settings['worker_amount']
            : $settings['student_amount'];
    }

    public function currentEthiopianDate(): array
    {
        $gregorian = Carbon::now();
        $jdn = cal_to_jd(CAL_GREGORIAN, $gregorian->month, $gregorian->day, $gregorian->year);
        $jdnEth = $jdn - 1723856;
        $r = $jdnEth % 1461;
        $n = ($r % 365) + 365 * (int)($r / 1460);
        $ethYear = 4 * (int)($jdnEth / 1461) + (int)($r / 365) - (int)($r / 1460);
        $ethMonth = (int)($n / 30) + 1;
        $ethDay = ($n % 30) + 1;
        return [(int)$ethYear, (int)$ethMonth, (int)$ethDay];
    }

    public function ethToTotalDays(int $year, int $month, int $day): int
    {
        $yearsPassed = $year - 1;
        $leapYearsPassed = (int)(($yearsPassed + 1) / 4);
        return $yearsPassed * 365 + $leapYearsPassed + ($month - 1) * 30 + $day;
    }

    /**
     * Generate a list of consecutive Ethiopian [year, month] pairs starting from fromYear/fromMonth.
     */
    private function consecutiveMonths(int $fromYear, int $fromMonth, int $count): array
    {
        $result = [];
        $year   = $fromYear;
        $month  = $fromMonth;

        for ($i = 0; $i < $count; ) {
            $result[] = [$year, $month];
            
            if ($month !== 13) {
                $i++;
            }
            
            $month++;
            if ($month > 13) {
                $month = 1;
                $year++;
            }
        }

        return $result;
    }

    private function breakdownRow(int $year, int $month, ?Payment $obligation, float $applied, string $forceStatus = ''): array
    {
        return [
            'year'           => $year,
            'month'          => $month,
            'base_amount'    => $obligation ? (float) $obligation->base_amount       : 0,
            'fine_amount'    => $obligation ? (float) $obligation->fine_amount        : 0,
            'total_due'      => $obligation ? (float) $obligation->total_amount_due   : 0,
            'amount_applied' => $applied,
            'status'         => $forceStatus ?: ($obligation ? $obligation->status : 'pending'),
            'payment_id'     => $obligation?->id,
        ];
    }

    private function unsavedObligation(
        int     $userId,
        int     $year,
        int     $month,
        ?string $workStatus,
        float   $baseAmount,
        float   $fineAmount,
        float   $totalDue,
        string  $status
    ): Payment {
        return new Payment([
            'user_id'          => $userId,
            'for_year'         => $year,
            'for_month'        => $month,
            'work_status'      => $workStatus,
            'base_amount'      => $baseAmount,
            'fine_amount'      => $fineAmount,
            'total_amount_due' => $totalDue,
            'amount_paid'      => 0,
            'status'           => $status,
        ]);
    }
    public function gregToEth(Carbon $date): array
    {
        $jdn = cal_to_jd(CAL_GREGORIAN, $date->month, $date->day, $date->year);
        $jdnEth = $jdn - 1723856;
        $r = $jdnEth % 1461;
        $n = ($r % 365) + 365 * (int)($r / 1460);
        $ethYear = 4 * (int)($jdnEth / 1461) + (int)($r / 365) - (int)($r / 1460);
        $ethMonth = (int)($n / 30) + 1;
        $ethDay = ($n % 30) + 1;
        return [(int)$ethYear, (int)$ethMonth, (int)$ethDay];
    }
}
