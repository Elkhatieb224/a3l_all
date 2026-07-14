@extends('frontend.layouts.app')

@section('title', __('frontend.profile.my_ads'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-primary mb-2">{{ __('frontend.profile.my_ads') }}</h1>
                            <p class="text-gray-600 text-sm">{{ __('frontend.profile.my_ads_management.manage_your_ads') }}</p>
                        </div>
                        <a href="{{ route('ads.create') }}" class="btn-primary px-3 sm:px-5 py-2 rounded-lg text-xs sm:text-sm font-bold whitespace-nowrap mt-4 sm:mt-0">
                            <i class="fas fa-plus ml-1"></i>
                            <span class="hidden sm:inline">{{ __('frontend.nav.add_ad') }}</span>
                            <span class="sm:hidden">{{ __('frontend.nav.add_ad') }}</span>
                        </a>
                    </div>

                    <!-- Status Filters -->
                    <div class="flex flex-wrap gap-2 mb-6 pb-6 border-b border-gray-200">
                        <a href="{{ route('profile.ads.index', ['status' => 'all']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status', 'all') === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('frontend.profile.my_ads_management.all') }} ({{ $statusCounts['all'] }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'active']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'active' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('frontend.profile.my_ads_management.active') }} ({{ $statusCounts['active'] }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'pending']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('frontend.profile.my_ads_management.pending') }} ({{ $statusCounts['pending'] }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'rejected']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'rejected' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('frontend.profile.my_ads_management.rejected') }} ({{ $statusCounts['rejected'] }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'expired']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'expired' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('frontend.profile.my_ads_management.expired') }} ({{ $statusCounts['expired'] }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'suspended']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'suspended' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('frontend.profile.my_ads_management.status.suspended') }} ({{ $statusCounts['suspended'] ?? 0 }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'featured']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'featured' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.my_ads_management.featured') }}" class="w-4 h-4 inline-block ml-1"> {{ __('frontend.profile.my_ads_management.featured') }} ({{ $statusCounts['featured'] ?? 0 }})
                        </a>
                        <a href="{{ route('profile.ads.index', ['status' => 'urgent']) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request('status') === 'urgent' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.my_ads_management.urgent') }}" class="w-4 h-4 inline-block ml-1"> {{ __('frontend.profile.my_ads_management.urgent') }} ({{ $statusCounts['urgent'] ?? 0 }})
                        </a>
                    </div>

                    <!-- Ads Grid -->
                    @if($ads->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($ads as $ad)
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                                    <!-- Image -->
                                    <div class="relative h-40 bg-gray-200">
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
                                            <div class="w-full h-full hidden items-center justify-center bg-gray-100">
                                                <i class="fas fa-image text-gray-400 text-4xl"></i>
                                            </div>
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                                <i class="fas fa-image text-gray-400 text-4xl"></i>
                                            </div>
                                        @endif
                                        
                                        <!-- Badges -->
                                        <div class="absolute top-2 {{ app()->getLocale() === 'ar' ? 'right-2' : 'left-2' }} flex flex-wrap gap-1">
                                            <span class="px-2 py-1 rounded text-xs font-semibold
                                                {{ $ad->status === 'active' ? 'bg-green-500 text-white' :
                                                   ($ad->status === 'pending' ? 'bg-yellow-500 text-white' :
                                                   ($ad->status === 'rejected' ? 'bg-red-500 text-white' :
                                                   ($ad->status === 'suspended' ? 'bg-orange-500 text-white' : 'bg-gray-500 text-white'))) }}">
                                                {{ __("frontend.profile.my_ads_management.status.{$ad->status}") }}
                                            </span>
                                            @if($ad->is_featured)
                                                <span class="bg-gradient-to-r from-yellow-400 to-amber-500 text-white px-2 py-1 rounded text-xs font-bold shadow-sm inline-flex items-center gap-1">
                                                    <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.my_ads_management.featured') }}" class="w-3.5 h-3.5">
                                                    {{ __('frontend.profile.my_ads_management.featured') }}
                                                </span>
                                            @endif
                                            @if($ad->is_urgent)
                                                <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-bold inline-flex items-center gap-1">
                                                    <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.my_ads_management.urgent') }}" class="w-3.5 h-3.5">
                                                    {{ __('frontend.profile.my_ads_management.urgent') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-4">
                                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">{{ $ad->title }}</h3>
                                        
                                        @if($ad->display_price)
                                            <div class="text-lg font-bold text-primary mb-2">
                                                {{ $ad->display_price }}
                                            </div>
                                        @endif

                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                            <span><i class="fas fa-eye ml-1"></i> {{ $ad->views_count }}</span>
                                            <span>{{ $ad->created_at->diffForHumans() }}</span>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('profile.ads.show', $ad->uid) }}" 
                                               class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg text-sm transition">
                                                <i class="fas fa-eye"></i> {{ __('frontend.view') }}
                                            </a>
                                            <a href="{{ route('profile.ads.edit', $ad->uid) }}" 
                                               class="flex-1 text-center bg-yellow-50 text-yellow-600 hover:bg-yellow-100 py-2 rounded-lg text-sm transition">
                                                <i class="fas fa-edit"></i> {{ __('frontend.edit') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $ads->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg mb-4">{{ __('frontend.profile.my_ads_management.no_ads_found') }}</p>
                            <a href="{{ route('ads.create') }}" class="btn-primary px-3 sm:px-5 py-2 rounded-lg text-xs sm:text-sm font-bold whitespace-nowrap inline-block">
                                <i class="fas fa-plus ml-1"></i>
                                <span class="hidden sm:inline">{{ __('frontend.nav.add_ad') }}</span>
                                <span class="sm:hidden">{{ __('frontend.nav.add_ad') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

