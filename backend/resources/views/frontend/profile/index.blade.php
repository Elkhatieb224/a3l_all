@extends('frontend.layouts.app')

@section('title', __('frontend.profile.my_account'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            <!-- Sidebar -->
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">
                        {{ __('frontend.profile.my_account') }}
                    </h1>

                    <!-- Profile Summary -->
                    <div class="border-b border-gray-200 pb-6 mb-6">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                            @if($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" 
                                     alt="{{ $user->name }}"
                                     class="w-24 h-24 sm:w-32 sm:h-32 rounded-full object-cover border-4 border-primary">
                            @else
                                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-primary flex items-center justify-center">
                                    <span class="text-white text-3xl sm:text-4xl font-bold">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                            <div class="text-center sm:text-right flex-1">
                                <div class="flex items-center justify-center sm:justify-start gap-3 mb-2">
                                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
                                    @if($user->is_verified)
                                        <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm font-semibold flex items-center gap-1">
                                            <i class="fas fa-check-circle"></i>
                                            {{ __('frontend.profile.verified') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-gray-600 mb-1">{{ $user->email }}</p>
                                @if($user->phone)
                                    <p class="text-gray-600">{{ $user->phone }}</p>
                                @endif
                                <a href="{{ route('profile.personal-info') }}" 
                                   class="inline-flex items-center gap-2 text-primary hover:text-secondary transition mt-3">
                                    <i class="fas fa-pencil-alt"></i>
                                    {{ __('frontend.profile.edit_profile') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl sm:text-3xl font-bold text-primary mb-1">
                                {{ $user->ads()->count() }}
                            </div>
                            <div class="text-sm text-gray-600">{{ __('frontend.ads.title') }}</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl sm:text-3xl font-bold text-primary mb-1">
                                {{ $user->subscriptions()->count() }}
                            </div>
                            <div class="text-sm text-gray-600">{{ __('frontend.profile.packages') }}</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl sm:text-3xl font-bold text-primary mb-1">
                                {{ $user->payments()->count() }}
                            </div>
                            <div class="text-sm text-gray-600">{{ __('frontend.nav.payments') }}</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            @if($user->is_verified)
                                <div class="text-2xl sm:text-3xl mb-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="text-sm font-semibold text-green-600 mb-1">{{ __('frontend.profile.verified') }}</div>
                            @else
                                <div class="text-2xl sm:text-3xl mb-2">
                                    <i class="fas fa-times-circle text-gray-400"></i>
                                </div>
                                <div class="text-sm text-gray-600 mb-1">{{ __('frontend.profile.not_verified') }}</div>
                            @endif
                            <a href="{{ route('profile.verification') }}" class="text-xs text-primary hover:underline">
                                {{ __('frontend.profile.view_details') }}
                            </a>
                        </div>
                    </div>

                    @if($user->is_verified)
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-2">{{ __('frontend.profile.business_profile_title') }}</h2>
                        <p class="text-sm text-gray-600 mb-4">{{ __('frontend.profile.business_profile_subtitle') }}</p>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 sm:p-5 space-y-3">
                            @if($user->business_name)
                                <p class="font-semibold text-gray-900">{{ $user->business_name }}</p>
                            @endif
                            @if($user->business_type)
                                <p class="text-sm text-gray-700">{{ $user->business_type }}</p>
                            @endif
                            @if($user->business_owner)
                                <p class="text-sm text-gray-600">{{ $user->business_owner }}</p>
                            @endif
                            @if($user->business_address)
                                <p class="text-sm text-gray-600 flex items-start gap-2">
                                    <i class="fas fa-map-marker-alt text-primary mt-0.5"></i>
                                    <span>{{ $user->business_address }}</span>
                                </p>
                            @endif
                            @if($user->business_phone)
                                <p class="text-sm text-gray-600 flex items-center gap-2">
                                    <i class="fas fa-phone text-primary"></i>
                                    <span>{{ $user->business_phone }}</span>
                                </p>
                            @endif
                            @if(!$user->business_name && !$user->business_type && !$user->business_address)
                                <p class="text-sm text-gray-500">{{ __('frontend.profile.manage_business_profile') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('profile.business-profile') }}"
                           class="inline-flex items-center gap-2 mt-4 text-primary hover:text-secondary font-semibold transition">
                            <i class="fas fa-pencil-alt"></i>
                            {{ __('frontend.profile.manage_business_profile') }}
                        </a>
                    </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

