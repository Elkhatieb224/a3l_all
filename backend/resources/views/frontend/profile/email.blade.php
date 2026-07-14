@extends('frontend.layouts.app')

@section('title', __('frontend.profile.email'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.email') }}
                    </h1>

                    @if(session('verified'))
                        <div class="mb-4 p-6 bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-300 rounded-lg text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-green-500 text-5xl"></i>
                            </div>
                            <h2 class="text-xl font-bold text-green-800 mb-2">
                                {{ __('frontend.profile.account_verified_successfully') }}
                            </h2>
                            <p class="text-sm text-green-700">
                                {{ __('frontend.profile.email_verified_successfully') }}
                            </p>
                            <p class="text-xs text-green-600 mt-2">
                                {{ __('frontend.profile.email_verification_complete_message') }}
                            </p>
                        </div>
                    @elseif(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-600">
                                <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                            </p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.email.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.email') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.password') }} <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-500 font-normal">({{ __('frontend.profile.password_confirm_change') }})</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                            <button type="submit" class="btn-primary px-6 sm:px-8 py-3 rounded-lg font-bold text-sm sm:text-base">
                                <i class="fas fa-save ml-2"></i>
                                {{ __('frontend.profile.update') }}
                            </button>
                            <a href="{{ route('profile.index') }}" 
                               class="px-6 sm:px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold text-sm sm:text-base">
                                {{ __('frontend.cancel') }}
                            </a>
                        </div>
                    </form>

                    <!-- Email Verification Section -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">
                            {{ __('frontend.profile.email_verification') }}
                        </h2>

                        @if($user->email_verified_at)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-green-800">
                                            {{ __('frontend.profile.email_verified') }}
                                        </p>
                                        <p class="text-xs text-green-600 mt-1">
                                            {{ __('frontend.profile.email_verified_at') }}: {{ $user->email_verified_at->format('Y-m-d H:i') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-yellow-800">
                                            {{ __('frontend.profile.email_not_verified') }}
                                        </p>
                                        <p class="text-xs text-yellow-600 mt-1">
                                            {{ __('frontend.profile.email_verification_required') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Send Verification Code Form -->
                            <form action="{{ route('profile.email.send-verification-code') }}" method="POST" class="mb-4">
                                @csrf
                                <button type="submit" 
                                        class="btn-primary px-6 py-3 rounded-lg font-semibold text-sm {{ !$canResend ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ !$canResend ? 'disabled' : '' }}>
                                    <i class="fas fa-paper-plane ml-2"></i>
                                    {{ __('frontend.profile.send_verification_code') }}
                                </button>
                                @if(!$canResend && $latestCode)
                                    <p class="text-xs text-gray-500 mt-2">
                                        {{ __('frontend.profile.wait_before_resend') }}
                                    </p>
                                @endif
                            </form>

                            @if($latestCode && !$latestCode->is_used)
                                <!-- Verify Code Form -->
                                <form action="{{ route('profile.email.verify-code') }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
                                            {{ __('frontend.profile.verification_code') }}
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="text" 
                                                   id="code" 
                                                   name="code" 
                                                   maxlength="6"
                                                   pattern="[0-9]{6}"
                                                   placeholder="000000"
                                                   required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-center text-2xl font-bold tracking-widest">
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">
                                            {{ __('frontend.profile.verification_code_hint') }}
                                        </p>
                                        @error('code')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-semibold text-sm">
                                        <i class="fas fa-check ml-2"></i>
                                        {{ __('frontend.profile.verify_email') }}
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

