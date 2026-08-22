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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared Helper: Get filtered payment rows for index, export, print
    // ─────────────────────────────────────────────────────────────────────────

    private function getFilteredRows(Request $request): array
    {
        $settings = $this->paymentService->getSettings();
        $year     = (int) $request->input('year', 2017);
        $month    = (int) $request->input('month', 1);

        $query = User::query()
            ->with(['info', 'senbetMembership'])
            ->whereHas('senbetMembership', function ($q) {
                $q->whereNull('deleted_at');
            });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("name::text"), 'ilike', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('info', fn($iq) => $iq->where('registration_id', 'ilike', "%{$search}%"));
            });
        }

        if (($grade = $request->input('grade')) && $grade !== 'all') {
            $query->whereHas('senbetMembership', fn($q) => $q->where('senbet_class', (string) $grade));
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
                'registration_id'  => $user->info?->registration_id ?? 'DBSS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'name'             => $user->name,
                'father_name'      => $user->info?->father_name,
                'grandfather_name' => $user->info?->grandfather_name,
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

        if (($el = $request->input('eligibility')) && $el !== 'all') {
            if ($el === 'exempt') {
                $rows = $rows->filter(fn($r) => $r['status'] === 'exempt');
            } elseif ($el === 'eligible') {
                $rows = $rows->filter(fn($r) => $r['status'] !== 'exempt');
            }
        }

        return [
            'settings' => $settings,
            'year'     => $year,
            'month'    => $month,
            'rows'     => $rows->values(),
        ];
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
            'eligibility'    => 'sometimes|in:eligible,exempt,all',
            'grade'          => 'sometimes|string|max:20',
            'age_min'        => 'sometimes|integer|min:0',
            'age_max'        => 'sometimes|integer|min:0',
            'per_page'       => 'sometimes|integer|min:5|max:200',
        ]);

        $perPage   = (int) $request->input('per_page', 10);
        $filterRes = $this->getFilteredRows($request);
        $rows      = $filterRes['rows'];
        $total     = $rows->count();
        $page      = max(1, (int) $request->input('page', 1));
        $sliced    = $rows->forPage($page, $perPage)->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'users'    => $sliced,
                'settings' => $filterRes['settings'],
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
    // EXPORT ENDPOINTS: CSV, Excel, PDF
    // ─────────────────────────────────────────────────────────────────────────

    public function exportCsv(Request $request)
    {
        $filterRes = $this->getFilteredRows($request);
        $rows      = $filterRes['rows'];
        $filename  = 'payment_report_' . $filterRes['year'] . '_' . $filterRes['month'] . '.csv';

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens Amharic characters correctly
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No.', 'Full Name', 'Registration ID', 'Grade', 'Age',
                'Work Status', 'Base Amount', 'Fine', 'Total Due',
                'Paid', 'Credit', 'Balance', 'Status'
            ]);

            foreach ($rows as $index => $r) {
                $nameStr = trim(implode(' ', array_filter([
                    is_array($r['name']) ? ($r['name']['am'] ?? $r['name']['en'] ?? '') : ($r['name'] ?? ''),
                    $r['father_name'] ?? '',
                    $r['grandfather_name'] ?? '',
                ])));
                fputcsv($file, [
                    $index + 1,
                    $nameStr,
                    $r['registration_id'],
                    $r['grade'] ?? '',
                    $r['age'] ?? '',
                    $r['work_status'],
                    number_format($r['base_amount'], 2, '.', ''),
                    number_format($r['fine_amount'], 2, '.', ''),
                    number_format($r['total_amount_due'], 2, '.', ''),
                    number_format($r['amount_paid'], 2, '.', ''),
                    number_format($r['available_credit'] ?? 0, 2, '.', ''),
                    number_format($r['balance'], 2, '.', ''),
                    $r['status'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filterRes = $this->getFilteredRows($request);
        $rows      = $filterRes['rows'];
        $filename  = 'payment_report_' . $filterRes['year'] . '_' . $filterRes['month'] . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'Debre Bisrat Saint Shenouda Sunday School - Payment Report');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Subtitle
        $sheet->setCellValue('A2', "Ethiopian Period: Month {$filterRes['month']} / Year {$filterRes['year']} | Generated: " . now()->toDateTimeString());
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Headers
        $headers = [
            'No.', 'Full Name', 'Registration ID', 'Grade', 'Age',
            'Work Status', 'Base Amount', 'Fine', 'Total Due',
            'Paid', 'Credit', 'Balance', 'Status'
        ];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:M4')->getFont()->setBold(true);

        $rowNum = 5;
        foreach ($rows as $index => $r) {
            $nameStr = trim(implode(' ', array_filter([
                is_array($r['name']) ? ($r['name']['am'] ?? $r['name']['en'] ?? '') : ($r['name'] ?? ''),
                $r['father_name'] ?? '',
                $r['grandfather_name'] ?? '',
            ])));
            $sheet->fromArray([
                $index + 1,
                $nameStr,
                $r['registration_id'],
                $r['grade'] ?? '',
                $r['age'] ?? '',
                $r['work_status'],
                $r['base_amount'],
                $r['fine_amount'],
                $r['total_amount_due'],
                $r['amount_paid'],
                $r['available_credit'] ?? 0,
                $r['balance'],
                $r['status'],
            ], null, "A{$rowNum}");
            $rowNum++;
        }

        // Auto column widths
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        $filterRes = $this->getFilteredRows($request);
        $rows      = $filterRes['rows'];
        $filename  = 'payment_report_' . $filterRes['year'] . '_' . $filterRes['month'] . '.pdf';

        $html = view('pdf.payment_report', [
            'rows'        => $rows,
            'year'        => $filterRes['year'],
            'month'       => $filterRes['month'],
            'generatedAt' => now()->toDayDateTimeString(),
        ])->render();

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Debre Bisrat Sunday School');
        $pdf->SetTitle('Payment Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage('L'); // Landscape for tabular view
        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
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
            'gift_surplus'   => 'nullable|boolean',
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
            $request->user()?->id ?: $user->id,
            $validated['payment_method'] ?? 'cash',
            $validated['reference'] ?? null,
            (bool) ($validated['gift_surplus'] ?? false)
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
            'gift_surplus'   => 'nullable|boolean',
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
            $request->user()?->id ?: $user->id,
            $validated['payment_method'] ?? 'cash',
            $validated['reference'] ?? null,
            (bool) ($validated['gift_surplus'] ?? false)
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
        // Clean up any obligations before registration date
        $membership = $user->senbetMembership;
        if ($membership && $membership->registration_date) {
            $regCarbon = Carbon::parse($membership->registration_date);
            [$regYear, $regMonth] = $this->paymentService->gregToEth($regCarbon);
            
            $user->payments()->where(function($q) use ($regYear, $regMonth) {
                $q->where('for_year', '<', $regYear)
                  ->orWhere(function($sub) use ($regYear, $regMonth) {
                      $sub->where('for_year', $regYear)->where('for_month', '<', $regMonth);
                  });
            })->delete();

            // Generate obligations up to current date
            [$currentYear, $currentMonth] = $this->paymentService->currentEthiopianDate();
            $year = $regYear;
            $month = $regMonth;
            $settings = $this->paymentService->getSettings();
            while ($year < $currentYear || ($year === $currentYear && $month <= $currentMonth)) {
                $this->paymentService->getOrGenerateObligation($user, $year, $month, $settings);
                $month++;
                if ($month > 13) {
                    $month = 1;
                    $year++;
                }
            }
        }

        $payments = $user->payments()
            ->with([
                'transactions'       => fn($q) => $q->with('recordedBy.info')->orderBy('paid_at'),
                'creditApplications' => fn($q) => $q->with('credit'),
            ])
            ->orderBy('for_year', 'desc')
            ->orderBy('for_month', 'desc')
            ->get()
            ->map(function (Payment $p) {
                $creditGenerated = (float) \App\Models\MemberCredit::where('source_payment_id', $p->id)->sum('amount');
                $donation        = (float) \App\Models\SchoolDonation::where('payment_id', $p->id)->sum('amount');
                $creditApplied   = (float) $p->creditApplications->sum('amount_applied');
                $balance         = max(0, (float) $p->total_amount_due - (float) $p->amount_paid - $creditApplied);

                return [
                    'id'               => $p->id,
                    'for_year'         => $p->for_year,
                    'for_month'        => $p->for_month,
                    'work_status'      => $p->work_status,
                    'base_amount'      => (float) $p->base_amount,
                    'fine_amount'      => (float) $p->fine_amount,
                    'total_amount_due' => (float) $p->total_amount_due,
                    'amount_paid'      => (float) $p->amount_paid,
                    'credit_applied'   => $creditApplied,
                    'balance'          => $balance,
                    'due_date'         => $p->due_date?->toDateString(),
                    'paid_at'          => $p->paid_at?->toDateTimeString(),
                    'status'           => $p->status,
                    'credit_generated' => $creditGenerated,
                    'donation'         => $donation,
                    'transactions'     => $p->transactions->map(fn($t) => [
                        'id'               => $t->id,
                        'amount'           => (float) $t->amount,
                        'fine_paid'        => (float) $t->fine_paid,
                        'is_gift'          => (bool) $t->is_gift,
                        'payment_method'   => $t->payment_method,
                        'reference_number' => $t->reference_number,
                        'paid_at'          => $t->paid_at?->toDateTimeString(),
                        'recorded_by'      => $t->recordedBy?->info?->registration_id ?? $t->recordedBy?->name ?? null,
                    ]),
                    'credit_applications' => $p->creditApplications->map(fn($ca) => [
                        'id'             => $ca->id,
                        'amount_applied' => (float) $ca->amount_applied,
                        'credit_source'  => $ca->credit?->note ?? 'Credit',
                        'created_at'     => $ca->created_at?->toDateTimeString(),
                    ]),
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

        // School donations for this member
        $donations = \App\Models\SchoolDonation::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'amount'     => (float) $d->amount,
                'note'       => $d->note,
                'created_at' => $d->created_at?->toDateTimeString(),
            ]);

        $summary = [
            'total_paid'             => round($payments->sum('amount_paid'), 2),
            'total_fines'            => round($payments->sum('fine_amount'), 2),
            'total_outstanding'      => round($payments->sum('balance'), 2),
            'total_credit_applied'   => round($payments->sum('credit_applied'), 2),
            'total_credit_generated' => round($payments->sum('credit_generated'), 2),
            'available_credit'       => $this->paymentService->availableCredit($user->id),
            'total_donations'        => round($donations->sum('amount'), 2),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'payments'          => $payments,
                'credits'           => $credits,
                'donations'         => $donations,
                'summary'           => $summary,
                'registration_date' => $membership?->registration_date?->toDateString(),
            ],
        ]);
    }
}
