@extends('frontend.layouts.app')

@section('title', __('frontend.profile.phone'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.phone') }}
                    </h1>

                    @if(session('success'))
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

                    <form action="{{ route('profile.phone.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.phone') }}
                            </label>
                            <div class="flex gap-2">
                                <select name="country_code" 
                                        id="country_code"
                                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent min-w-[160px]">
                                    <option value="">{{ __('frontend.profile.select_country_code') }}</option>
                                    @if(is_array($countryCodes) && count($countryCodes) > 0)
                                        @foreach($countryCodes as $code)
                                            @php
                                                $codeValue = $code['code'] ?? '';
                                                $flag = $code['flag'] ?? '';
                                                $nameKey = 'name_' . app()->getLocale();
                                                $name = $code[$nameKey] ?? $code['name'] ?? $code['name_ar'] ?? '';
                                            @endphp
                                            <option value="{{ $codeValue }}" 
                                                    {{ old('country_code', $user->country_code) === $codeValue ? 'selected' : '' }}>
                                                {{ $flag }} {{ $codeValue }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="+963" {{ old('country_code', $user->country_code) === '+963' ? 'selected' : '' }}>🇸🇾 +963</option>
                                        <option value="+90" {{ old('country_code', $user->country_code) === '+90' ? 'selected' : '' }}>🇹🇷 +90</option>
                                        <option value="+966" {{ old('country_code', $user->country_code) === '+966' ? 'selected' : '' }}>🇸🇦 +966</option>
                                        <option value="+971" {{ old('country_code', $user->country_code) === '+971' ? 'selected' : '' }}>🇦🇪 +971</option>
                                    @endif
                                </select>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone', $user->phone) }}"
                                       placeholder="{{ __('frontend.profile.phone_placeholder') }}"
                                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
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
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

