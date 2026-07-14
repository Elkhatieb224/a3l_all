@extends('frontend.layouts.app')

@section('title', __('frontend.profile.business_profile_title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">
                        {{ __('frontend.profile.business_profile_title') }}
                    </h1>
                    <p class="text-sm text-gray-600 mb-6">{{ __('frontend.profile.business_profile_subtitle') }}</p>

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

                    <form action="{{ route('profile.business-profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.business_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="business_name" value="{{ old('business_name', $user->business_name) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                @error('business_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.business_type') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="business_type" value="{{ old('business_type', $user->business_type) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                @error('business_type')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.responsible_person') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="business_owner" value="{{ old('business_owner', $user->business_owner) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                @error('business_owner')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.business_phone') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="business_phone" value="{{ old('business_phone', $user->business_phone) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                                <p class="text-xs text-gray-500 mt-1">{{ __('frontend.profile.business_phone_hint') }}</p>
                                @error('business_phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.business_address') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea name="business_address" rows="3" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('business_address', $user->business_address) }}</textarea>
                            @error('business_address')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.instagram') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                                </label>
                                <input type="url" name="instagram_url" value="{{ old('instagram_url', $user->instagram_url) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                       placeholder="{{ __('frontend.profile.add_your_link') }}">
                                @error('instagram_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.facebook') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                                </label>
                                <input type="url" name="facebook_url" value="{{ old('facebook_url', $user->facebook_url) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                       placeholder="{{ __('frontend.profile.add_your_link') }}">
                                @error('facebook_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.website') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                                </label>
                                <input type="url" name="website_url" value="{{ old('website_url', $user->website_url) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                       placeholder="{{ __('frontend.profile.add_your_link') }}">
                                @error('website_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('frontend.profile.storefront_photo_title') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span></h3>
                            <p class="text-xs text-gray-500 mb-3">{{ __('frontend.profile.storefront_photo_hint') }}</p>
                            @if($user->storefront_image_path)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-600 mb-2">{{ __('frontend.profile.current_storefront_image') }}</p>
                                    <img src="{{ asset('storage/' . $user->storefront_image_path) }}" alt="" class="max-h-40 rounded-lg border border-gray-200 object-contain bg-gray-50">
                                </div>
                            @endif
                            <input type="file" name="storefront_image" accept=".jpg,.jpeg,.png,.webp"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-500 mt-1">
                                {{ __('frontend.profile.verification_documents_formats') }}: JPG, JPEG, PNG, WebP —
                                {{ __('frontend.profile.verification_documents_max_size') }}: 10MB
                            </p>
                            @error('storefront_image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-2">
                            <button type="submit" class="btn-primary px-6 sm:px-8 py-3 rounded-lg font-bold">
                                <i class="fas fa-save ml-2"></i>
                                {{ __('frontend.profile.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
