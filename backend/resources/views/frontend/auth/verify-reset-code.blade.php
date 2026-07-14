<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('frontend.auth.password_reset_code_title') }} - {{ __('frontend.site_name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#042B64', secondary: '#FFD600' } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        .btn-primary { background-color: #FFD600; color: #042B64; font-weight: 600; transition: all 0.3s ease; }
        .btn-primary:hover { background-color: #ffc400; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 214, 0, 0.4); }
        .input-field:focus { outline: none; border-color: #FFD600; box-shadow: 0 0 0 3px rgba(255, 214, 0, 0.1); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="absolute top-4 {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} z-50">
        <div class="relative language-switcher" onmouseenter="showLanguageMenu(this)" onmouseleave="hideLanguageMenu(this)">
            <button class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg shadow-md hover:shadow-lg transition">
                <i class="fas fa-globe text-primary"></i>
                <span class="text-sm font-semibold text-gray-700">{{ config('app.available_locales')[app()->getLocale()] }}</span>
                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
            </button>
            <div class="language-menu absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden">
                @foreach(config('app.available_locales') as $locale => $name)
                    <a href="{{ route('language.switch', $locale) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition {{ app()->getLocale() === $locale ? 'bg-blue-50 text-primary font-semibold' : 'text-gray-700' }}">
                        @if($locale === 'ar')<span class="text-lg">🇸🇦</span>@elseif($locale === 'en')<span class="text-lg">🇬🇧</span>@else<span class="text-lg">🇹🇷</span>@endif
                        <span>{{ $name }}</span>
                        @if(app()->getLocale() === $locale)<i class="fas fa-check text-green-500 {{ app()->getLocale() === 'ar' ? 'ml-auto' : 'mr-auto' }}"></i>@endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-4">
                    <img src="{{ asset('logo.png') }}" alt="{{ __('frontend.site_name') }}" class="h-12 w-auto">
                    <div>
                        <h1 class="text-2xl font-bold text-primary">{{ __('frontend.site_name') }}</h1>
                        <p class="text-xs text-gray-500">{{ __('frontend.tagline') }}</p>
                    </div>
                </a>
                <h2 class="text-2xl font-bold text-gray-800">{{ __('frontend.auth.password_reset_code_title') }}</h2>
                <p class="text-sm text-gray-600 mt-2">{{ __('frontend.auth.password_reset_code_message') }}</p>
                <p class="text-sm font-medium text-primary mt-1">{{ $email }}</p>
            </div>

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-600 space-y-1">
                        @foreach($errors->all() as $error)
                            <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.verify-code.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.auth.code') }}</label>
                    <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus
                           placeholder="000000" class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:ring-2 focus:ring-secondary text-center text-2xl tracking-widest">
                </div>
                <button type="submit" class="w-full btn-primary py-3 rounded-lg text-lg font-bold">
                    {{ __('frontend.auth.password_reset') }}
                </button>
            </form>

            <div class="mt-6 flex justify-between text-sm">
                <a href="{{ route('password.forgot') }}" class="text-primary hover:text-secondary font-semibold transition">
                    <i class="fas fa-arrow-right ml-1"></i> {{ __('frontend.auth.password_reset_request_title') }}
                </a>
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-primary font-semibold transition">
                    {{ __('frontend.auth.login') }}
                </a>
            </div>
        </div>
    </div>

    <script>
        let languageMenuTimeout = null;
        function showLanguageMenu(el) { if (languageMenuTimeout) { clearTimeout(languageMenuTimeout); languageMenuTimeout = null; } const m = el.querySelector('.language-menu'); if (m) m.classList.remove('hidden'); }
        function hideLanguageMenu(el) { const m = el.querySelector('.language-menu'); if (m) languageMenuTimeout = setTimeout(() => { m.classList.add('hidden'); languageMenuTimeout = null; }, 4000); }
        document.getElementById('code').addEventListener('input', function(e) { this.value = this.value.replace(/\D/g, '').slice(0, 6); });
    </script>
</body>
</html>
