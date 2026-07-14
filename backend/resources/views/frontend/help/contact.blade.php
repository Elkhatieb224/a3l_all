@extends('frontend.layouts.app')

@section('title', __('frontend.help.contact_title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white py-8 sm:py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('frontend.help.contact_title') }}</h1>
                <p class="text-blue-100 text-lg">{{ __('frontend.help.contact_subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('help.send-message') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @guest
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                                {{ __('frontend.help.name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}"
                                   dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                            @error('name')
                                <p class="text-red-500 text-sm mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                                {{ __('frontend.help.email') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}"
                                   dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                            @error('email')
                                <p class="text-red-500 text-sm mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-800 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                                <i class="fas fa-info-circle {{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}"></i>
                                {{ __('frontend.help.logged_in_as', ['name' => Auth::user()->name, 'email' => Auth::user()->email]) }}
                            </p>
                        </div>
                    @endguest

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                            {{ __('frontend.help.subject') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="subject" value="{{ old('subject') }}" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}"
                               dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                        @error('subject')
                            <p class="text-red-500 text-sm mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                            {{ __('frontend.help.message') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="message" rows="8" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}"
                                  dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-red-500 text-sm mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                            {{ __('frontend.help.attachments') }}
                        </label>
                        <input type="file" name="attachments[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 {{ app()->getLocale() === 'ar' ? 'file:ml-4 file:mr-0' : '' }}">
                        <p class="text-xs text-gray-500 mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ __('frontend.help.attachments_hint') }}</p>
                        @error('attachments')
                            <p class="text-red-500 text-sm mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $message }}</p>
                        @enderror
                        @error('attachments.*')
                            <p class="text-red-500 text-sm mt-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                        <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold inline-flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-paper-plane"></i>
                            {{ __('frontend.help.send_message') }}
                        </button>
                        <a href="{{ route('help.index') }}" class="text-gray-600 hover:text-primary transition">
                            {{ __('frontend.help.back_to_help') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

