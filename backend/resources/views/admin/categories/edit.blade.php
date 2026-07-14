@extends('admin.layouts.app')

@section('title', __('admin.categories.edit_title'))
@section('page-title', __('admin.categories.edit_title'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.categories.show', $category->id) }}" 
               class="text-gray-600 hover:text-primary" title="{{ __('admin.categories.details_title') }}">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.categories.edit') }}</h2>
                <p class="text-gray-600">{{ $category->getName('ar') }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-secondary"></i>
                {{ __('admin.categories.basic_info') }}
            </h3>

            <div class="space-y-4">
                <!-- Arabic Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_ar" 
                           value="{{ old('name_ar', $category->name_ar) }}"
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('name_ar')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- English Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.name_en') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_en" 
                           value="{{ old('name_en', $category->name_en) }}"
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('name_en')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Turkish Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.name_tr') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name_tr" 
                           value="{{ old('name_tr', $category->name_tr) }}"
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('name_tr')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.slug') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="slug" 
                           value="{{ old('slug', $category->slug) }}"
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('slug')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Icon -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.icon') }}
                    </label>
                    
                    @if($category->icon)
                        <div class="mb-3 flex items-center gap-4">
                            <img src="{{ asset('storage/' . $category->icon) }}" 
                                 alt="Icon" 
                                 class="w-16 h-16 object-contain rounded-lg border border-gray-300 bg-white p-2">
                            <div>
                                <p class="text-sm text-gray-600">{{ __('admin.categories.current_icon') }}</p>
                                <p class="text-xs text-gray-500">{{ basename($category->icon) }}</p>
                            </div>
                        </div>
                    @endif
                    
                    <input type="file" 
                           name="icon" 
                           accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('admin.categories.supported_formats_hint') }}
                        @if($category->icon)
                            <br>{{ __('admin.categories.keep_icon_hint') }}
                        @endif
                    </p>
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.order') }}
                    </label>
                    <input type="number" 
                           name="order" 
                           value="{{ old('order', $category->order) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>
            </div>
        </div>

        <!-- Descriptions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-align-right text-secondary"></i>
                {{ __('admin.categories.descriptions_section') }}
            </h3>

            <div class="space-y-4">
                <!-- Arabic Description -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.description_ar') }}
                    </label>
                    <textarea name="description_ar" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('description_ar', $category->description_ar) }}</textarea>
                </div>

                <!-- English Description -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.description_en') }}
                    </label>
                    <textarea name="description_en" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('description_en', $category->description_en) }}</textarea>
                </div>

                <!-- Turkish Description -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.description_tr') }}
                    </label>
                    <textarea name="description_tr" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('description_tr', $category->description_tr) }}</textarea>
                </div>
            </div>
        </div>

        @include('admin.categories.partials.ad-images-settings', ['record' => $category, 'showInherit' => false])

        <!-- Status -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        {{ __('admin.categories.activate_category') }}
                    </label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" 
                           name="enable_negotiation" 
                           value="1"
                           {{ old('enable_negotiation', $category->enable_negotiation ?? true) ? 'checked' : '' }}
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        {{ __('admin.categories.enable_negotiation') }}
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.save') }}
            </button>
            
            <a href="{{ route('admin.categories.show', $category->id) }}" 
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

