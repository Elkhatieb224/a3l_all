@extends('frontend.layouts.app')

@section('title', __('frontend.negotiations.negotiate_price'))

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('frontend.negotiations.negotiate_price') }}</h1>
                <p class="text-gray-600">{{ __('frontend.negotiations.negotiate_description') }}</p>
            </div>

            <!-- Ad Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center gap-4">
                    @php
                        $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                        $images = $images ?? [];
                        $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                        $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                    @endphp
                    @if($firstImagePath)
                        <img src="{{ asset('storage/' . $firstImagePath) }}" 
                             alt="{{ $ad->title }}"
                             class="w-24 h-24 object-cover rounded-lg"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-24 h-24 hidden bg-gray-200 rounded-lg items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                        </div>
                    @else
                        <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                        </div>
                    @endif
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 mb-1">{{ $ad->title }}</h3>
                        @if($ad->display_price)
                            <p class="text-lg font-semibold text-primary">
                                {{ __('frontend.negotiations.current_price') }}: {{ $ad->display_price }}
                            </p>
                        @endif
                        <a href="{{ route('ads.show', $ad->uid) }}" 
                           class="text-sm text-blue-600 hover:underline mt-2 inline-block">
                            <i class="fas fa-external-link-alt ml-1"></i>
                            {{ __('frontend.negotiations.view_ad') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Negotiation Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('negotiations.store', $ad->uid) }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Offered Price -->
                    <div>
                        <label for="offered_price" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('frontend.negotiations.offered_price') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="number" 
                                   name="offered_price" 
                                   id="offered_price" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('offered_price') }}"
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="{{ __('frontend.negotiations.offered_price_placeholder') }}"
                                   required>
                            <select name="currency" 
                                    id="currency" 
                                    class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    required>
                                <option value="SYP" {{ old('currency', $ad->currency) === 'SYP' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('SYP') }}</option>
                                <option value="TRY" {{ old('currency', $ad->currency) === 'TRY' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('TRY') }}</option>
                                <option value="USD" {{ old('currency', $ad->currency) === 'USD' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('USD') }}</option>
                                <option value="EUR" {{ old('currency', $ad->currency) === 'EUR' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('EUR') }}</option>
                            </select>
                        </div>
                        @error('offered_price')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        @error('currency')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Message -->
                    <div>
                        <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('frontend.negotiations.message') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                        </label>
                        <textarea name="message" 
                                  id="message" 
                                  rows="4" 
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="{{ __('frontend.negotiations.message_placeholder') }}">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('ads.show', $ad->uid) }}" 
                           class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold">
                            {{ __('frontend.cancel') }}
                        </a>
                        <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold">
                            <i class="fas fa-paper-plane ml-2"></i>
                            {{ __('frontend.negotiations.send_request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

