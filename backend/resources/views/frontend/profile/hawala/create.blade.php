@extends('frontend.layouts.app')

@section('title', __('frontend.hawala.submit_transfer'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.hawala.submit_transfer') }}
                    </h1>

                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.hawala.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-xl">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('frontend.hawala.amount') }} *</label>
                            <input type="number" name="amount" step="0.01" min="0.01" required
                                   value="{{ old('amount') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('frontend.hawala.currency') }} *</label>
                            <select name="currency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                                <option value="SYP" {{ old('currency') === 'SYP' ? 'selected' : '' }}>SYP (ل.س)</option>
                                <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="TRY" {{ old('currency') === 'TRY' ? 'selected' : '' }}>TRY</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('frontend.hawala.receipt_number') }} *</label>
                            <input type="text" name="receipt_number" required
                                   value="{{ old('receipt_number') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('frontend.hawala.receipt_image') }} *</label>
                            <input type="file" name="receipt_image" accept="image/jpeg,image/jpg,image/png,image/webp" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1">JPEG, PNG, WebP. Max 5MB.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('frontend.hawala.note_optional') }}</label>
                            <textarea name="note" rows="3" maxlength="1000" placeholder="{{ __('frontend.hawala.note_placeholder') }}"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                            <i class="fas fa-paper-plane ml-2"></i>
                            {{ __('frontend.hawala.submit_transfer') }}
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
