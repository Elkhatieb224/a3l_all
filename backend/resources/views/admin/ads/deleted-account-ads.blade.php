@extends('admin.layouts.app')

@section('title', __('admin.ads.deleted_account_ads.title'))
@section('page-title', __('admin.ads.deleted_account_ads.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('admin.ads.deleted_account_ads.title') }}</h2>
        <a href="{{ route('admin.ads.index') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-bold flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            {{ __('admin.ads.deleted_account_ads.back_to_ads') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ __('admin.ads.deleted_account_ads.search_placeholder') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <select name="category_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.ads.deleted_account_ads.all_categories') }}</option>
                <!-- Add categories dynamically -->
            </select>

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.ads.deleted_account_ads.search') }}
            </button>
        </form>
    </div>

    <!-- Ads Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($ads as $ad)
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition border-r-4 border-red-500">
                <!-- Image -->
                <div class="h-48 bg-gray-200 relative overflow-hidden">
                    @php
                        $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                        $images = $images ?? [];
                        $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                        $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                    @endphp
                    @if($firstImagePath)
                        <img src="{{ asset('storage/' . $firstImagePath) }}" 
                             alt="{{ $ad->title }}" 
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-full hidden items-center justify-center bg-gradient-to-br from-primary to-blue-900">
                            <i class="fas fa-image text-4xl text-white opacity-50"></i>
                        </div>
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary to-blue-900">
                            <i class="fas fa-image text-4xl text-white opacity-50"></i>
                        </div>
                    @endif

                    <!-- Badges -->
                    <div class="absolute top-2 left-2 flex gap-2">
                        <span class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded">
                            <i class="fas fa-user-slash"></i> {{ __('admin.ads.deleted_account_ads.deleted_account_badge') }}
                        </span>
                        @if($ad->is_featured)
                            <span class="px-2 py-1 bg-gradient-to-r from-yellow-400 to-amber-500 text-white text-xs font-bold rounded shadow-sm ring-1 ring-amber-300/70">
                                <i class="fas fa-star"></i> {{ __('admin.ads.deleted_account_ads.featured') }}
                            </span>
                        @endif
                        @if($ad->is_urgent)
                            <span class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded">
                                <i class="fas fa-bolt"></i> {{ __('admin.ads.deleted_account_ads.urgent') }}
                            </span>
                        @endif
                    </div>

                    <span class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-semibold
                        {{ $ad->status === 'active' ? 'bg-green-500 text-white' :
                           ($ad->status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-red-500 text-white') }}">
                        {{ $ad->status }}
                    </span>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-500 font-mono">UID: {{ $ad->uid }}</span>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2 line-clamp-2">{{ $ad->title }}</h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $ad->description }}</p>

                    <div class="flex items-center justify-between mb-3 pb-3 border-b">
                        <span class="text-lg font-bold text-primary">
                            @if($ad->display_price)
                                {{ $ad->display_price }}
                            @else
                                <span class="text-sm text-gray-500">{{ __('admin.ads.deleted_account_ads.price_not_specified') }}</span>
                            @endif
                        </span>
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-eye ml-1"></i>
                            {{ $ad->views_count }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                        @if($ad->user)
                            <img src="{{ $ad->user->avatar ? asset('storage/' . $ad->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($ad->user->name) }}"
                                 alt="{{ $ad->user->name }}"
                                 class="w-6 h-6 rounded-full">
                            <span>{{ $ad->user->name }}</span>
                        @else
                            <span class="text-red-600">{{ __('admin.ads.deleted_account_ads.deleted_account') }}</span>
                        @endif
                        <span class="mx-1">•</span>
                        <span>{{ $ad->category->name_ar ?? '-' }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.ads.show', $ad->uid) }}"
                           class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-eye"></i> {{ __('admin.ads.deleted_account_ads.view') }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-bullhorn text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.ads.deleted_account_ads.no_ads') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($ads->hasPages())
        <div class="bg-white rounded-xl shadow-md p-4">
            {{ $ads->links() }}
        </div>
    @endif
</div>
@endsection

