@php
    $showInherit = $showInherit ?? false;
    $defaultMode = $record->ad_images_mode ?? ($showInherit ? '' : 'user_upload');
    $modeVal = old('ad_images_mode', $defaultMode);
    if ($modeVal === null) {
        $modeVal = '';
    }
    $galleryList = old('ad_gallery_keep', $record->ad_gallery_images ?? []);
    if (!is_array($galleryList)) {
        $galleryList = [];
    }
    $maxDefault = $record->ad_images_max ?? ($showInherit ? '' : \App\Support\AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES);
    $maxVal = old('ad_images_max', $maxDefault);
@endphp
<div id="category-ad-images-settings" class="bg-white rounded-xl shadow-md p-6 scroll-mt-24">
    <h3 class="text-xl font-bold text-primary mb-4 flex items-center gap-2">
        <i class="fas fa-images text-secondary"></i>
        {{ __('admin.categories.ad_images_section') }}
    </h3>
    <p class="text-sm text-gray-600 mb-4">{{ __('admin.categories.ad_images_help') }}</p>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.ad_images_mode_label') }}</label>
            <select name="ad_images_mode" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                @if($showInherit)
                    <option value="" {{ $modeVal === '' ? 'selected' : '' }}>{{ __('admin.categories.ad_images_inherit') }}</option>
                @endif
                <option value="user_upload" {{ $modeVal === 'user_upload' ? 'selected' : '' }}>{{ __('admin.categories.ad_images_user_upload') }}</option>
                <option value="admin_gallery" {{ $modeVal === 'admin_gallery' ? 'selected' : '' }}>{{ __('admin.categories.ad_images_admin_gallery') }}</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.ad_images_max_label') }}</label>
            <input type="number"
                   name="ad_images_max"
                   min="1"
                   max="50"
                   value="{{ $maxVal }}"
                   placeholder="{{ $showInherit ? __('admin.categories.ad_images_max_inherit_placeholder') : \App\Support\AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES }}"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            <p class="text-xs text-gray-500 mt-1">
                {{ $showInherit ? __('admin.categories.ad_images_max_help_inherit') : __('admin.categories.ad_images_max_help_default') }}
            </p>
            @error('ad_images_max')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.ad_gallery_existing') }}</label>
            @if(count($galleryList) > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mb-4">
                    @foreach($galleryList as $path)
                        @if(is_string($path) && $path !== '')
                            <div class="relative border rounded-lg p-2 bg-gray-50">
                                <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener" class="block">
                                    <img src="{{ asset('storage/' . $path) }}" alt="" class="w-full h-28 object-contain rounded bg-white">
                                </a>
                                <label class="flex items-center gap-2 mt-2 text-xs text-red-600 cursor-pointer">
                                    <input type="checkbox" name="ad_gallery_remove[]" value="{{ $path }}">
                                    {{ __('admin.categories.ad_gallery_remove') }}
                                </label>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">{{ __('admin.categories.ad_gallery_empty') }}</p>
            @endif

            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.ad_gallery_add') }}</label>
            <input type="file" name="ad_gallery_new[]" accept="image/jpeg,image/png,image/jpg,image/webp" multiple
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.categories.ad_gallery_formats') }}</p>
        </div>
    </div>
</div>
