<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.login') }} - أعلنها</title>

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

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-primary via-blue-900 to-blue-800 min-h-screen flex items-center justify-center p-4">
    <!-- Language Switcher (Top Right) -->
    <div class="absolute top-4 {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} z-50">
        <div class="relative group">
            <button class="flex items-center gap-2 px-4 py-2 bg-white/90 backdrop-blur rounded-lg hover:bg-white transition shadow-lg">
                <i class="fas fa-globe text-primary"></i>
                <span class="text-sm font-semibold text-gray-700">
                    {{ config('app.available_locales')[app()->getLocale()] }}
                </span>
                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
            </button>

            <div class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block">
                @foreach(config('app.available_locales') as $locale => $name)
                    <a href="{{ route('admin.language.switch', $locale) }}"
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

    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-primary text-white text-center py-8 px-6">
                <div class="mb-4">
                    <i class="fas fa-shield-alt text-5xl text-secondary"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">{{ __('admin.site_name') }}</h1>
                <p class="text-gray-300">{{ __('admin.login_title') }}</p>
            </div>

            <!-- Form -->
            <div class="p-8">
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope text-primary ml-2"></i>
                            {{ __('admin.email') }}
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent transition"
                               placeholder="admin@a3lenha.com">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock text-primary ml-2"></i>
                            {{ __('admin.password') }}
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent transition"
                               placeholder="••••••••">
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="remember"
                                   class="w-4 h-4 text-secondary border-gray-300 rounded focus:ring-secondary">
                            <span class="mr-2 text-sm text-gray-700">{{ __('admin.remember_me') }}</span>
                        </label>

                        <a href="#" class="text-sm text-primary hover:text-blue-800 font-semibold">
                            {{ __('admin.forgot_password') }}
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-secondary hover:bg-yellow-500 text-primary font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <i class="fas fa-sign-in-alt ml-2"></i>
                        {{ __('admin.login') }}
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 text-center text-sm text-gray-600 border-t">
                <p>{{ __('admin.copyright') }}</p>
            </div>
        </div>
    </div>
</body>
</html>

