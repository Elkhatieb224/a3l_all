<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('frontend.auth.register') }} - {{ __('frontend.site_name') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#042B64',
                        secondary: '#FFD600',
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Arabic Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        .btn-primary {
            background-color: #042B64;
            color: #FFFFFF;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #031d4a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(4, 43, 100, 0.4);
        }

        .input-field:focus {
            outline: none;
            border-color: #042B64;
            box-shadow: 0 0 0 3px rgba(4, 43, 100, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <!-- Language Switcher (Top Right) -->
    <div class="absolute top-4 {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} z-50">
        <div class="relative language-switcher" 
             onmouseenter="showLanguageMenu(this)" 
             onmouseleave="hideLanguageMenu(this)">
            <button class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg shadow-md hover:shadow-lg transition">
                <i class="fas fa-globe text-primary"></i>
                <span class="text-sm font-semibold text-gray-700">
                    {{ config('app.available_locales')[app()->getLocale()] }}
                </span>
                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
            </button>
            <div class="language-menu absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden">
                @foreach(config('app.available_locales') as $locale => $name)
                    <a href="{{ route('language.switch', $locale) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition {{ app()->getLocale() === $locale ? 'bg-blue-50 text-primary font-semibold' : 'text-gray-700' }}">
                        @if($locale === 'ar')
                            <span class="text-lg">🇸🇦</span>
                        @elseif($locale === 'en')
                            <span class="text-lg">🇬🇧</span>
                        @else
                            <span class="text-lg">🇹🇷</span>
                        @endif
                        <span>{{ $name }}</span>
                        @if(app()->getLocale() === $locale)
                            <i class="fas fa-check text-green-500 {{ app()->getLocale() === 'ar' ? 'ml-auto' : 'mr-auto' }}"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Register Form -->
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-4">
                    <img src="{{ asset('logo.png') }}" alt="{{ __('frontend.site_name') }}" class="h-12 w-auto">
                    <div>
                        <h1 class="text-2xl font-bold text-primary">{{ __('frontend.site_name') }}</h1>
                        <p class="text-xs text-gray-500">{{ __('frontend.tagline') }}</p>
                    </div>
                </a>
                <h2 class="text-3xl font-bold text-gray-800">{{ __('frontend.auth.register') }}</h2>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-600 space-y-1">
                        @foreach($errors->all() as $error)
                            <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600">
                        <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                    </p>
                </div>
            @endif

            <!-- Register Form -->
            <form action="{{ route('register') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('frontend.auth.email') }}
                        <i class="fas fa-info-circle text-blue-500 text-xs mr-1" title="معلومات"></i>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}"
                           required
                           autofocus
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:ring-2 focus:ring-primary">
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('frontend.auth.name') }}
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:ring-2 focus:ring-primary">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('frontend.auth.password') }}
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:ring-2 focus:ring-primary pr-12">
                        <button type="button" 
                                onclick="togglePassword('password')"
                                class="absolute {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary">
                            <i class="fas fa-eye" id="passwordToggle"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('frontend.auth.password_confirmation') }}
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:ring-2 focus:ring-primary pr-12">
                        <button type="button" 
                                onclick="togglePassword('password_confirmation')"
                                class="absolute {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary">
                            <i class="fas fa-eye" id="passwordConfirmationToggle"></i>
                        </button>
                    </div>
                </div>

                <!-- Agreement Checkboxes -->
                <div class="space-y-3">
                    <!-- Agreement 1 -->
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" 
                               name="agreement" 
                               value="1"
                               required
                               class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="text-sm text-gray-700">
                            {{ __('frontend.auth.agreement') }}
                            <a href="/terms" target="_blank" rel="noopener noreferrer" class="text-primary hover:text-secondary font-semibold underline">
                                {{ __('frontend.auth.agreement_text') }}
                            </a>
                        </span>
                    </label>

                    <!-- Agreement 2 (Optional) -->
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" 
                               name="commercial_messages" 
                               value="1"
                               class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="text-sm text-gray-700">
                            {{ __('frontend.auth.commercial_messages') }}
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full btn-primary py-3 rounded-lg text-lg font-bold">
                    {{ __('frontend.auth.register') }}
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500">{{ __('frontend.auth.or') }}</span>
                </div>
            </div>

            <!-- Social Login (Optional - for future) -->
            <div class="space-y-3">
                <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <img src="https://www.google.com/favicon.ico" alt="Google" class="w-5 h-5">
                    <span class="text-sm font-semibold text-gray-700">{{ __('frontend.auth.google_signup') }}</span>
                </button>
                <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <i class="fab fa-apple text-xl text-gray-700"></i>
                    <span class="text-sm font-semibold text-gray-700">{{ __('frontend.auth.apple_signup') }}</span>
                </button>
            </div>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    {{ __('frontend.auth.already_member') }}
                    <a href="{{ route('login') }}" class="text-primary hover:text-secondary font-semibold transition">
                        {{ __('frontend.auth.sign_in') }} <i class="fas fa-arrow-left text-xs"></i>
                    </a>
                </p>
            </div>

            <!-- Footer Agreement -->
            <div class="mt-6 text-center text-xs text-gray-500">
                <p>
                    {{ __('frontend.auth.by_continuing') }}
                    <a href="#" class="text-primary hover:text-secondary underline">
                        {{ __('frontend.auth.agreement_text') }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleId = fieldId === 'password' ? 'passwordToggle' : 'passwordConfirmationToggle';
            const passwordToggle = document.getElementById(toggleId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }

        let languageMenuTimeout = null;

        function showLanguageMenu(element) {
            // Clear any existing timeout
            if (languageMenuTimeout) {
                clearTimeout(languageMenuTimeout);
                languageMenuTimeout = null;
            }
            
            const menu = element.querySelector('.language-menu');
            if (menu) {
                menu.classList.remove('hidden');
            }
        }

        function hideLanguageMenu(element) {
            const menu = element.querySelector('.language-menu');
            if (menu) {
                // Set timeout to hide after 4 seconds
                languageMenuTimeout = setTimeout(function() {
                    menu.classList.add('hidden');
                    languageMenuTimeout = null;
                }, 4000);
            }
        }
    </script>
</body>
</html>

