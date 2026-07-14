@extends('frontend.layouts.app')

@section('title', __('frontend.profile.my_ads_management.stats'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 pb-6 border-b border-gray-200">
                        <div>
                            <h1 class="text-2xl font-bold text-primary mb-2">{{ __('frontend.profile.my_ads_management.stats') }}</h1>
                            <p class="text-gray-600 text-sm">{{ $ad->title }}</p>
                        </div>
                        <a href="{{ route('profile.ads.show', $ad->uid) }}" 
                           class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-sm transition mt-4 sm:mt-0">
                            <i class="fas fa-arrow-right ml-2"></i> {{ __('frontend.back') }}
                        </a>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Views -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-eye text-blue-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.views') }}</h3>
                            <p class="text-3xl font-bold text-primary">{{ number_format($stats['views']) }}</p>
                        </div>

                        <!-- Status -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-info-circle text-green-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.status_label') }}</h3>
                            <p class="text-xl font-bold text-primary">
                                {{ __('frontend.profile.my_ads_management.status.' . $stats['status']) }}
                            </p>
                        </div>

                        <!-- Created Date -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-calendar-plus text-purple-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.created_at') }}</h3>
                            <p class="text-lg font-bold text-primary">{{ $stats['created_at']->format('Y-m-d') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $stats['created_at']->diffForHumans() }}</p>
                        </div>

                        <!-- Published Date -->
                        @if($stats['published_at'])
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-calendar-check text-yellow-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.published_at') }}</h3>
                            <p class="text-lg font-bold text-primary">{{ $stats['published_at']->format('Y-m-d') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $stats['published_at']->diffForHumans() }}</p>
                        </div>
                        @endif

                        <!-- Expires Date -->
                        @if($stats['expires_at'])
                        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-calendar-times text-red-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.expires_at') }}</h3>
                            <p class="text-lg font-bold text-primary">{{ $stats['expires_at']->format('Y-m-d') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $stats['expires_at']->diffForHumans() }}</p>
                        </div>
                        @endif

                        <!-- Featured -->
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-star text-orange-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.featured') }}</h3>
                            <p class="text-xl font-bold text-primary">
                                {{ $stats['is_featured'] ? __('frontend.yes') : __('frontend.no') }}
                            </p>
                        </div>

                        <!-- Urgent -->
                        <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-bolt text-pink-600 text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.urgent') }}</h3>
                            <p class="text-xl font-bold text-primary">
                                {{ $stats['is_urgent'] ? __('frontend.yes') : __('frontend.no') }}
                            </p>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-bold text-primary mb-4">{{ __('frontend.profile.my_ads_management.ad_info') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">{{ __('frontend.ads.category') }}</p>
                                <p class="font-semibold text-gray-800">{{ $ad->category->getName(app()->getLocale()) }}</p>
                            </div>
                            @if($ad->subcategory)
                            <div>
                                <p class="text-sm text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.subcategory') }}</p>
                                <p class="font-semibold text-gray-800">{{ $ad->subcategory->getName(app()->getLocale()) }}</p>
                            </div>
                            @endif
                            @if($ad->display_price)
                            <div>
                                <p class="text-sm text-gray-600 mb-1">{{ __('frontend.ads.price') }}</p>
                                <p class="font-semibold text-gray-800">{{ $ad->display_price }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-sm text-gray-600 mb-1">{{ __('frontend.profile.my_ads_management.uid') }}</p>
                                <p class="font-semibold text-gray-800 font-mono">{{ $ad->uid }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

