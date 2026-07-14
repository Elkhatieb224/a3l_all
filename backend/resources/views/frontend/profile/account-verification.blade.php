@extends('frontend.layouts.app')

@section('title', __('frontend.profile.account_verification'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.account_verification') }}
                    </h1>

                    <div class="space-y-6">
                        <!-- Email Verification -->
                        <div class="border border-gray-200 rounded-lg p-4 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 mb-1">{{ __('frontend.profile.email') }}</h3>
                                    <p class="text-sm text-gray-600">{{ $user->email }}</p>
                                </div>
                                @if($user->email_verified_at)
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                        <i class="fas fa-check-circle ml-1"></i> {{ __('frontend.profile.verified') }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-semibold">
                                        <i class="fas fa-clock ml-1"></i> {{ __('frontend.profile.not_verified') }}
                                    </span>
                                @endif
                            </div>
                            @if(!$user->email_verified_at)
                                <button class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                                    {{ __('frontend.profile.verify_email') }}
                                </button>
                            @endif
                        </div>

                        <!-- Phone Verification -->
                        @if($user->phone)
                        <div class="border border-gray-200 rounded-lg p-4 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 mb-1">{{ __('frontend.profile.phone') }}</h3>
                                    <p class="text-sm text-gray-600">{{ $user->phone }}</p>
                                </div>
                                @if($user->phone_verified_at)
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                        <i class="fas fa-check-circle ml-1"></i> {{ __('frontend.profile.verified') }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-semibold">
                                        <i class="fas fa-clock ml-1"></i> {{ __('frontend.profile.not_verified') }}
                                    </span>
                                @endif
                            </div>
                            @if(!$user->phone_verified_at)
                                <button class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                                    {{ __('frontend.profile.verify_phone') }}
                                </button>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

