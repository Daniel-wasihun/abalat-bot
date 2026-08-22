<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 12px; margin-bottom: 15px; }
        .header h1 { margin: 0; color: #1e3a8a; font-size: 18px; font-weight: bold; }
        .header p { margin: 4px 0 0 0; color: #64748b; font-size: 10px; }
        .meta { margin-bottom: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 6px; }
        .meta table { width: 100%; border: none; }
        .meta td { border: none; padding: 2px 5px; font-size: 10px; color: #475569; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 10px; text-align: left; }
        table.data-table th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
        table.data-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: capitalize; }
        .status-paid { background-color: #dcfce7; color: #166534; }
        .status-pending { background-color: #dbeafe; color: #1e40af; }
        .status-partial { background-color: #fef3c7; color: #92400e; }
        .status-late { background-color: #fee2e2; color: #991b1b; }
        .status-exempt { background-color: #f3f4f6; color: #4b5563; }
        .summary-box { margin-top: 15px; border-top: 2px solid #e2e8f0; padding-top: 10px; text-align: right; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Debre Bisrat Saint Shenouda Sunday School</h1>
        <p>Monthly Payment & Financial Report</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td><strong>Period:</strong> Ethiopian Month {{ $month }} / {{ $year }}</td>
                <td><strong>Generated On:</strong> {{ $generatedAt }}</td>
                <td><strong>Total Records:</strong> {{ count($rows) }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No.</th>
                <th style="width: 17%;">Full Name</th>
                <th style="width: 10%;">Reg. ID</th>
                <th style="width: 6%;" class="text-center">Grade</th>
                <th style="width: 5%;" class="text-center">Age</th>
                <th style="width: 6%;" class="text-center">Type</th>
                <th style="width: 8%;" class="text-right">Base</th>
                <th style="width: 7%;" class="text-right">Fine</th>
                <th style="width: 8%;" class="text-right">Total Due</th>
                <th style="width: 8%;" class="text-right">Paid</th>
                <th style="width: 7%;" class="text-right">Credit</th>
                <th style="width: 8%;" class="text-right">Balance</th>
                <th style="width: 6%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ implode(' ', array_filter([
                    is_array($row['name']) ? ($row['name']['am'] ?? $row['name']['en'] ?? '') : ($row['name'] ?? ''),
                    $row['father_name'] ?? '',
                    $row['grandfather_name'] ?? '',
                ])) }}</td>
                <td>{{ $row['registration_id'] ?? '—' }}</td>
                <td class="text-center">{{ $row['grade'] ?? '—' }}</td>
                <td class="text-center">{{ $row['age'] ?? '—' }}</td>
                <td class="text-center capitalize">{{ $row['work_status'] ?? '—' }}</td>
                <td class="text-right">{{ number_format($row['base_amount'], 2) }}</td>
                <td class="text-right">{{ number_format($row['fine_amount'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format($row['total_amount_due'], 2) }}</td>
                <td class="text-right">{{ number_format($row['amount_paid'], 2) }}</td>
                <td class="text-right">{{ number_format($row['available_credit'] ?? 0, 2) }}</td>
                <td class="text-right font-bold">{{ number_format($row['balance'], 2) }}</td>
                <td class="text-center">
                    <span class="badge status-{{ $row['status'] }}">{{ $row['status'] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <p><strong>Total Base:</strong> {{ number_format(collect($rows)->sum('base_amount'), 2) }} ETB &nbsp;|&nbsp;
           <strong>Total Fines:</strong> {{ number_format(collect($rows)->sum('fine_amount'), 2) }} ETB &nbsp;|&nbsp;
           <strong>Total Collected:</strong> {{ number_format(collect($rows)->sum('amount_paid'), 2) }} ETB &nbsp;|&nbsp;
           <strong>Total Outstanding:</strong> {{ number_format(collect($rows)->sum('balance'), 2) }} ETB</p>
    </div>
</body>
</html>
