<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('frontend.auth.password_reset') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #042B64 0%, #06408a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ __('frontend.auth.password_reset') }}</h1>
    </div>

    <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb;">
        @if($userName)
            <p style="font-size: 16px; margin-bottom: 20px;">{{ __('frontend.profile.hello') }} {{ $userName }},</p>
        @else
            <p style="font-size: 16px; margin-bottom: 20px;">{{ __('frontend.profile.hello') }},</p>
        @endif

        <p style="font-size: 16px; margin-bottom: 20px;">{{ __('frontend.auth.password_reset_message') }}</p>

        <div style="background: white; border: 2px dashed #042B64; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #042B64; font-family: 'Courier New', monospace;">
                {{ $code }}
            </div>
        </div>

        <p style="font-size: 14px; color: #6b7280; margin-top: 20px;">
            {{ __('frontend.auth.password_reset_expires') }}
        </p>

        <p style="font-size: 14px; color: #6b7280; margin-top: 10px;">
            {{ __('frontend.auth.password_reset_warning') }}
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;">
        <p>{{ __('frontend.auth.password_reset_footer') }}</p>
    </div>
</body>
</html>
