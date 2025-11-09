<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'Notification' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.6;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 20px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 30px 40px;
            text-align: center;
        }

        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .organization-name {
            color: #ffffff;
            opacity: 0.9;
            font-size: 14px;
            margin-top: 8px;
        }

        .email-body {
            padding: 40px;
        }

        .email-footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .email-footer p {
            margin: 0 0 10px 0;
            color: #6b7280;
            font-size: 14px;
        }

        .unsubscribe-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 13px;
        }

        .unsubscribe-link:hover {
            text-decoration: underline;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #111827;
                color: #f3f4f6;
            }

            .email-wrapper {
                background-color: #111827;
            }

            .email-container {
                background-color: #1f2937;
            }

            .email-footer {
                background-color: #111827;
                border-top-color: #374151;
            }

            .email-footer p {
                color: #9ca3af;
            }
        }

        /* Responsive */
        @media only screen and (max-width: 640px) {
            .email-header {
                padding: 20px 30px;
            }

            .email-header h1 {
                font-size: 20px;
            }

            .email-body {
                padding: 30px 25px;
            }

            .email-footer {
                padding: 20px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>{{ $organizationName ?? config('app.name') }}</h1>
                @if(isset($emailTitle))
                    <p class="organization-name">{{ $emailTitle }}</p>
                @endif
            </div>

            <!-- Body Content -->
            <div class="email-body">
                @yield('content')
            </div>

            <!-- Footer -->
            @if(($showUnsubscribe ?? true) && isset($unsubscribeLink))
                <div class="email-footer">
                    <p>&copy; {{ date('Y') }} {{ $organizationName ?? config('app.name') }}. All rights reserved.</p>
                    <p>
                        Don't want to receive these reminders?
                        <a href="{{ $unsubscribeLink }}" class="unsubscribe-link">Manage your preferences</a>
                    </p>
                </div>
            @elseif(!($showUnsubscribe ?? true))
                <div class="email-footer">
                    <p>&copy; {{ date('Y') }} {{ $organizationName ?? config('app.name') }}. All rights reserved.</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
