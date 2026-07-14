@extends('frontend.layouts.app')

@section('title', __('frontend.ads.title'))

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
    <div class="mb-4 sm:mb-8">
        <div class="flex items-center justify-between gap-3 mb-2">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800">
                <i class="fas fa-list text-secondary ml-2"></i>
                {{ __('frontend.ads.title') }}
            </h1>
            @auth
                <form action="{{ route('profile.saved-searches.store') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="category_id" value="{{ request('category') }}">
                    <button type="submit" class="px-3 py-2 rounded-lg bg-primary text-white hover:bg-secondary transition text-sm">
                        {{ __('frontend.saved_searches.save_search') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 rounded-lg bg-primary text-white hover:bg-secondary transition text-sm">
                    {{ __('frontend.saved_searches.save_search') }}
                </a>
            @endauth
        </div>
        <p class="text-sm sm:text-base text-gray-600">{{ __('frontend.tagline') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Sidebar - Filters -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 sticky top-20">
                <h3 class="font-bold text-gray-800 mb-4">
                    <i class="fas fa-filter text-secondary ml-2"></i>
                    {{ __('frontend.filter') }}
                </h3>

                <form action="{{ route('ads.index') }}" method="GET" class="space-y-4">
                    <!-- Search (min 3 chars) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.search') }}</label>
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="{{ __('frontend.home.search_placeholder') }}"
                               minlength="3"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                        <p class="text-xs text-gray-500 mt-1">{{ __('frontend.ads.search_min_chars', ['count' => 3]) }}</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.categories.title') }}</label>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            <option value="">{{ __('frontend.categories.all_categories') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->getName(app()->getLocale()) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1 px-4 py-2 rounded-lg">
                            {{ __('frontend.search') }}
                        </button>
                        <a href="{{ route('ads.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            {{ __('frontend.clear') }}
                        </a>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Main Content - Ads List -->
        <main class="lg:col-span-3">
            @if(!empty($searchTerm) && count($searchCategories) > 0)
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
                    <h3 class="font-bold text-gray-800 mb-3">
                        {{ __('frontend.ads.categories_with_results') }}
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($searchCategories as $sc)
                            <a href="{{ route('ads.index', ['search' => $searchTerm, 'category' => $sc->id]) }}"
                               class="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-200 hover:border-secondary hover:bg-gray-50 transition">
                                <span class="font-semibold text-gray-800 text-center">{{ $sc->name }}</span>
                                <span class="text-sm text-gray-500 mt-1">{{ number_format($sc->matching_ads_count) }} {{ __("frontend.categories.ads_count") }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($ads->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                    @foreach($ads as $ad)
                        @include('frontend.partials.ad-card', ['ad' => $ad])
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $ads->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-4">{{ __('frontend.ads.no_ads_found') }}</p>
                    <a href="{{ route('ads.create') }}" class="btn-primary inline-block px-6 py-3 rounded-lg">
                        <i class="fas fa-plus ml-2"></i>
                        {{ __('frontend.nav.add_ad') }}
                    </a>
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

