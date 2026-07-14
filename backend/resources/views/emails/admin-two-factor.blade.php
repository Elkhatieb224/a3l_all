<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.two_factor.mail_title') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #042B64 0%, #06408a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 22px;">{{ __('admin.two_factor.mail_title') }}</h1>
    </div>

    <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb;">
        @if($adminName)
            <p style="font-size: 16px; margin-bottom: 20px;">{{ __('admin.two_factor.mail_greeting', ['name' => $adminName]) }}</p>
        @else
            <p style="font-size: 16px; margin-bottom: 20px;">{{ __('admin.two_factor.mail_greeting_generic') }}</p>
        @endif

        @if($purpose === \App\Models\AdminTwoFactorChallenge::TYPE_SETUP)
            <p style="font-size: 16px; margin-bottom: 20px;">{{ __('admin.two_factor.mail_body_setup') }}</p>
        @else
            <p style="font-size: 16px; margin-bottom: 20px;">{{ __('admin.two_factor.mail_body_login') }}</p>
        @endif

        <div style="background: white; border: 2px dashed #042B64; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #042B64; font-family: 'Courier New', monospace;">
                {{ $code }}
            </div>
        </div>

        <p style="font-size: 14px; color: #6b7280;">
            {{ __('admin.two_factor.mail_expires', ['minutes' => config('admin_two_factor.challenge_ttl_minutes', 10)]) }}
        </p>
    </div>
</body>
</html>
