<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Feedback Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1e3a8a; font-size: 20px; }
        .header p { margin: 5px 0 0 0; color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: capitalize; }
        .status-new { background-color: #fef3c7; color: #92400e; }
        .status-in-progress { background-color: #dbeafe; color: #1e40af; }
        .status-resolved { background-color: #dcfce7; color: #166534; }
        .status-closed { background-color: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Telegram Feedback System Report</h1>
        <p>Generated on: {{ $generatedAt }} | Total Records: {{ count($feedbacks) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">User</th>
                <th style="width: 12%;">Category</th>
                <th style="width: 10%;">Priority</th>
                <th style="width: 12%;">Status</th>
                <th>Message</th>
                <th style="width: 15%;">Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($feedbacks as $f)
            <tr>
                <td>
                    <strong>{{ $f['userName'] ?? 'Anonymous' }}</strong><br>
                    <small>ID: {{ $f['telegramId'] ?? 'N/A' }}</small>
                </td>
                <td>{{ $f['category'] ?? 'Other' }}</td>
                <td>{{ $f['priority'] ?? 'Medium' }}</td>
                <td>
                    <span class="badge status-{{ strtolower(str_replace(' ', '-', $f['status'] ?? 'new')) }}">
                        {{ $f['status'] ?? 'New' }}
                    </span>
                </td>
                <td>{{ $f['message'] ?? '' }}</td>
                <td>{{ isset($f['createdAt']) ? date('Y-m-d H:i', strtotime($f['createdAt'])) : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
