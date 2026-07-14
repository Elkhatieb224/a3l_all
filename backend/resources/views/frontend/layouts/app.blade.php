<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('frontend.site_name')) - {{ __('frontend.tagline') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('Favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('Favicon.ico') }}">

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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Arabic Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        .btn-primary {
            background-color: #FFD600;
            color: #042B64;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #ffc400;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 214, 0, 0.4);
        }

        .header-bg {
            background-color: #042B64;
        }

        .search-input:focus {
            outline: none;
            border-color: #FFD600;
            box-shadow: 0 0 0 3px rgba(255, 214, 0, 0.1);
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50">
    @stack('scripts')
    <!-- Header -->
    <header class="header-bg text-white sticky top-0 z-50 shadow-lg">
        <!-- Top Bar -->
        <div class="border-b border-blue-800">
            <div class="container mx-auto px-2 sm:px-4 py-2 sm:py-3">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-3 lg:gap-0">
                    <!-- Logo -->
                    <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
                        <img src="{{ asset('logo.png') }}" alt="{{ __('frontend.site_name') }}" class="h-8 sm:h-10 w-auto">
                        <span class="text-lg sm:text-xl font-bold text-white">{{ __('frontend.site_name') }}</span>
                    </a>

                    <!-- Search Bar -->
                    <div class="flex-1 w-full lg:max-w-2xl lg:mx-4 xl:mx-8 order-3 lg:order-2">
                        <form action="{{ route('ads.index') }}" method="GET" class="flex gap-2 items-center">
                            <input type="text"
                                   name="search"
                                   placeholder="{{ __('frontend.home.search_placeholder') }}"
                                   value="{{ request('search') }}"
                                   class="flex-1 px-3 sm:px-4 py-2 rounded-lg text-gray-800 text-sm search-input border-0">
                            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-lg text-sm transition whitespace-nowrap">
                                <span class="hidden sm:inline">{{ __('frontend.home.search_button') }}</span>
                                <i class="fas fa-search sm:hidden"></i>
                            </button>
                            <a href="{{ route('ads.index') }}" class="text-xs text-gray-300 hover:text-secondary hidden sm:block whitespace-nowrap flex items-center gap-1">
                                <i class="fas fa-filter"></i>
                                {{ __('frontend.search') }} {{ __('frontend.filter') }}
                            </a>
                        </form>
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center gap-2 sm:gap-4 order-2 lg:order-3">
                        <!-- Notifications Dropdown -->
                        @auth
                            @php
                                $unreadNotificationsCount = Auth::user()->unreadNotifications->count();
                                $latestNotifications = Auth::user()->notifications()->latest()->take(5)->get();
                            @endphp
                            <div class="hidden md:block relative" x-data="{ open: false }" @click.away="open = false">
                                <button @click="open = !open" class="text-white hover:text-secondary transition relative" title="{{ __('frontend.notifications.title') }}">
                                    <i class="fas fa-bell text-lg sm:text-xl"></i>
                                    @if($unreadNotificationsCount > 0)
                                        <span class="absolute -top-1 {{ app()->getLocale() === 'ar' ? '-right-1' : '-left-1' }} bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
                                    @endif
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} top-full mt-2 w-80 sm:w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-96 overflow-hidden flex flex-col"
                                     style="display: none;">
                                    <!-- Header -->
                                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-gray-800">{{ __('frontend.notifications.title') }}</h3>
                                        @if($unreadNotificationsCount > 0)
                                            <form action="{{ route('profile.notifications.read-all') }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-primary hover:underline">
                                                    {{ __('frontend.notifications.mark_all_read') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    
                                    <!-- Notifications List -->
                                    <div class="overflow-y-auto flex-1">
                                        @forelse($latestNotifications as $notification)
                                            @php
                                                $data = $notification->data ?? [];
                                                $type = $data['type'] ?? null;

                                                $adUrl = $data['ad_url'] ?? null;
                                                $packageRequestId = $data['package_request_id'] ?? null;
                                                $hawalaTransferId = $data['hawala_transfer_id'] ?? null;
                                                $verificationRequestId = $data['verification_request_id'] ?? null;
                                                $conversationId = $data['conversation_id'] ?? null;
                                                $reportId = $data['report_id'] ?? null;
                                                $supportMessageId = $data['support_message_id'] ?? null;

                                                $clickUrl = null;
                                                if ($adUrl) {
                                                    $clickUrl = $adUrl;
                                                } elseif ($type === 'package_request_responded' && $packageRequestId) {
                                                    $clickUrl = route('profile.package-requests.show', $packageRequestId);
                                                } elseif (in_array($type, ['hawala_approved', 'hawala_rejected']) && $hawalaTransferId) {
                                                    $clickUrl = route('profile.hawala.index');
                                                } elseif (in_array($type, ['verification_approved', 'verification_rejected']) && $verificationRequestId) {
                                                    $clickUrl = route('profile.verification');
                                                } elseif ($type === 'package_activated') {
                                                    $clickUrl = route('profile.index');
                                                } elseif ($type === 'new_message' && $conversationId) {
                                                    $clickUrl = route('messages.show', $conversationId);
                                                } elseif ($type === 'report_action' && $reportId) {
                                                    $clickUrl = route('profile.reports.show', $reportId);
                                                } elseif ($type === 'support_action' && $supportMessageId) {
                                                    $clickUrl = route('profile.support-messages.show', $supportMessageId);
                                                } else {
                                                    $clickUrl = route('profile.notifications.index');
                                                }
                                            @endphp

                                            <a href="{{ $clickUrl }}"
                                               class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition {{ $notification->read_at ? '' : 'bg-blue-50' }}"
                                               onclick="event.preventDefault(); markNotificationAsRead('{{ $notification->id }}'); window.location.href='{{ $clickUrl }}';">
                                                <div class="flex items-start gap-3">
                                                    <div class="flex-shrink-0 mt-1">
                                                        <i class="fas fa-circle text-xs {{ $notification->read_at ? 'text-gray-300' : 'text-blue-500' }}"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm text-gray-800 font-medium">
                                                            {{ $notification->data['title'] ?? __('frontend.notifications.notification') }}
                                                        </p>
                                                        <p class="text-xs text-gray-600 mt-1">
                                                            {{ $notification->data['message'] ?? '' }}
                                                        </p>
                                                        <p class="text-xs text-gray-400 mt-1">
                                                            {{ $notification->created_at->diffForHumans() }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="px-4 py-8 text-center">
                                                <i class="fas fa-bell-slash text-gray-300 text-3xl mb-2"></i>
                                                <p class="text-sm text-gray-500">{{ __('frontend.notifications.no_notifications') }}</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    
                                    <!-- Footer -->
                                    @if($latestNotifications->count() > 0)
                                        <div class="px-4 py-3 border-t border-gray-200 text-center">
                                            <a href="{{ route('profile.notifications.index') }}" class="text-sm text-primary hover:underline">
                                                {{ __('frontend.notifications.view_all') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endauth
                        
                        @auth
                            <a href="{{ route('messages.index') }}" class="hidden md:block text-white hover:text-secondary transition relative" title="{{ __('frontend.messages.my_messages') }}">
                                <i class="fas fa-envelope text-lg sm:text-xl"></i>
                                @php
                                    $unreadCount = \App\Models\Message::whereHas('conversation', function($q) {
                                        $q->where(function($query) {
                                            $query->where('sender_id', Auth::id())
                                                  ->orWhere('receiver_id', Auth::id());
                                        });
                                    })
                                    ->where('sender_id', '!=', Auth::id())
                                    ->where('is_read', false)
                                    ->count();
                                @endphp
                                @if($unreadCount > 0)
                                    <span class="absolute -top-1 {{ app()->getLocale() === 'ar' ? '-right-1' : '-left-1' }} bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">{{ $unreadCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('favorites.index') }}" class="hidden md:block text-white hover:text-secondary transition relative" title="{{ __('frontend.favorites.my_favorites') }}">
                                <i class="fas fa-heart text-lg sm:text-xl"></i>
                            </a>
                        @endauth

                        <!-- Post Ad Button -->
                        <a href="{{ route('ads.create') }}" class="btn-primary px-3 sm:px-5 py-2 rounded-lg text-xs sm:text-sm font-bold whitespace-nowrap">
                            <i class="fas fa-plus ml-1"></i>
                            <span class="hidden sm:inline">{{ __('frontend.nav.add_ad') }}</span>
                            <span class="sm:hidden">{{ __('frontend.nav.add_ad') }}</span>
                        </a>

                        <!-- Language Switcher -->
                        <div class="relative language-switcher"
                             onmouseenter="showLanguageMenu(this)"
                             onmouseleave="hideLanguageMenu(this)">
                            <button class="flex items-center gap-1 text-white hover:text-secondary transition text-xs sm:text-sm">
                                <i class="fas fa-globe"></i>
                                <span class="hidden sm:inline">{{ config('app.available_locales')[app()->getLocale()] }}</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="language-menu absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50"
                                 onmouseenter="showLanguageMenu(this.closest('.language-switcher'))"
                                 onmouseleave="hideLanguageMenu(this.closest('.language-switcher'))">
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

                        <!-- Login/User Menu -->
                        @auth
                            <div class="relative user-menu"
                                onmouseenter="showUserMenu(this)"
                                onmouseleave="hideUserMenu(this)">
                                <button class="text-white hover:text-secondary transition text-xs sm:text-sm flex items-center gap-1">
                                    <i class="fas fa-user"></i>
                                    <span class="hidden md:inline">{{ Auth::user()->name }}</span>
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                <div class="user-menu-dropdown absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50"
                                     onmouseenter="showUserMenu(this.closest('.user-menu'))"
                                     onmouseleave="hideUserMenu(this.closest('.user-menu'))">
                                    <a href="{{ route('profile.index') }}" class="block px-4 py-3 hover:bg-gray-50 transition text-gray-700">
                                        <i class="fas fa-user ml-2"></i> {{ __('frontend.nav.profile') }}
                                    </a>
                                    <a href="{{ route('profile.ads.index') }}" class="block px-4 py-3 hover:bg-gray-50 transition text-gray-700">
                                        <i class="fas fa-list ml-2"></i> {{ __('frontend.profile.my_ads') }}
                                    </a>
                                    <form action="{{ route('logout') }}" method="POST" class="border-t border-gray-200" id="logout-form">
                                        @csrf
                                        <button type="submit" class="w-full text-right px-4 py-3 hover:bg-gray-50 transition text-gray-700">
                                            <i class="fas fa-sign-out-alt ml-2"></i> {{ __('frontend.nav.logout') }}
                                        </button>
                                    </form>
                                    <script>
                                        document.getElementById('logout-form')?.addEventListener('submit', function(e) {
                                            e.preventDefault();
                                            var form = this;
                                            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form.querySelector('input[name="_token"]')?.value;
                                            
                                            if (!token) {
                                                // If no token, submit normally
                                                form.submit();
                                                return;
                                            }
                                            
                                            // Create form data
                                            var formData = new FormData();
                                            formData.append('_token', token);
                                            
                                            // Send logout request
                                            fetch('{{ route("logout") }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': token,
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'Accept': 'application/json'
                                                },
                                                body: formData,
                                                credentials: 'same-origin'
                                            })
                                            .then(response => {
                                                if (response.ok || response.redirected) {
                                                    window.location.href = '{{ route("home") }}';
                                                } else {
                                                    // If failed, try normal form submission
                                                    form.submit();
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Logout error:', error);
                                                // Fallback: submit form normally
                                                form.submit();
                                            });
                                        });
                                    </script>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="text-white hover:text-secondary transition text-xs sm:text-sm">
                                <i class="fas fa-user ml-1"></i>
                                <span class="hidden sm:inline">{{ __('frontend.nav.login') }}</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Bar -->
        <div class="bg-blue-900 border-b border-blue-800">
            <div class="container mx-auto px-2 sm:px-4">
                <nav class="flex items-center gap-3 sm:gap-6 text-xs sm:text-sm py-2 overflow-x-auto">
                    <a href="{{ route('home') }}" class="text-white hover:text-secondary transition font-semibold whitespace-nowrap">
                        {{ __('frontend.nav.home') }}
                    </a>
                    <a href="{{ route('categories.index') }}" class="text-gray-300 hover:text-secondary transition whitespace-nowrap">
                        {{ __('frontend.nav.categories') }}
                    </a>
                    <a href="{{ route('ads.index') }}" class="text-gray-300 hover:text-secondary transition whitespace-nowrap">
                        {{ __('frontend.nav.ads') }}
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-8 sm:mt-12">
        <div class="container mx-auto px-2 sm:px-4 py-6 sm:py-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                <!-- About -->
                <div>
                    <img src="{{ asset('logo.png') }}" alt="{{ __('frontend.site_name') }}" class="h-10 sm:h-12 w-auto mb-3 sm:mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-secondary mb-2 sm:mb-3">{{ __('frontend.site_name') }}</h3>
                    <p class="text-gray-300 text-xs sm:text-sm">{{ __('frontend.tagline') }}</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-bold mb-3 sm:mb-4 text-base sm:text-lg">{{ __('frontend.footer.quick_links') }}</h4>
                    <ul class="space-y-2 text-xs sm:text-sm text-gray-300">
                        <li><a href="{{ route('home') }}" class="hover:text-secondary transition">{{ __('frontend.nav.home') }}</a></li>
                        <li><a href="{{ route('categories.index') }}" class="hover:text-secondary transition">{{ __('frontend.nav.categories') }}</a></li>
                        <li><a href="{{ route('ads.index') }}" class="hover:text-secondary transition">{{ __('frontend.nav.ads') }}</a></li>
                        <li><a href="{{ route('ads.create') }}" class="hover:text-secondary transition">{{ __('frontend.nav.add_ad') }}</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h4 class="font-bold mb-3 sm:mb-4 text-base sm:text-lg">{{ __('frontend.categories.title') }}</h4>
                    <ul class="space-y-2 text-xs sm:text-sm text-gray-300">
                        @php
                            $footerCategories = \App\Models\Category::active()->orderBy('order')->take(5)->get();
                        @endphp
                        @foreach($footerCategories as $cat)
                            <li>
                                <a href="{{ route('categories.show', $cat->slug) }}" class="hover:text-secondary transition">
                                    {{ $cat->getName(app()->getLocale()) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="font-bold mb-3 sm:mb-4 text-base sm:text-lg">{{ __('frontend.footer.contact_us') }}</h4>
                    <ul class="space-y-2 text-xs sm:text-sm text-gray-300">
                        <li><i class="fas fa-envelope ml-2"></i> info@a3lenha.com</li>
                        <li><i class="fas fa-phone ml-2"></i> +963 11 123 4567</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-6 sm:mt-8 pt-4 sm:pt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs sm:text-sm text-gray-400">
                    <p>&copy; {{ date('Y') }} {{ __('frontend.site_name') }}. {{ __('frontend.footer.all_rights_reserved') }}.</p>
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <a href="{{ route('help.index') }}" class="hover:text-secondary transition">
                            <i class="fas fa-question-circle ml-1"></i>
                            {{ __('frontend.help.title') }}
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="{{ route('app-info') }}" class="hover:text-secondary transition">
                            <i class="fas fa-info-circle ml-1"></i>
                            {{ __('frontend.app_info.title') }}
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="{{ route('legal.terms') }}" class="hover:text-secondary transition">
                            {{ __('frontend.legal.terms_title') }}
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="{{ route('legal.privacy') }}" class="hover:text-secondary transition">
                            {{ __('frontend.legal.privacy_title') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        let languageMenuTimeout = null;
        let userMenuTimeout = null;

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
                // Set timeout to hide after 500ms (half a second)
                languageMenuTimeout = setTimeout(function() {
                    menu.classList.add('hidden');
                    languageMenuTimeout = null;
                }, 500);
            }
        }

        function showUserMenu(element) {
            // Clear any existing timeout
            if (userMenuTimeout) {
                clearTimeout(userMenuTimeout);
                userMenuTimeout = null;
            }

            const menu = element.querySelector('.user-menu-dropdown');
            if (menu) {
                menu.classList.remove('hidden');
            }
        }

        function hideUserMenu(element) {
            const menu = element.querySelector('.user-menu-dropdown');
            if (menu) {
                // Set timeout to hide after 500ms (half a second)
                userMenuTimeout = setTimeout(function() {
                    menu.classList.add('hidden');
                    userMenuTimeout = null;
                }, 500);
            }
        }
    </script>

    @stack('scripts')
    <script>
        function markNotificationAsRead(notificationId) {
            fetch(`{{ url('/profile/notifications') }}/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        }
    </script>
    
    @stack('scripts')
</body>
</html>

