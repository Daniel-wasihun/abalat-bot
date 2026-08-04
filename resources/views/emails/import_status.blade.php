<!DOCTYPE html>
<html>

<head>
    <title>Import Status Report</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #1a73e8;
        }

        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .error-list {
            margin-top: 20px;
        }

        .error-item {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .failure {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">User Import Status Report</div>

        <div class="summary">
            <p>The user import process has completed.</p>
            <ul>
                <li>Total Rows Processed: <strong>{{ $total }}</strong></li>
                <li>Successfully Imported: <span class="success">{{ $success }}</span></li>
                <li>Failed to Import: <span class="failure">{{ $failure }}</span></li>
            </ul>
        </div>

        @if(count($errorList) > 0)
        <div class="error-list">
            <h3>Error Details:</h3>
            @foreach($errorList as $error)
            <div class="error-item">
                {{ $error }}
            </div>
            @endforeach
        </div>
        @endif

        <p>You can view more details in the system's import logs.</p>

        <p>Best regards,<br>Library Management System</p>
    </div>
</body>

</html>