@extends('frontend.layouts.app')

@section('title', __('frontend.home.welcome'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-6">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            <!-- Sidebar -->
            <aside class="w-full lg:w-64 flex-shrink-0 hidden lg:block">
                <div class="bg-white rounded-lg shadow-md p-4 sticky top-24 max-h-[calc(100vh-8rem)] overflow-y-auto">
                    <!-- Featured Ads -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <h3 class="font-bold text-yellow-600 mb-3 text-sm">
                            <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.home.featured_ads') }}" class="w-4 h-4 inline-block ml-1">
                            {{ __('frontend.home.featured_ads') }}
                        </h3>
                        <div class="space-y-2">
                            <a href="{{ route('ads.index', ['featured' => 1]) }}"
                               class="block text-sm text-gray-700 hover:text-primary transition">
                                {{ __('frontend.home.view_all_featured') }}
                            </a>
                        </div>
                    </div>

                    <!-- Urgent Ads -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <h3 class="font-bold text-red-600 mb-3 text-sm">
                            <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.home.urgent_ads') }}" class="w-4 h-4 inline-block ml-1">
                            {{ __('frontend.home.urgent_ads') }}
                        </h3>
                        <div class="space-y-2">
                            <a href="{{ route('ads.index', ['urgent' => 1]) }}"
                               class="block text-sm text-gray-700 hover:text-primary transition">
                                {{ __('frontend.home.view_all_urgent') }}
                            </a>
                        </div>
                    </div>

                    <!-- Time Filter -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <h3 class="font-bold text-red-600 mb-3 text-sm">
                            <i class="fas fa-clock ml-1"></i>
                            {{ __('frontend.home.latest_urgent_ads') }}
                        </h3>
                        <div class="space-y-2">
                            <a href="{{ route('ads.index', ['time' => 'month']) }}"
                               class="block text-sm text-gray-700 hover:text-primary transition">
                                {{ __('frontend.home.one_month') }}
                            </a>
                            <a href="{{ route('ads.index', ['time' => 'week']) }}"
                               class="block text-sm text-gray-700 hover:text-primary transition">
                                {{ __('frontend.home.one_week') }}
                            </a>
                            <a href="{{ route('ads.index', ['time' => '48h']) }}"
                               class="block text-sm text-gray-700 hover:text-primary transition">
                                {{ __('frontend.home.last_48_hours') }}
                            </a>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="space-y-4">
                        @foreach($categories as $category)
                            <div>
                                <a href="{{ route('categories.show', $category->slug) }}"
                                   class="font-bold text-gray-800 hover:text-primary transition block mb-2 text-sm">
                                    {{ $category->getName(app()->getLocale()) }}
                                    <span class="text-gray-500 text-xs">
                                        <span>({{ $category->ads_count }} {{ __('frontend.categories.ads_count') }})</span>
                                    </span>
                                </a>
                                @if($category->subcategories->count() > 0)
                                    <ul class="space-y-1 mr-4">
                                        @foreach($category->subcategories->take(5) as $subcategory)
                                            <li>
                                                <a href="{{ route('categories.subcategory', [$category->slug, $subcategory->slug]) }}"
                                                   class="text-xs text-gray-600 hover:text-primary transition block">
                                                    {{ $subcategory->getName(app()->getLocale()) }}
                                                    <span class="text-gray-400">({{ $subcategory->ads_count ?? 0 }})</span>
                                                </a>
                                            </li>
                                        @endforeach
                                        @if($category->subcategories->count() > 5)
                                            <li>
                                                <a href="{{ route('categories.show', $category->slug) }}"
                                                   class="text-xs text-primary hover:underline">
                                                    {{ __('frontend.home.show_all') }} ({{ $category->subcategories->count() }})
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1">
                <!-- Featured Ads Grid -->
                @if($featuredAds->count() > 0)
                <section class="mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-3 sm:mb-4">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800">
                            {{ __('frontend.home.featured_ads') }}
                        </h2>
                        <a href="{{ route('ads.index', ['featured' => 1]) }}"
                           class="text-xs sm:text-sm text-primary hover:text-secondary transition">
                            {{ __('frontend.home.view_all') }} <i class="fas fa-arrow-left text-xs"></i>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                        @foreach($featuredAds->take(10) as $ad)
                            @include('frontend.partials.ad-card', ['ad' => $ad])
                        @endforeach
                    </div>
                </section>
                @endif

                <!-- Urgent Ads -->
                @if($urgentAds->count() > 0)
                <section class="mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-3 sm:mb-4">
                        <h2 class="text-lg sm:text-xl font-bold text-red-600">
                            <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.home.urgent_ads') }}" class="w-4 h-4 inline-block ml-1">
                            {{ __('frontend.home.urgent_ads') }}
                        </h2>
                        <a href="{{ route('ads.index', ['urgent' => 1]) }}"
                           class="text-xs sm:text-sm text-primary hover:text-secondary transition">
                            {{ __('frontend.home.view_all') }} <i class="fas fa-arrow-left text-xs"></i>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                        @foreach($urgentAds->take(10) as $ad)
                            @include('frontend.partials.ad-card', ['ad' => $ad])
                        @endforeach
                    </div>
                </section>
                @endif

                <!-- Latest Ads -->
                <section class="mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 mb-3 sm:mb-4">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800">
                            {{ __('frontend.home.latest_ads') }}
                        </h2>
                        <a href="{{ route('ads.index') }}"
                           class="text-xs sm:text-sm text-primary hover:text-secondary transition">
                            {{ __('frontend.home.view_all') }} <i class="fas fa-arrow-left text-xs"></i>
                        </a>
                    </div>
                    @if($latestAds->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                            @foreach($latestAds->take(20) as $ad)
                                @include('frontend.partials.ad-card', ['ad' => $ad])
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white rounded-lg shadow-md p-12 text-center">
                            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg">{{ __('frontend.home.no_ads') }}</p>
                        </div>
                    @endif
                </section>

                <!-- Recent Searches -->
                @if(Auth::check() && $recentSearches && $recentSearches->count() > 0)
                <section class="mb-6 sm:mb-8 bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-3 sm:mb-4 text-base sm:text-lg">{{ __('frontend.home.recent_searches') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($recentSearches as $search)
                            <a href="{{ route('ads.index', ['search' => $search]) }}"
                               class="px-3 py-1 bg-gray-100 hover:bg-secondary hover:text-primary rounded-full text-sm transition">
                                {{ $search }}
                            </a>
                        @endforeach
                    </div>
                </section>
                @endif

                <!-- Categories Grid -->
                <section class="mb-6 sm:mb-8">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-3 sm:mb-4">
                        {{ __('frontend.home.browse_categories') }}
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-4">
                        @foreach($categories as $category)
                            <a href="{{ route('categories.show', $category->slug) }}"
                               class="bg-white rounded-lg shadow-md p-4 hover:shadow-xl transition group text-center">
                                @if($category->icon)
                                    <img src="{{ asset('storage/' . $category->icon) }}"
                                         alt="{{ $category->getName(app()->getLocale()) }}"
                                         class="w-12 h-12 mx-auto mb-2 object-contain">
                                @else
                                    <div class="w-12 h-12 mx-auto mb-2 bg-primary rounded-lg flex items-center justify-center">
                                        <i class="fas fa-folder text-secondary"></i>
                                    </div>
                                @endif
                                <h3 class="font-semibold text-gray-800 group-hover:text-primary transition text-sm">
                                    {{ $category->getName(app()->getLocale()) }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $category->ads_count }} {{ __('frontend.categories.ads_count') }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>
@endsection
