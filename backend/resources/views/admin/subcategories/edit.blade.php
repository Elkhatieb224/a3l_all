@extends('admin.layouts.app')

@section('title', __('admin.categories.subcategories.edit_title'))
@section('page-title', __('admin.categories.subcategories.edit_title'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('admin.categories.show', $category->id) }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.categories.subcategories.edit_title') }}</h2>
                <div class="flex items-center gap-2 text-gray-600 mt-2">
                    <span class="text-sm">{{ __('admin.categories.subcategories.path') }}:</span>
                    <div class="flex items-center gap-2 text-sm">
                        <a href="{{ route('admin.categories.show', $category->id) }}"
                           class="text-primary hover:text-secondary transition">
                            {{ $category->getName('ar') }}
                        </a>
                        @if($subcategory->parent_subcategory_id)
                            <i class="fas fa-chevron-left text-gray-400 text-xs"></i>
                            <span class="text-gray-700">
                                {{ $subcategory->parent->getName('ar') ?? '' }}
                            </span>
                        @endif
                        <i class="fas fa-chevron-left text-gray-400 text-xs"></i>
                        <span class="text-gray-800 font-semibold">
                            {{ $subcategory->getName('ar') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.subcategories.update', $subcategory->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-secondary"></i>
                {{ __('admin.categories.subcategories.basic_info') }}
            </h3>

            <div class="space-y-4">
                <!-- Parent Subcategory (Optional) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.parent_label') }}
                    </label>
                    <select name="parent_subcategory_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                        @if($subcategory->parent_subcategory_id)
                            @php
                                $currentParent = $parentSubcategories->firstWhere('id', $subcategory->parent_subcategory_id);
                            @endphp
                            @if($currentParent)
                                <option value="{{ $currentParent->id }}" selected>
                                    {{ $currentParent->getName('ar') }}
                                </option>
                            @else
                                {{-- Fallback: if parent not in list, try to load it directly --}}
                                @php
                                    $fallbackParent = \App\Models\Subcategory::find($subcategory->parent_subcategory_id);
                                @endphp
                                @if($fallbackParent)
                                    <option value="{{ $fallbackParent->id }}" selected>
                                        {{ $fallbackParent->getName('ar') }}
                                    </option>
                                @endif
                            @endif
                            <option value="">{{ __('admin.categories.subcategories.parent_placeholder') }}</option>
                            @foreach($parentSubcategories as $sub)
                                @if($sub->id != $subcategory->parent_subcategory_id)
                                    <option value="{{ $sub->id }}">
                                        {{ $sub->getName('ar') }}
                                    </option>
                                @endif
                            @endforeach
                        @else
                            <option value="" selected>{{ __('admin.categories.subcategories.parent_placeholder') }}</option>
                            @foreach($parentSubcategories as $sub)
                                <option value="{{ $sub->id }}">
                                    {{ $sub->getName('ar') }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Arabic Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name_ar"
                           value="{{ old('name_ar', $subcategory->name_ar) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- English Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.name_en') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name_en"
                           value="{{ old('name_en', $subcategory->name_en) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Turkish Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.name_tr') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name_tr"
                           value="{{ old('name_tr', $subcategory->name_tr) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.slug') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="slug"
                           value="{{ old('slug', $subcategory->slug) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Icon -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.icon') }}
                    </label>

                    @if($subcategory->icon)
                        <div class="mb-3 flex items-center gap-4">
                            <img src="{{ asset('storage/' . $subcategory->icon) }}"
                                 alt="Icon"
                                 class="w-16 h-16 object-contain rounded-lg border border-gray-300 bg-white p-2">
                            <div>
                                <p class="text-sm text-gray-600">{{ __('admin.categories.current_icon') }}</p>
                                <p class="text-xs text-gray-500">{{ basename($subcategory->icon) }}</p>
                            </div>
                        </div>
                    @endif

                    <input type="file"
                           name="icon"
                           accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('admin.categories.subcategories.supported_formats_hint') }}
                        @if($subcategory->icon)
                            <br>{{ __('admin.categories.subcategories.keep_icon_hint') }}
                        @endif
                    </p>
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.order') }}
                    </label>
                    <input type="number"
                           name="order"
                           value="{{ old('order', $subcategory->order) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           name="is_active"
                           {{ $subcategory->is_active ? 'checked' : '' }}
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        {{ __('admin.categories.subcategories.activate') }}
                    </label>
                </div>
            </div>
        </div>

        @include('admin.categories.partials.ad-images-settings', ['record' => $subcategory, 'showInherit' => true])

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.categories.subcategories.save_changes') }}
            </button>

            <a href="{{ route('admin.categories.show', $category->id) }}"
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.categories.subcategories.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

