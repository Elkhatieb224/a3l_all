<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.dashboard')) - {{ __('admin.site_name') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#002C60',
                        secondary: '#FFD600',
                        white: '#FFFFFF',
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Quill Editor (Free, No API Key Required) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <!-- Arabic Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        [dir="rtl"] .sidebar-link {
            transition: all 0.3s ease;
        }

        [dir="rtl"] .sidebar-link:hover {
            background-color: rgba(255, 214, 0, 0.1);
            border-right: 4px solid #FFD600;
        }

        [dir="rtl"] .sidebar-link.active {
            background-color: rgba(255, 214, 0, 0.15);
            border-right: 4px solid #FFD600;
            font-weight: 600;
        }

        [dir="ltr"] .sidebar-link {
            transition: all 0.3s ease;
        }

        [dir="ltr"] .sidebar-link:hover {
            background-color: rgba(255, 214, 0, 0.1);
            border-left: 4px solid #FFD600;
        }

        [dir="ltr"] .sidebar-link.active {
            background-color: rgba(255, 214, 0, 0.15);
            border-left: 4px solid #FFD600;
            font-weight: 600;
        }

        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 44, 96, 0.15);
        }

        .btn-primary {
            background-color: #FFD600;
            color: #002C60;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #ffc400;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 214, 0, 0.4);
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-primary text-white flex-shrink-0 overflow-y-auto">
            <div class="p-6 border-b border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ asset('logo.png') }}" alt="{{ __('admin.site_name') }}" class="h-8 w-auto">
                    <h1 class="text-2xl font-bold text-secondary">{{ __('admin.site_name') }}</h1>
                </div>
                <p class="text-sm text-gray-300 mt-1">{{ __('admin.control_panel') }}</p>
            </div>

            <nav class="p-4">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                    <i class="fas fa-home text-lg"></i>
                    <span>{{ __('admin.nav.dashboard') }}</span>
                </a>

                @if(auth('admin')->user()->isAdmin())
                <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="fas fa-users text-lg"></i>
                    <span>{{ __('admin.nav.users') }}</span>
                </a>
                @endif

                <a href="{{ route('admin.categories.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
                    <i class="fas fa-folder text-lg"></i>
                    <span>{{ __('admin.nav.categories') }}</span>
                </a>

                <a href="{{ route('admin.ads.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.ads*') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn text-lg"></i>
                    <span>{{ __('admin.nav.ads') }}</span>
                </a>

                @if(auth('admin')->user()->isAdmin())
                <a href="{{ route('admin.notifications.create') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}">
                    <i class="fas fa-bell text-lg"></i>
                    <span>{{ __('admin.nav.notifications') }}</span>
                </a>
                @endif

                @if(auth('admin')->user()->isAdmin())
                <a href="{{ route('admin.packages.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.packages*') && !request()->routeIs('admin.package-requests*') ? 'active' : '' }}">
                    <i class="fas fa-box text-lg"></i>
                    <span>{{ __('admin.nav.packages') }}</span>
                </a>
                <a href="{{ route('admin.package-requests.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.package-requests*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list text-lg"></i>
                    <span>{{ __('admin.nav.package_requests') }}</span>
                </a>

                <a href="{{ route('admin.payments.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card text-lg"></i>
                    <span>{{ __('admin.nav.payments') }}</span>
                </a>

                <a href="{{ route('admin.hawala-transfers.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.hawala-transfers*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt text-lg"></i>
                    <span>{{ __('admin.nav.hawala_transfers') }}</span>
                </a>
                @endif

                <a href="{{ route('admin.reports.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                    <i class="fas fa-flag text-lg"></i>
                    <span>{{ __('admin.nav.reports') }}</span>
                </a>

                @if(auth('admin')->user()->isAdmin() || auth('admin')->user()->isSupportAgent())
                <a href="{{ route('admin.faqs.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.faqs*') ? 'active' : '' }}">
                    <i class="fas fa-question-circle text-lg"></i>
                    <span>{{ __('admin.nav.faqs') }}</span>
                </a>

                <a href="{{ route('admin.support.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.support*') ? 'active' : '' }}">
                    <i class="fas fa-headset text-lg"></i>
                    <span>{{ __('admin.nav.support') }}</span>
                </a>
                @endif

                @if(auth('admin')->user()->isAdmin())
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog text-lg"></i>
                    <span>{{ __('admin.nav.settings') }}</span>
                </a>

                <a href="{{ route('admin.dynamic-regions.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.dynamic-regions*') ? 'active' : '' }}">
                    <i class="fas fa-map-marked-alt text-lg"></i>
                    <span>{{ __('admin.nav.dynamic_regions') }}</span>
                </a>
                <a href="{{ route('admin.geo-divisions.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.geo-divisions*') ? 'active' : '' }}">
                    <i class="fas fa-globe-europe text-lg"></i>
                    <span>{{ __('admin.nav.geo_catalog') }}</span>
                </a>

                <a href="{{ route('admin.translations.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.translations*') ? 'active' : '' }}">
                    <i class="fas fa-language text-lg"></i>
                    <span>ملفات الترجمة</span>
                </a>
                @endif

                @if(auth('admin')->user()->isSuperAdmin())
                <a href="{{ route('admin.admins.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.admins*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield text-lg"></i>
                    <span>{{ __('admin.nav.admins') }}</span>
                </a>

                <a href="{{ route('admin.logs.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.logs*') ? 'active' : '' }}">
                    <i class="fas fa-history text-lg"></i>
                    <span>{{ __('admin.nav.logs') }}</span>
                </a>

                <a href="{{ route('admin.login-ip-blocks.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.login-ip-blocks*') ? 'active' : '' }}">
                    <i class="fas fa-ban text-lg"></i>
                    <span>{{ __('admin.nav.login_ip_blocks') }}</span>
                </a>
                @endif

                @if(auth('admin')->user()->isAdmin())
                <a href="{{ route('admin.reporting.index') }}" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.reporting*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar text-lg"></i>
                    <span>{{ __('admin.reports_analytics') }}</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <button id="menuToggle" class="text-gray-600 hover:text-primary lg:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-primary">@yield('page-title', __('admin.dashboard'))</h2>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Profile Link -->
                        <a href="{{ route('admin.profile.index') }}" class="text-gray-600 hover:text-primary transition">
                            <i class="fas fa-user-circle text-xl"></i>
                        </a>

                        <!-- Language Switcher -->
                        <div class="relative language-switcher"
                             onmouseenter="showAdminLangMenu(this)"
                             onmouseleave="hideAdminLangMenu(this)">
                            <button class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-globe text-primary"></i>
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ config('app.available_locales')[app()->getLocale()] }}
                                </span>
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>

                            <div class="language-menu absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
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
                                            <i class="fas fa-check text-green-500 mr-auto"></i>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">{{ auth('admin')->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth('admin')->user()->role }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <img src="{{ auth('admin')->user()->avatar ? asset('storage/' . auth('admin')->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth('admin')->user()->name) }}"
                                 alt="Avatar"
                                 class="w-10 h-10 rounded-full border-2 border-secondary">

                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-700 px-3 py-2 rounded-lg hover:bg-red-50 transition">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-3">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle')?.addEventListener('click', function() {
            document.querySelector('aside').classList.toggle('hidden');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.bg-green-100, .bg-red-100').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);

        document.querySelectorAll('form.admin-action-form').forEach(function(form) {
            form.addEventListener('submit', function() {
                var btn = form.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> {{ __("admin.processing") }}';
                }
            });
        });
    </script>

    @stack('scripts')
    <script>
        function showAdminLangMenu(el) {
            clearTimeout(el.dataset.timer);
            const menu = el.querySelector('.language-menu');
            if (menu) menu.classList.remove('hidden');
        }
        function hideAdminLangMenu(el) {
            const menu = el.querySelector('.language-menu');
            el.dataset.timer = setTimeout(() => {
                if (menu) menu.classList.add('hidden');
            }, 1000); // يبقى ظاهراً ثانية بعد الابتعاد
        }
    </script>
</body>
</html>

