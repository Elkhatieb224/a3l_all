@extends('frontend.layouts.app')

@section('title', __('frontend.packages.title'))

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-2 sm:px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 mb-4">{{ __('frontend.packages.title') }}</h1>
            <p class="text-gray-600 text-lg">{{ __('frontend.packages.subtitle') }}</p>
        </div>

        @if(session('error'))
            <div class="max-w-4xl mx-auto mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                <i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="max-w-4xl mx-auto mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
                <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
            </div>
        @endif

        @auth
            <!-- Your Current Plan: Package details, remaining limits, features -->
            <div class="max-w-4xl mx-auto mb-8 bg-white rounded-lg shadow-md p-6 border-t-4 border-primary">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">{{ __('frontend.packages.your_current_plan') }}</h3>
                        <p class="text-primary font-bold text-xl">{{ $currentPlanName ?? '' }}</p>
                    </div>
                    @if(isset($remainingAds) && $remainingAds > 0)
                        <a href="{{ route('ads.create') }}" class="btn-primary px-6 py-3 rounded-lg">
                            <i class="fas fa-plus ml-2"></i>
                            {{ __('frontend.ads.create_ad') }}
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">{{ __('frontend.packages.remaining_ads') }}</p>
                        <p class="text-2xl font-bold text-primary">
                            {{ $remainingAds ?? 0 }} / {{ $adsLimit ?? $freeAdsLimit }}
                        </p>
                    </div>
                    @if(isset($featuredLimit) && $featuredLimit > 0)
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.ads.featured') }} {{ __('frontend.packages.remaining_ads') }}</p>
                            <p class="text-xl font-bold text-yellow-700">{{ $remainingFeatured ?? 0 }} / {{ $featuredLimit }}</p>
                        </div>
                    @endif
                    @if(isset($urgentLimit) && $urgentLimit > 0)
                        <div class="bg-red-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.ads.urgent') }} {{ __('frontend.packages.remaining_ads') }}</p>
                            <p class="text-xl font-bold text-red-700">{{ $remainingUrgent ?? 0 }} / {{ $urgentLimit }}</p>
                        </div>
                    @endif
                    @if($planExpiresAt)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.packages.expires_at') }}</p>
                            <p class="text-lg font-semibold text-gray-800">{{ $planExpiresAt->translatedFormat('d F Y') }}</p>
                        </div>
                    @endif
                </div>

                @if(!empty($currentPlanFeatures))
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.packages.your_features') }}</p>
                        <ul class="flex flex-wrap gap-2">
                            @foreach($currentPlanFeatures as $feature)
                                @php
                                    $isEmpty = is_array($feature) ? empty($feature) : empty(trim((string) $feature));
                                @endphp
                                @if(!$isEmpty)
                                    <li class="flex items-center gap-2 text-sm bg-green-50 text-green-800 px-3 py-1.5 rounded-full">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        @if($feature === 'feature_ads_limit')
                                            {{ __('frontend.packages.feature_ads_limit', ['count' => $adsLimit ?? 0]) }}
                                        @elseif(is_array($feature) && count($feature) >= 2 && in_array($feature[0] ?? '', ['feature_featured_ads_limit', 'feature_urgent_ads_limit']))
                                            {{ __('frontend.packages.' . $feature[0], ['count' => $feature[1] ?? 0]) }}
                                        @elseif(is_string($feature) && (str_starts_with($feature, 'feature_') || str_starts_with($feature, 'free_feature_')))
                                            {{ __('frontend.packages.' . $feature) }}
                                        @else
                                            {{ is_array($feature) ? ($feature[0] ?? '') : $feature }}
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(isset($activeSubscriptions) && $activeSubscriptions->isNotEmpty())
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.packages.active_packages') }}</p>
                        <div class="space-y-2">
                            @foreach($activeSubscriptions as $sub)
                                <div class="flex items-center justify-between bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                                    <span class="font-medium text-blue-900">
                                        {{ $sub->package?->getName(app()->getLocale()) ?? '-' }}
                                    </span>
                                    <span class="text-sm text-blue-800">
                                        {{ __('frontend.packages.expires_at') }}:
                                        {{ optional($sub->expires_at)->translatedFormat('d F Y') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endauth

        <!-- Packages Grid -->
        @if($packages->count() > 0)
            <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($packages as $package)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-4 border-secondary transform hover:scale-105 transition duration-300">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="text-center mb-6">
                                <h3 class="text-2xl font-bold text-primary mb-2">{{ $package->getName(app()->getLocale()) }}</h3>
                                @if($package->description_ar || $package->description_en || $package->description_tr)
                                    <p class="text-sm text-gray-600">{{ $package->getDescription(app()->getLocale()) }}</p>
                                @endif
                            </div>

                            <!-- Price -->
                            <div class="text-center mb-6 py-4 bg-gradient-to-r from-primary to-blue-900 rounded-lg">
                                <div class="text-4xl font-bold text-secondary">
                                    {{ number_format($package->price, 0) }}
                                </div>
                                <div class="text-white text-sm mt-1">
                                    {{ format_price($package->price, 2, $package->currency) }}
                                    <span class="text-xs">/ {{ $package->duration_days }} {{ __('frontend.packages.days') }}</span>
                                </div>
                            </div>

                            <!-- Features -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>{{ $package->ads_limit }} {{ __('frontend.ads.total_ads') }}</span>
                                </div>

                                @if($package->featured_ads)
                                    <div class="flex items-center gap-2 text-sm">
                                        <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.ads.featured') }}" class="w-4 h-4">
                                        <span>
                                            {{ __('frontend.ads.featured') }}
                                            @if((int)($package->featured_ads_limit ?? 0) > 0)
                                                ({{ __('frontend.packages.feature_featured_ads_limit', ['count' => $package->featured_ads_limit]) }})
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if($package->urgent_ads)
                                    <div class="flex items-center gap-2 text-sm">
                                        <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.ads.urgent') }}" class="w-4 h-4">
                                        <span>
                                            {{ __('frontend.ads.urgent') }}
                                            @if((int)($package->urgent_ads_limit ?? 0) > 0)
                                                ({{ __('frontend.packages.feature_urgent_ads_limit', ['count' => $package->urgent_ads_limit]) }})
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                @if($package->priority_support)
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="fas fa-headset text-blue-500"></i>
                                        <span>{{ __('frontend.packages.priority_support') }}</span>
                                    </div>
                                @endif

                                @if($package->homepage_display)
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="fas fa-home text-purple-500"></i>
                                        <span>{{ __('frontend.packages.homepage_display') }}</span>
                                    </div>
                                @endif
                            </div>

                            @auth
                                @php
                                    $meta = $packagePurchaseMeta[$package->id] ?? null;
                                    $canActivateNow = (bool) ($meta['can_activate_now'] ?? false);
                                @endphp
                                <form action="{{ route('packages.request', $package->id) }}" method="POST">
                                    @csrf
                                    @if($canActivateNow)
                                        <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold">
                                            <i class="fas fa-bolt ml-2"></i>
                                            {{ __('frontend.packages.activate_now') }}
                                        </button>
                                    @else
                                        <a href="{{ route('hawala.create') }}"
                                           class="w-full block text-center py-3 rounded-lg font-bold bg-yellow-400 hover:bg-yellow-500 text-gray-900">
                                            <i class="fas fa-wallet ml-2"></i>
                                            {{ __('frontend.packages.add_balance_to_activate') }}
                                        </a>
                                    @endif
                                </form>
                            @else
                                <a href="{{ route('login') }}"
                                   class="btn-primary w-full block text-center py-3 rounded-lg font-bold">
                                    <i class="fas fa-sign-in-alt ml-2"></i>
                                    {{ __('frontend.packages.login_to_subscribe') }}
                                </a>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('frontend.packages.no_packages') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection

