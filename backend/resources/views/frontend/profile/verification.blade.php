@extends('frontend.layouts.app')

@section('title', __('frontend.profile.account_verification'))

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
                        {{ __('frontend.profile.account_verification') }}
                    </h1>

                    <!-- Success Message -->
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-600">
                                <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                            </p>
                        </div>
                    @endif

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

                    <!-- Account Already Verified -->
                    @if($user->is_verified)
                    <div class="bg-green-50 border-r-4 border-green-500 p-6 rounded-lg mb-6">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-600 text-2xl mt-1"></i>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-green-800 mb-2">{{ __('frontend.profile.account_verified') }}</h3>
                                <p class="text-gray-700 mb-2">{{ __('frontend.profile.account_verified_message') }}</p>
                                @if($user->is_verified)
                                <p class="mt-3">
                                    <a href="{{ route('profile.business-profile') }}" class="inline-flex items-center gap-2 text-primary font-semibold hover:underline">
                                        <i class="fas fa-briefcase"></i>
                                        {{ __('frontend.profile.manage_business_profile') }}
                                    </a>
                                </p>
                                @endif
                                @if($approvedRequest)
                                <p class="text-sm text-gray-600 mb-4">
                                    {{ __('frontend.profile.verification_approved_at') }}: {{ $approvedRequest->reviewed_at->format('Y-m-d H:i') }}
                                </p>
                                @endif
                                <form action="{{ route('profile.verification.revoke') }}" method="POST" onsubmit="return confirm('{{ __('frontend.profile.confirm_revoke_verification') }}')">
                                    @csrf
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                        <i class="fas fa-times-circle ml-1"></i>
                                        {{ __('frontend.profile.revoke_verification') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @else
                        <!-- Verification Requirements -->
                        @if($verificationRequirements)
                        <div class="bg-blue-50 border-r-4 border-blue-500 p-4 sm:p-6 rounded-lg mb-6">
                            <h2 class="text-lg sm:text-xl font-bold text-blue-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i>
                                {{ __('frontend.profile.verification_requirements') }}
                            </h2>
                            <div class="text-gray-700 whitespace-pre-line text-sm sm:text-base">{!! nl2br(e($verificationRequirements)) !!}</div>
                        </div>
                        @endif

                        <!-- Pending Request Alert -->
                        @if($pendingRequest)
                        <div class="bg-yellow-50 border-r-4 border-yellow-500 p-4 sm:p-6 rounded-lg mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-clock text-yellow-600 text-xl mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-yellow-800 mb-2">{{ __('frontend.profile.verification_request_pending') }}</h3>
                                    <p class="text-gray-700 mb-2 text-sm sm:text-base">{{ __('frontend.profile.verification_request_submitted_at') }}: {{ $pendingRequest->created_at->format('Y-m-d H:i') }}</p>
                                    @if($pendingRequest->message)
                                    <p class="text-gray-600 text-sm mb-1">{{ __('frontend.profile.your_message') }}:</p>
                                    <p class="text-gray-700 bg-white p-3 rounded mt-2 text-sm sm:text-base">{{ $pendingRequest->message }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Rejected Request Alert -->
                        @if($rejectedRequest)
                        <div class="bg-red-50 border-r-4 border-red-500 p-4 sm:p-6 rounded-lg mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-times-circle text-red-600 text-xl mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-red-800 mb-2">{{ __('frontend.profile.verification_request_rejected') }}</h3>
                                    <p class="text-gray-700 mb-2 text-sm sm:text-base">
                                        {{ __('frontend.profile.verification_request_rejected_at') }}: {{ $rejectedRequest->reviewed_at->format('Y-m-d H:i') }}
                                    </p>
                                    @if($rejectedRequest->admin_notes)
                                    <div class="bg-white p-3 rounded mt-3">
                                        <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.profile.rejection_reason') }}:</p>
                                        <p class="text-gray-800 text-sm sm:text-base">{{ $rejectedRequest->admin_notes }}</p>
                                    </div>
                                    @endif
                                    <p class="text-sm text-gray-600 mt-3">{{ __('frontend.profile.verification_can_resubmit') }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Verification Form -->
                        @if(!$pendingRequest && !$user->is_verified)
                        <form action="{{ route('profile.verification.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf

                            <!-- Business Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.business_name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="business_name" value="{{ old('business_name') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                    @error('business_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.business_type') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="business_type" value="{{ old('business_type') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                    @error('business_type')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.responsible_person') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="responsible_person" value="{{ old('responsible_person') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                    @error('responsible_person')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.business_address') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="business_address" value="{{ old('business_address') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                    @error('business_address')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.business_phone') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="business_phone" value="{{ old('business_phone') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('frontend.profile.business_phone_hint') }}</p>
                                    @error('business_phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.instagram') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <input type="url" name="instagram_url" value="{{ old('instagram_url') }}"
                                           placeholder="{{ __('frontend.profile.add_your_link') }}"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('instagram_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.facebook') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <input type="url" name="facebook_url" value="{{ old('facebook_url') }}"
                                           placeholder="{{ __('frontend.profile.add_your_link') }}"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('facebook_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.website') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <input type="url" name="website_url" value="{{ old('website_url') }}"
                                           placeholder="{{ __('frontend.profile.add_your_link') }}"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('website_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Documents -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('frontend.profile.primary_document_title') }} <span class="text-red-500">*</span></h3>
                                    <p class="text-xs text-gray-500 mb-3">{{ __('frontend.profile.primary_document_hint') }}</p>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="primary_document_type" value="id" {{ old('primary_document_type', 'id') === 'id' ? 'checked' : '' }} required>
                                            <span>{{ __('frontend.profile.primary_document_id') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="primary_document_type" value="cr" {{ old('primary_document_type') === 'cr' ? 'checked' : '' }}>
                                            <span>{{ __('frontend.profile.primary_document_cr') }}</span>
                                        </label>
                                    </div>
                                    @error('primary_document_type')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror

                                    <input type="file"
                                           name="primary_document"
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                           class="w-full mt-3 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                           required>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ __('frontend.profile.verification_documents_formats') }}: PDF, DOC, DOCX, JPG, JPEG, PNG<br>
                                        {{ __('frontend.profile.verification_documents_max_size') }}: 10MB {{ __('frontend.per_file') }}
                                    </p>
                                    @error('primary_document')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('frontend.profile.storefront_photo_title') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span></h3>
                                    <p class="text-xs text-gray-500 mb-3">{{ __('frontend.profile.storefront_photo_hint') }}</p>
                                    <input type="file"
                                           name="storefront_image"
                                           accept=".jpg,.jpeg,.png"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    @error('storefront_image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end gap-4">
                                <button type="submit" class="btn-primary px-6 sm:px-8 py-3 rounded-lg font-bold">
                                    <i class="fas fa-paper-plane ml-2"></i>
                                    {{ __('frontend.profile.submit_verification_request') }}
                                </button>
                            </div>
                        </form>
                        @endif
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

