<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.two_factor.challenge_title') }} - {{ __('admin.site_name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#002C60',
                        secondary: '#FFD600',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-primary via-blue-900 to-blue-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-primary text-white text-center py-8 px-6">
                <div class="mb-4"><i class="fas fa-envelope-open-text text-5xl text-secondary"></i></div>
                <h1 class="text-2xl font-bold mb-2">{{ __('admin.two_factor.challenge_heading') }}</h1>
                <p class="text-gray-300 text-sm">{{ __('admin.two_factor.challenge_desc') }}</p>
            </div>

            <div class="p-8">
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('admin.two-factor.verify') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-key text-primary ml-2"></i>
                            {{ __('admin.two_factor.confirm_code_label') }}
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               inputmode="numeric"
                               pattern="[0-9]{6}"
                               maxlength="6"
                               autocomplete="one-time-code"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl tracking-widest font-mono focus:ring-2 focus:ring-secondary"
                               placeholder="000000">
                    </div>

                    <button type="submit"
                            class="w-full bg-secondary hover:bg-yellow-500 text-primary font-bold py-3 px-6 rounded-lg transition shadow-lg">
                        <i class="fas fa-check ml-2"></i>
                        {{ __('admin.login') }}
                    </button>
                </form>

                <form action="{{ route('admin.two-factor.resend') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full py-2 text-sm text-primary hover:text-blue-900 font-semibold underline">
                        {{ __('admin.two_factor.resend') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-gray-500">
                    <a href="{{ route('admin.login') }}" class="hover:text-primary">{{ __('admin.login_title') }}</a>
                </p>
            </div>

            <div class="bg-gray-50 px-8 py-4 text-center text-sm text-gray-600 border-t">
                <p>{{ __('admin.copyright') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
