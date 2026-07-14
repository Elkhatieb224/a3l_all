@extends('frontend.layouts.app')

@section('title', __('frontend.reports.submit_report'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            <!-- Sidebar -->
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.reports.submit_report') }}
                    </h1>

                    <!-- Error Messages -->
                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Report Target Info -->
                    @if($ad || $reportedUser || $conversation)
                    <div class="mb-6 p-4 bg-blue-50 border-r-4 border-blue-500 rounded-lg">
                        <p class="text-sm font-semibold text-blue-800 mb-2">{{ __('frontend.reports.reporting_about') }}:</p>
                        @if($conversation)
                            <p class="text-blue-700 mb-2">
                                <i class="fas fa-comments ml-1"></i>
                                {{ __('frontend.reports.conversation') }}: <strong>{{ $conversation->ad->title }}</strong>
                            </p>
                            <p class="text-blue-700 text-sm">
                                {{ __('frontend.reports.with') }}: 
                                <strong>{{ $conversation->sender_id === Auth::id() ? $conversation->receiver->name : $conversation->sender->name }}</strong>
                            </p>
                            @if($conversationMessages && $conversationMessages->count() > 0)
                                <div class="mt-4 p-3 bg-white rounded-lg border border-blue-200">
                                    <p class="text-xs font-semibold text-blue-800 mb-2">{{ __('frontend.reports.last_messages') }}:</p>
                                    <div class="space-y-2 max-h-40 overflow-y-auto">
                                        @foreach($conversationMessages as $msg)
                                            <div class="text-xs p-2 bg-gray-50 rounded">
                                                <span class="font-semibold">{{ $msg->sender->name }}:</span>
                                                <span class="text-gray-700">{{ Str::limit($msg->message, 100) }}</span>
                                                <span class="text-gray-500 text-xs">{{ $msg->created_at->format('H:i') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                        @if($ad)
                            <p class="text-blue-700">
                                <i class="fas fa-bullhorn ml-1"></i>
                                {{ __('frontend.reports.ad') }}: <strong>{{ $ad->title }}</strong>
                            </p>
                        @endif
                        @if($reportedUser)
                            <p class="text-blue-700">
                                <i class="fas fa-user ml-1"></i>
                                {{ __('frontend.reports.user') }}: <strong>{{ $reportedUser->name }}</strong>
                            </p>
                        @endif
                    </div>
                    @else
                    <div class="mb-6 p-4 bg-gray-50 border-r-4 border-gray-400 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-info-circle ml-1"></i>
                            {{ __('frontend.reports.general_report_hint') }}
                        </p>
                    </div>
                    @endif

                    <form action="{{ route('profile.reports.store') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                        @csrf

                        <!-- Hidden Fields -->
                        @if($ad)
                            <input type="hidden" name="ad_id" value="{{ $ad->id }}">
                        @endif
                        @if($reportedUser)
                            <input type="hidden" name="reported_user_id" value="{{ $reportedUser->id }}">
                        @endif
                        @if($conversation)
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                        @endif

                        <!-- Report Type -->
                        <div>
                            <label for="type" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.reports.report_type') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="type" 
                                    id="type" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    required>
                                <option value="">{{ __('frontend.reports.select_type') }}</option>
                                <option value="spam" {{ old('type') === 'spam' ? 'selected' : '' }}>{{ __('frontend.reports.type_spam') }}</option>
                                <option value="fraud" {{ old('type') === 'fraud' ? 'selected' : '' }}>{{ __('frontend.reports.type_fraud') }}</option>
                                <option value="inappropriate" {{ old('type') === 'inappropriate' ? 'selected' : '' }}>{{ __('frontend.reports.type_inappropriate') }}</option>
                                <option value="duplicate" {{ old('type') === 'duplicate' ? 'selected' : '' }}>{{ __('frontend.reports.type_duplicate') }}</option>
                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>{{ __('frontend.reports.type_other') }}</option>
                            </select>
                            @error('type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.reports.reason') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea name="reason" 
                                      id="reason" 
                                      rows="6" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                      placeholder="{{ __('frontend.reports.reason_placeholder') }}"
                                      required>{{ old('reason') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ __('frontend.reports.reason_hint') }}</p>
                            @error('reason')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- صور البلاغ (اختياري) -->
                        <div>
                            <label for="images" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.reports.attach_images') }}
                            </label>
                            <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            <p class="text-xs text-gray-500 mt-1">{{ __('frontend.reports.attach_images_hint') }}</p>
                            @error('images')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @error('images.*')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('profile.reports.index') }}" 
                               class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold">
                                {{ __('frontend.cancel') }}
                            </a>
                            <button type="submit" class="btn-primary px-6 sm:px-8 py-3 rounded-lg font-bold">
                                <i class="fas fa-paper-plane ml-2"></i>
                                {{ __('frontend.reports.submit_report') }}
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

