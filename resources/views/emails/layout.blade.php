<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Notification')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            background-color: #f4f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: #1a1c21;
        }

        .wrapper {
            width: 100%;
            padding: 40px 0;
            background-color: #f4f7fa;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f6;
        }

        .header {
            padding: 48px 48px 32px;
            text-align: left;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #0b529c;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .content {
            padding: 0 48px 48px;
        }

        .headline {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 24px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .body-text {
            font-size: 16px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 32px;
        }

        .card {
            background-color: #f9fafb;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid #f3f4f6;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-group:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #111827;
        }

        .password-value {
            font-family: 'JetBrains Mono', 'Monaco', 'Consolas', monospace;
            font-size: 20px;
            color: #0b529c;
            background: #eef4ff;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
            letter-spacing: 1px;
        }

        .btn-container {
            margin-top: 40px;
        }

        .btn {
            display: inline-block;
            background-color: #0b529c;
            color: #ffffff !important;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .footer {
            padding: 32px 48px;
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }

        .footer p {
            margin: 8px 0;
        }

        .footer-links {
            margin-top: 12px;
        }

        .footer-links a {
            color: #0b529c;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
        }

        @media screen and (max-width: 600px) {
            .container {
                margin: 0 10px;
            }

            .header,
            .content {
                padding-left: 24px;
                padding-right: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <a href="{{ config('app.url') }}" class="logo">{{ \App\Services\BackMessage::get('app.name') }}.</a>
            </div>

            <div class="content">
                @yield('content')
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ \App\Services\BackMessage::get('email.copyright') }}</p>
                <div class="footer-links">
                    <a href="{{ config('app.url') }}">{{ \App\Services\BackMessage::get('email.website') }}</a>
                    <a href="#">{{ \App\Services\BackMessage::get('email.support') }}</a>
                    <a href="#">{{ \App\Services\BackMessage::get('email.privacy') }}</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>