<?php

namespace App\Http\Controllers;

use App\Models\MemberCredit;
use App\Models\MemberCreditApplication;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/payments
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $request->validate([
            'year'           => 'sometimes|integer|min:2000|max:2100',
            'month'          => 'sometimes|integer|min:1|max:13',
            'search'         => 'sometimes|string|max:100',
            'work_status'    => 'sometimes|in:student,worker,all',
            'payment_status' => 'sometimes|in:paid,pending,partial,late,exempt,all',
            'grade'          => 'sometimes|string|max:20',
            'age_min'        => 'sometimes|integer|min:0',
            'age_max'        => 'sometimes|integer|min:0',
            'per_page'       => 'sometimes|integer|min:5|max:200',
        ]);

        $settings = $this->paymentService->getSettings();
        $year     = (int) $request->input('year',    2017);
        $month    = (int) $request->input('month',   1);
        $perPage  = (int) $request->input('per_page', 25);

        $query = User::query()
            ->with(['info', 'senbetMembership'])
            ->whereHas('senbetMembership', function ($q) {
                $q->whereNull('deleted_at');
            });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("name::text"), 'ilike', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($grade = $request->input('grade')) {
            $query->whereHas('senbetMembership', fn($q) => $q->where('senbet_class', $grade));
        }

        if (($ws = $request->input('work_status')) && $ws !== 'all') {
            $query->whereHas('senbetMembership', fn($q) => $q->where('work_status', $ws));
        }

        $ageMin = $request->input('age_min');
        $ageMax = $request->input('age_max');

        $users = $query->get();

        $rows = $users->map(function (User $user) use ($year, $month, $settings, $ageMin, $ageMax) {
            $membership = $user->senbetMembership;
            $age = $membership?->date_of_birth
                ? Carbon::parse($membership->date_of_birth)->age
                : null;

            if ($ageMin !== null && $age !== null && $age < (int) $ageMin) return null;
            if ($ageMax !== null && $age !== null && $age > (int) $ageMax) return null;

            $obligation = $this->paymentService->getOrGenerateObligation($user, $year, $month, $settings);
            if (! $obligation) return null;

            $credit = $this->paymentService->availableCredit($user->id);

            return [
                'id'               => $user->id,
                'name'             => $user->name,
                'gender'           => $user->info?->gender,
                'age'              => $age,
                'grade'            => $membership?->senbet_class,
                'work_status'      => $obligation->work_status,
                'status'           => $obligation->status,
                'exempt_reason'    => $obligation->status === 'exempt' ? $this->paymentService->exemptReason($membership, $settings) : null,
                'base_amount'      => (float) $obligation->base_amount,
                'fine_amount'      => (float) $obligation->fine_amount,
                'total_amount_due' => (float) $obligation->total_amount_due,
                'amount_paid'      => (float) $obligation->amount_paid,
                'balance'          => (float) $obligation->balance,
                'due_date'         => $obligation->due_date,
                'paid_at'          => $obligation->paid_at?->toDateTimeString(),
                'payment_id'       => $obligation->exists ? $obligation->id : null,
                'available_credit' => $credit,
            ];
        })->filter();

        if (($ps = $request->input('payment_status')) && $ps !== 'all') {
            $rows = $rows->filter(fn($r) => $r['status'] === $ps);
        }

        $rows    = $rows->values();
        $total   = $rows->count();
        $page    = max(1, (int) $request->input('page', 1));
        $sliced  = $rows->forPage($page, $perPage)->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'users'    => $sliced,
                'settings' => $settings,
                'meta'     => [
                    'total'        => $total,
                    'per_page'     => $perPage,
                    'current_page' => $page,
                    'last_page'    => (int) ceil($total / $perPage),
                ],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/payments/{payment}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Payment $payment)
    {
        $payment->load('transactions', 'user', 'creditApplications.credit');
        return response()->json(['status' => 'success', 'data' => $payment]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/payments/statistics
    // ─────────────────────────────────────────────────────────────────────────

    public function statistics(Request $request)
    {
        $request->validate([
            'year'  => 'sometimes|integer',
            'month' => 'sometimes|integer|min:1|max:13',
        ]);

        $year  = (int) $request->input('year',  2017);
        $month = (int) $request->input('month', 1);

        $stats = $this->paymentService->getStatistics($year, $month);

        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/payments  (single month)
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'for_year'       => 'required|integer|min:2000|max:2100',
            'for_month'      => 'required|integer|min:1|max:13',
            'amount_paid'    => 'required|numeric|min:0.01',
            'fine_paid'      => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,mobile_money',
            'reference'      => 'nullable|string|max:100',
        ]);

        $settings = $this->paymentService->getSettings();
        $user     = User::with('senbetMembership')->findOrFail($validated['user_id']);

        $obligation = $this->paymentService->getOrGenerateObligation(
            $user,
            $validated['for_year'],
            $validated['for_month'],
            $settings
        );

        if (! $obligation || $obligation->status === 'exempt') {
            return response()->json([
                'status'  => 'error',
                'message' => 'This member is not eligible for payment.',
            ], 403);
        }

        $transaction = $this->paymentService->recordPayment(
            $obligation,
            (float) $validated['amount_paid'],
            (float) ($validated['fine_paid'] ?? 0),
            $request->user()->id,
            $validated['payment_method'] ?? 'cash',
            $validated['reference'] ?? null,
        );

        $credit = $this->paymentService->availableCredit($user->id);

        return response()->json([
            'status'           => 'success',
            'message'          => 'Payment recorded successfully.',
            'data'             => $transaction->load('recordedBy:id,name'),
            'available_credit' => $credit,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/payments/bulk  (multi-month)
    // ─────────────────────────────────────────────────────────────────────────

    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'from_year'      => 'required|integer|min:2000|max:2100',
            'from_month'     => 'required|integer|min:1|max:13',
            'num_months'     => 'required|integer|in:1,3,6,12',
            'amount_paid'    => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank_transfer,mobile_money',
            'reference'      => 'nullable|string|max:100',
        ]);

        $user = User::with('senbetMembership')->findOrFail($validated['user_id']);

        $result = $this->paymentService->recordBulkPayment(
            $user,
            (int) $validated['from_year'],
            (int) $validated['from_month'],
            (int) $validated['num_months'],
            (float) $validated['amount_paid'],
            $request->user()->id,
            $validated['payment_method'] ?? 'cash',
            $validated['reference'] ?? null,
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Bulk payment recorded successfully.',
            'data'    => $result,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/payments/preview-bulk
    // ─────────────────────────────────────────────────────────────────────────

    public function previewBulk(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'from_year'  => 'required|integer|min:2000|max:2100',
            'from_month' => 'required|integer|min:1|max:13',
            'num_months' => 'required|integer|in:1,3,6,12',
            'amount'     => 'required|numeric|min:0',
        ]);

        $user = User::with('senbetMembership')->findOrFail($validated['user_id']);

        $preview = $this->paymentService->previewBulkPayment(
            $user,
            (int) $validated['from_year'],
            (int) $validated['from_month'],
            (int) $validated['num_months'],
            (float) $validated['amount'],
        );

        return response()->json(['status' => 'success', 'data' => $preview]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/payments/history/{user}
    // ─────────────────────────────────────────────────────────────────────────

    public function history(User $user)
    {
        $payments = $user->payments()
            ->with([
                'transactions'       => fn($q) => $q->orderBy('paid_at'),
                'creditApplications' => fn($q) => $q->with('credit'),
            ])
            ->orderBy('for_year', 'desc')
            ->orderBy('for_month', 'desc')
            ->get()
            ->map(function (Payment $p) {
                return [
                    'id'               => $p->id,
                    'for_year'         => $p->for_year,
                    'for_month'        => $p->for_month,
                    'work_status'      => $p->work_status,
                    'base_amount'      => (float) $p->base_amount,
                    'fine_amount'      => (float) $p->fine_amount,
                    'total_amount_due' => (float) $p->total_amount_due,
                    'amount_paid'      => (float) $p->amount_paid,
                    'balance'          => (float) $p->balance,
                    'due_date'         => $p->due_date?->toDateString(),
                    'paid_at'          => $p->paid_at?->toDateTimeString(),
                    'status'           => $p->status,
                    'transactions'     => $p->transactions->map(fn($t) => [
                        'id'               => $t->id,
                        'amount'           => (float) $t->amount,
                        'fine_paid'        => (float) $t->fine_paid,
                        'payment_method'   => $t->payment_method,
                        'reference_number' => $t->reference_number,
                        'paid_at'          => $t->paid_at?->toDateTimeString(),
                        'recorded_by'      => $t->recorded_by,
                    ]),
                    'credit_applications' => $p->creditApplications->map(fn($ca) => [
                        'id'             => $ca->id,
                        'amount_applied' => (float) $ca->amount_applied,
                        'credit_source'  => $ca->credit?->note ?? 'Credit',
                        'created_at'     => $ca->created_at?->toDateTimeString(),
                    ]),
                    'credit_applied' => (float) $p->creditApplications->sum('amount_applied'),
                ];
            });

        // Credit ledger for this member
        $credits = MemberCredit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'amount'      => (float) $c->amount,
                'remaining'   => (float) $c->remaining,
                'source_type' => $c->source_type,
                'note'        => $c->note,
                'created_at'  => $c->created_at?->toDateTimeString(),
            ]);

        $summary = [
            'total_paid'           => round($payments->sum('amount_paid'), 2),
            'total_fines'          => round($payments->sum('fine_amount'), 2),
            'total_outstanding'    => round($payments->sum('balance'), 2),
            'total_credit_applied' => round($payments->sum('credit_applied'), 2),
            'available_credit'     => $this->paymentService->availableCredit($user->id),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'payments' => $payments,
                'credits'  => $credits,
                'summary'  => $summary,
            ],
        ]);
    }
}
