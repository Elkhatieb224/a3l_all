@extends('frontend.layouts.app')

@section('title', __('frontend.profile.my_ads_management.edit_ad'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <!-- Header -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-primary mb-2">{{ __('frontend.profile.my_ads_management.edit_ad') }}</h1>
                        <p class="text-gray-600 text-sm">{{ __('frontend.profile.my_ads_management.edit_ad_description') }}</p>
                    </div>

                    <!-- Pending Changes Notice -->
                    @if($ad->pending_changes)
                        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-clock text-yellow-600 mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-yellow-800 mb-1">
                                        {{ __('frontend.profile.ads.changes_pending_review_title') }}
                                    </h3>
                                    <p class="text-sm text-yellow-700">
                                        {{ __('frontend.profile.ads.changes_pending_review_message') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Category Path -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-2 flex-wrap text-sm">
                            @if($ad->subcategory)
                                <span class="text-gray-700">{{ $ad->subcategory->getName(app()->getLocale()) }}</span>
                                <span class="text-gray-400"> < </span>
                            @endif
                            <span class="text-primary font-semibold">{{ $ad->category->getName(app()->getLocale()) }}</span>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <form action="{{ route('profile.ads.update', $ad->uid) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.title') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   value="{{ old('title', $ad->title) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="{{ __('frontend.ads.title_placeholder') }}"
                                   required>
                            @error('title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.description') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="8" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                      placeholder="{{ __('frontend.ads.description_placeholder') }}"
                                      required>{{ old('description', $ad->description) }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Custom Fields -->
                        @if(!empty($customFields))
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('frontend.ads.additional_details') }}</h3>
                            <div class="space-y-4">
                                @foreach($customFields as $field)
                                    @php
                                        $fieldId = $field['id'] ?? 'field_' . $loop->index;
                                        $fieldType = $field['type'] ?? 'text';
                                        $fieldLabel = $field['label'][app()->getLocale()] ?? $field['label']['ar'] ?? $fieldId;
                                        $isRequired = $field['required'] ?? false;
                                        $fieldValue = old($fieldId, $ad->custom_fields[$fieldId] ?? '');
                                        $isSellerType = \App\Support\SellerTypeField::isField($field);
                                        $sellerTypeLocked = $isSellerType && !auth()->user()->is_verified;
                                        if ($sellerTypeLocked) {
                                            $fieldValue = \App\Support\SellerTypeField::ownerStoredValue($field);
                                        }
                                    @endphp
                                    
                                    <div>
                                        <label for="{{ $fieldId }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                            {{ $fieldLabel }}
                                            @if($isRequired)
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </label>
                                        
                                        @if($fieldType === 'textarea')
                                            <textarea name="{{ $fieldId }}" 
                                                      id="{{ $fieldId }}" 
                                                      rows="4"
                                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                      @if($isRequired) required @endif>{{ $fieldValue }}</textarea>
                                        @elseif($fieldType === 'select' && isset($field['options']))
                                            @if($sellerTypeLocked)
                                                <input type="hidden" name="{{ $fieldId }}" value="{{ $fieldValue }}">
                                            @endif
                                            <select name="{{ $sellerTypeLocked ? '' : $fieldId }}" 
                                                    id="{{ $fieldId }}"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $sellerTypeLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                                    @if($sellerTypeLocked) disabled @endif
                                                    @if($isRequired && !$sellerTypeLocked) required @endif>
                                                @unless($sellerTypeLocked)
                                                    <option value="">{{ __('frontend.ads.select_option') }}</option>
                                                @endunless
                                                @foreach($field['options'] as $option)
                                                    @php
                                                        $optionValue = $option[app()->getLocale()] ?? $option['ar'] ?? $option;
                                                    @endphp
                                                    <option value="{{ $optionValue }}" 
                                                            {{ $fieldValue == $optionValue ? 'selected' : '' }}>
                                                        {{ $optionValue }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @elseif($fieldType === 'number')
                                            @php
                                                $isTbd = is_array($fieldValue) && !empty($fieldValue['tbd']);
                                                $numVal = $isTbd ? '' : (is_array($fieldValue) ? ($fieldValue['value'] ?? $fieldValue) : $fieldValue);
                                                $numCur = is_array($fieldValue) ? ($fieldValue['currency'] ?? 'SYP') : 'SYP';
                                                $allowTbd = !empty($field['allow_tbd']);
                                            @endphp
                                            @if(!empty($field['show_currency']))
                                                @if($allowTbd)
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <input type="checkbox" name="{{ $fieldId }}[tbd]" id="{{ $fieldId }}_tbd" value="1" {{ $isTbd ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                                        <label for="{{ $fieldId }}_tbd" class="text-sm font-medium text-gray-700">{{ __('frontend.ads.price_tbd') }}</label>
                                                    </div>
                                                @endif
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <input type="number" 
                                                               name="{{ $fieldId }}[value]" 
                                                               id="{{ $fieldId }}_value"
                                                               value="{{ $numVal }}"
                                                               step="{{ $field['step'] ?? 1 }}"
                                                               min="{{ $field['min'] ?? '' }}"
                                                               max="{{ $field['max'] ?? '' }}"
                                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                               @if($isRequired && !$allowTbd) required @endif>
                                                    </div>
                                                    <div>
                                                        <select name="{{ $fieldId }}[currency]" 
                                                                id="{{ $fieldId }}_currency"
                                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                                            <option value="SYP" {{ $numCur === 'SYP' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('SYP') }}</option>
                                                            <option value="TRY" {{ $numCur === 'TRY' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('TRY') }}</option>
                                                            <option value="USD" {{ $numCur === 'USD' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('USD') }}</option>
                                                            <option value="EUR" {{ $numCur === 'EUR' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('EUR') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            @else
                                                <input type="number" 
                                                       name="{{ $fieldId }}" 
                                                       id="{{ $fieldId }}"
                                                       value="{{ $numVal }}"
                                                       step="{{ $field['step'] ?? 1 }}"
                                                       min="{{ $field['min'] ?? '' }}"
                                                       max="{{ $field['max'] ?? '' }}"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                       @if($isRequired) required @endif>
                                            @endif
                                        @elseif($fieldType === 'checkbox')
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" 
                                                       name="{{ $fieldId }}" 
                                                       id="{{ $fieldId }}"
                                                       value="1"
                                                       {{ $fieldValue ? 'checked' : '' }}
                                                       class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                                                <label for="{{ $fieldId }}" class="text-sm text-gray-600">
                                                    {{ __('frontend.ads.yes') }}
                                                </label>
                                            </div>
                                        @elseif($fieldType === 'date')
                                            <input type="date"
                                                   name="{{ $fieldId }}"
                                                   id="{{ $fieldId }}"
                                                   value="{{ is_array($fieldValue) ? '' : $fieldValue }}"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                   @if($isRequired) required @endif>
                                        @elseif($fieldType === 'location')
                                            @php
                                                $locVal = is_array($fieldValue) ? $fieldValue : [];
                                                $locLat = $locVal['lat'] ?? '';
                                                $locLng = $locVal['lng'] ?? '';
                                                $locAddr = $locVal['address'] ?? '';
                                            @endphp
                                            <div class="space-y-3">
                                                <p class="text-sm text-gray-600">{{ __('frontend.ads.select_location_from_map') }}</p>
                                                <input type="hidden" name="{{ $fieldId }}[lat]" id="edit_{{ $fieldId }}_lat" value="{{ $locLat }}" @if($isRequired) required @endif>
                                                <input type="hidden" name="{{ $fieldId }}[lng]" id="edit_{{ $fieldId }}_lng" value="{{ $locLng }}" @if($isRequired) required @endif>
                                                <input type="hidden" name="{{ $fieldId }}[address]" id="edit_{{ $fieldId }}_address" value="{{ $locAddr }}">
                                                <button type="button" id="edit_btn_use_my_location_{{ $fieldId }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:opacity-90 transition flex items-center gap-2">
                                                    <i class="fas fa-location-crosshairs"></i>
                                                    {{ __('frontend.ads.use_my_location') }}
                                                </button>
                                                <div id="edit_map_{{ $fieldId }}" class="w-full h-64 border border-gray-300 rounded-lg bg-gray-100 z-0"></div>
                                                <p class="text-xs text-gray-500">{{ __('frontend.ads.click_map_to_select') }}</p>
                                                <div id="edit_map_{{ $fieldId }}_address" class="text-sm text-gray-700 mt-1 min-h-[1.5rem]"></div>
                                            </div>
                                        @elseif($fieldType === 'car_body_map')
                                            @include('partials.car-body-map-field', [
                                                'fieldId' => $fieldId,
                                                'fieldValue' => $fieldValue,
                                                'namePrefix' => $fieldId,
                                            ])
                                        @else
                                            <input type="text" 
                                                   name="{{ $fieldId }}" 
                                                   id="{{ $fieldId }}"
                                                   value="{{ $fieldValue }}"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                   @if($isRequired) required @endif>
                                        @endif
                                        
                                        @error($fieldId)
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Current Images -->
                        @if($ad->images && count($ad->images) > 0)
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.my_ads_management.current_images') }}
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    @foreach($ad->images as $image)
                                        <div class="relative">
                                            <img src="{{ asset('storage/' . $image) }}" 
                                                 alt="Current image"
                                                 class="w-full h-32 object-cover rounded-lg">
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500 mb-4">{{ __('frontend.profile.my_ads_management.replace_images_note') }}</p>
                            </div>
                        @endif

                        <!-- New Images -->
                        <div>
                            <label for="images" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.images') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                            </label>
                            <input type="file" 
                                   name="images[]" 
                                   id="images" 
                                   multiple 
                                   accept="image/*"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-500 mt-2">
                                {{ __('frontend.ads.images_hint') }}
                            </p>
                            @error('images.*')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @php
                            $editVDur = (int) \App\Models\Setting::get('ad_video_max_duration_seconds', 60);
                            $editVMb = (int) \App\Models\Setting::get('ad_video_max_size_mb', 50);
                        @endphp
                        <div class="mt-6 pt-6 border-t">
                            <label for="ad_edit_video" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.video_optional') }}
                            </label>
                            <input type="file"
                                   name="video"
                                   id="ad_edit_video"
                                   accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-primary/10 file:text-primary">
                            <p class="text-xs text-gray-500 mt-2">
                                {{ __('frontend.ads.video_hint', ['seconds' => $editVDur, 'max' => $editVMb]) }}
                            </p>
                            @error('video')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Preview Selected Images -->
                        <div id="images-preview" class="hidden">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('frontend.ads.selected_images') }}:</h3>
                            <div id="images-list" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between gap-4 pt-4 border-t">
                            <a href="{{ route('profile.ads.show', $ad->uid) }}" 
                               class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold">
                                <i class="fas fa-arrow-right ml-2"></i>
                                {{ __('frontend.cancel') }}
                            </a>
                            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                                <i class="fas fa-save ml-2"></i>
                                {{ __('frontend.profile.my_ads_management.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('images');
    const imagesPreview = document.getElementById('images-preview');
    const imagesList = document.getElementById('images-list');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            imagesList.innerHTML = '';
            
            if (this.files.length > 0) {
                imagesPreview.classList.remove('hidden');
                
                Array.from(this.files).forEach((file) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imageItem = document.createElement('div');
                        imageItem.className = 'relative';
                        imageItem.innerHTML = `
                            <img src="${e.target.result}" 
                                 alt="${file.name}" 
                                 class="w-full h-32 object-cover rounded-lg">
                            <span class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                                ${(file.size / 1024 / 1024).toFixed(2)} MB
                            </span>
                        `;
                        imagesList.appendChild(imageItem);
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                imagesPreview.classList.add('hidden');
            }
        });
    }
});
</script>

@if(collect($customFields)->where('type', 'location')->isNotEmpty() && config('services.google_maps.api_key'))
@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&language={{ app()->getLocale() }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationFields = @json(collect($customFields)->where('type', 'location')->pluck('id')->toArray());
    const maps = {};

    function initEditMap(fieldId) {
        if (maps[fieldId] || typeof google === 'undefined' || !google.maps) return;
        const mapDiv = document.getElementById('edit_map_' + fieldId);
        if (!mapDiv) return;

        const latInput = document.getElementById('edit_' + fieldId + '_lat');
        const lngInput = document.getElementById('edit_' + fieldId + '_lng');
        const addressInput = document.getElementById('edit_' + fieldId + '_address');
        const addressDisplay = document.getElementById('edit_map_' + fieldId + '_address');

        const defaultLat = parseFloat(latInput?.value) || 33.5138;
        const defaultLng = parseFloat(lngInput?.value) || 36.2765;
        const pos = { lat: defaultLat, lng: defaultLng };

        const map = new google.maps.Map(mapDiv, {
            center: pos,
            zoom: 13,
            mapTypeControl: true,
            streetViewControl: false,
        });
        const marker = new google.maps.Marker({
            position: pos,
            map: map,
            draggable: true,
        });
        const geocoder = new google.maps.Geocoder();

        function updateFromPosition(lat, lng) {
            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;
            geocoder.geocode({ location: { lat: lat, lng: lng } }, function (results, status) {
                if (status === 'OK' && results[0]) {
                    const addr = results[0].formatted_address;
                    if (addressInput) addressInput.value = addr;
                    if (addressDisplay) addressDisplay.textContent = addr;
                } else {
                    if (addressDisplay) addressDisplay.textContent = '';
                }
            });
        }

        marker.addListener('dragend', function () {
            const p = marker.getPosition();
            updateFromPosition(p.lat(), p.lng());
        });

        map.addListener('click', function (e) {
            const p = e.latLng;
            marker.setPosition(p);
            updateFromPosition(p.lat(), p.lng());
        });

        if (latInput?.value && lngInput?.value) {
            updateFromPosition(parseFloat(latInput.value), parseFloat(lngInput.value));
        }

        maps[fieldId] = { map: map, marker: marker, updateFromPosition: updateFromPosition };

        const useLocationBtn = document.getElementById('edit_btn_use_my_location_' + fieldId);
        if (useLocationBtn) {
            useLocationBtn.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    alert('{{ __("frontend.ads.geolocation_not_supported") }}');
                    return;
                }
                useLocationBtn.disabled = true;
                useLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("frontend.ads.getting_location") }}';
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const gpos = { lat: lat, lng: lng };
                        marker.setPosition(gpos);
                        map.setCenter(gpos);
                        map.setZoom(16);
                        updateFromPosition(lat, lng);
                        useLocationBtn.disabled = false;
                        useLocationBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> {{ __("frontend.ads.use_my_location") }}';
                    },
                    function (error) {
                        useLocationBtn.disabled = false;
                        useLocationBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> {{ __("frontend.ads.use_my_location") }}';
                        alert(error.code === 1 ? '{{ __("frontend.ads.location_permission_denied") }}' : '{{ __("frontend.ads.location_error") }}');
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        }
    }

    locationFields.forEach(initEditMap);
});
</script>
@endpush
@endif
@endsection

