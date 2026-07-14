@extends('frontend.layouts.app')

@section('title', __('frontend.ads.ad_details'))

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('frontend.ads.ad_details') }}</h1>
                <p class="text-gray-600">{{ __('frontend.ads.fill_ad_details') }}</p>
            </div>

            <!-- Selected Category Path -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    @if(!empty($adData['subcategories']))
                        @foreach(array_reverse($adData['subcategories']) as $index => $sub)
                            <span class="text-gray-700">{{ $sub['name'] }}</span>
                            @if($index < count($adData['subcategories']) - 1)
                                <span class="text-gray-400"> < </span>
                            @endif
                        @endforeach
                        <span class="text-gray-400"> < </span>
                    @endif
                    <span class="text-primary font-semibold">{{ $category->getName(app()->getLocale()) }}</span>
                </div>
            </div>

            <!-- Ad Details Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('ads.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('frontend.ads.title') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="title"
                               id="title"
                               value="{{ old('title') }}"
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
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @php $hasCustomFields = !empty($customFields); @endphp

                    @if(!$hasCustomFields)
                    <!-- Price -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.price') }} <span class="text-gray-500">({{ __('frontend.optional') }})</span>
                            </label>
                            <input type="number"
                                   name="price"
                                   id="price"
                                   value="{{ old('price') }}"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="{{ __('frontend.ads.price_placeholder') }}">
                            @error('price')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.currency') }}
                            </label>
                            <select name="currency"
                                    id="currency"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="SYP" {{ old('currency', \App\Models\Setting::get('default_currency', 'SYP')) === 'SYP' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('SYP') }} - الليرة السورية</option>
                                <option value="TRY" {{ old('currency') === 'TRY' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('TRY') }} - الليرة التركية</option>
                                <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('USD') }} - الدولار الأمريكي</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('EUR') }} - اليورو</option>
                            </select>
                            @error('currency')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    @endif

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
                                    $fieldValue = old('custom_fields.' . $fieldId);
                                @endphp

                                <div>
                                    <label for="custom_fields_{{ $fieldId }}" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ $fieldLabel }}
                                        @if($isRequired)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @if($fieldType === 'textarea')
                                        <textarea name="custom_fields[{{ $fieldId }}]"
                                                  id="custom_fields_{{ $fieldId }}"
                                                  rows="4"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                  @if($isRequired) required @endif>{{ $fieldValue }}</textarea>
                                    @elseif($fieldType === 'select' && isset($field['options']))
                                        <select name="custom_fields[{{ $fieldId }}]"
                                                id="custom_fields_{{ $fieldId }}"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                @if($isRequired) required @endif>
                                            <option value="">{{ __('frontend.ads.select_option') }}</option>
                                            @foreach($field['options'] as $option)
                                                <option value="{{ $option[app()->getLocale()] ?? $option['ar'] ?? $option }}"
                                                        {{ $fieldValue == ($option[app()->getLocale()] ?? $option['ar'] ?? $option) ? 'selected' : '' }}>
                                                    {{ $option[app()->getLocale()] ?? $option['ar'] ?? $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($fieldType === 'number')
                                        @if(!empty($field['show_currency']))
                                            @php $allowTbd = !empty($field['allow_tbd']); $isTbd = is_array($fieldValue) && !empty($fieldValue['tbd']); @endphp
                                            @if($allowTbd)
                                                <div class="flex items-center gap-2 mb-3">
                                                    <input type="checkbox" name="custom_fields[{{ $fieldId }}][tbd]" id="custom_fields_{{ $fieldId }}_tbd" value="1" {{ $isTbd ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                                    <label for="custom_fields_{{ $fieldId }}_tbd" class="text-sm font-medium text-gray-700">{{ __('frontend.ads.price_tbd') }}</label>
                                                </div>
                                            @endif
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 {{ $allowTbd ? 'tbd-value-fields' : '' }}" data-tbd-toggle="custom_fields_{{ $fieldId }}_tbd">
                                                <div>
                                                    <input type="number"
                                                           name="custom_fields[{{ $fieldId }}][value]"
                                                           id="custom_fields_{{ $fieldId }}_value"
                                                           value="{{ is_array($fieldValue) && !empty($fieldValue['tbd']) ? '' : (is_array($fieldValue) ? ($fieldValue['value'] ?? $fieldValue) : $fieldValue) }}"
                                                           step="{{ $field['step'] ?? 1 }}"
                                                           min="{{ $field['min'] ?? '' }}"
                                                           max="{{ $field['max'] ?? '' }}"
                                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                           @if($isRequired && !$allowTbd) required @endif
                                                           placeholder="{{ $fieldLabel }}">
                                                </div>
                                                <div>
                                                    <select name="custom_fields[{{ $fieldId }}][currency]"
                                                            id="custom_fields_{{ $fieldId }}_currency"
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                                        <option value="SYP" {{ (is_array($fieldValue) ? ($fieldValue['currency'] ?? '') : '') === 'SYP' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('SYP') }}</option>
                                                        <option value="TRY" {{ (is_array($fieldValue) ? ($fieldValue['currency'] ?? '') : '') === 'TRY' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('TRY') }}</option>
                                                        <option value="USD" {{ (is_array($fieldValue) ? ($fieldValue['currency'] ?? '') : '') === 'USD' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('USD') }}</option>
                                                        <option value="EUR" {{ (is_array($fieldValue) ? ($fieldValue['currency'] ?? '') : '') === 'EUR' ? 'selected' : '' }}>{{ get_currency_symbol_for_code('EUR') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        @else
                                            <input type="number"
                                                   name="custom_fields[{{ $fieldId }}]"
                                                   id="custom_fields_{{ $fieldId }}"
                                                   value="{{ is_array($fieldValue) ? ($fieldValue['value'] ?? $fieldValue) : $fieldValue }}"
                                                   step="{{ $field['step'] ?? 1 }}"
                                                   min="{{ $field['min'] ?? '' }}"
                                                   max="{{ $field['max'] ?? '' }}"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                                   @if($isRequired) required @endif>
                                        @endif
                                    @elseif($fieldType === 'checkbox')
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox"
                                                   name="custom_fields[{{ $fieldId }}]"
                                                   id="custom_fields_{{ $fieldId }}"
                                                   value="1"
                                                   {{ $fieldValue ? 'checked' : '' }}
                                                   class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                                            <label for="custom_fields_{{ $fieldId }}" class="text-sm text-gray-600">
                                                {{ __('frontend.ads.yes') }}
                                            </label>
                                        </div>
                                    @elseif($fieldType === 'date')
                                        <input type="date"
                                               name="custom_fields[{{ $fieldId }}]"
                                               id="custom_fields_{{ $fieldId }}"
                                               value="{{ is_array($fieldValue) ? '' : $fieldValue }}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                               @if($isRequired) required @endif>
                                    @elseif($fieldType === 'location')
                                        <div class="space-y-3">
                                            <p class="text-sm text-gray-600">{{ __('frontend.ads.select_location_from_map') }}</p>
                                            <input type="hidden" name="custom_fields[{{ $fieldId }}][lat]" id="custom_fields_{{ $fieldId }}_lat" value="{{ is_array($fieldValue) ? ($fieldValue['lat'] ?? '') : '' }}" @if($isRequired) required @endif>
                                            <input type="hidden" name="custom_fields[{{ $fieldId }}][lng]" id="custom_fields_{{ $fieldId }}_lng" value="{{ is_array($fieldValue) ? ($fieldValue['lng'] ?? '') : '' }}" @if($isRequired) required @endif>
                                            <input type="hidden" name="custom_fields[{{ $fieldId }}][address]" id="custom_fields_{{ $fieldId }}_address" value="{{ is_array($fieldValue) ? ($fieldValue['address'] ?? '') : '' }}">
                                            <button type="button" id="btn_use_my_location_{{ $fieldId }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:opacity-90 transition flex items-center gap-2">
                                                <i class="fas fa-location-crosshairs"></i>
                                                {{ __('frontend.ads.use_my_location') }}
                                            </button>
                                            <div id="map_{{ $fieldId }}" class="w-full h-64 border border-gray-300 rounded-lg bg-gray-100 z-0"></div>
                                            <p id="map_{{ $fieldId }}_hint" class="text-xs text-gray-500">{{ __('frontend.ads.click_map_to_select') }}</p>
                                            <div id="map_{{ $fieldId }}_address" class="text-sm text-gray-700 mt-1 min-h-[1.5rem]"></div>
                                        </div>
                                    @elseif($fieldType === 'car_body_map')
                                        @include('partials.car-body-map-field', ['fieldId' => $fieldId, 'fieldValue' => $fieldValue])
                                    @else
                                        <input type="text"
                                               name="custom_fields[{{ $fieldId }}]"
                                               id="custom_fields_{{ $fieldId }}"
                                               value="{{ $fieldValue }}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                               @if($isRequired) required @endif>
                                    @endif

                                    @error('custom_fields.' . $fieldId)
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Location Section (قبل الصور) -->
                    <div class="border-t pt-6" id="ad-location-section"
                         data-geo-api="{{ url('/api/v1') }}"
                         data-has-google-maps="{{ config('services.google_maps.api_key') ? '1' : '0' }}">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('frontend.ads.location_section') }}</h3>
                        <input type="hidden" name="location_input_method" id="location_input_method"
                               value="{{ config('services.google_maps.api_key') ? old('location_input_method', 'map') : old('location_input_method', 'manual') }}">
                        <input type="hidden" name="latitude" id="ad_main_lat" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="ad_main_lng" value="{{ old('longitude') }}">
                        <input type="hidden" name="location_state" id="ad_loc_state_txt" value="{{ old('location_state') }}">
                        <input type="hidden" name="location_city" id="ad_loc_city_txt" value="{{ old('location_city') }}">
                        <input type="hidden" name="location_district" id="ad_loc_district_txt" value="{{ old('location_district') }}">

                        <div class="space-y-4">
                            @if(!config('services.google_maps.api_key'))
                                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">{{ __('frontend.ads.location_map_unavailable') }}</p>
                            @endif

                            <div>
                                <label for="location_country" class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.ads.location_country') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="location_country" id="location_country" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">{{ __('frontend.ads.select_country') }}</option>
                                    <option value="SY" {{ old('location_country', 'SY') === 'SY' ? 'selected' : '' }}>🇸🇾 {{ __('frontend.ads.country_sy') }}</option>
                                    <option value="TR" {{ old('location_country') === 'TR' ? 'selected' : '' }}>🇹🇷 {{ __('frontend.ads.country_tr') }}</option>
                                </select>
                                @error('location_country')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="ad-region-cascade" class="space-y-4">
                                <div>
                                    <label for="location_state_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.ads.location_state') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select name="location_state_code" id="location_state_code"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="">{{ __('frontend.ads.select_state_first') }}</option>
                                    </select>
                                    @error('location_state_code')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="location_city_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.ads.location_city_level') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select name="location_city_code" id="location_city_code" disabled
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="">{{ __('frontend.ads.select_city_after_state') }}</option>
                                    </select>
                                    @error('location_city_code')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="location_district_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.ads.location_district_level') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select name="location_district_code" id="location_district_code" disabled
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="">{{ __('frontend.ads.select_district_after_city') }}</option>
                                    </select>
                                    @error('location_district_code')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div id="ad-map-location" class="space-y-3 hidden">
                                <p class="text-sm text-gray-600">{{ __('frontend.ads.select_location_from_map') }}</p>
                                <button type="button" id="btn_ad_main_use_my_location" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:opacity-90 transition flex items-center gap-2">
                                    <i class="fas fa-location-crosshairs"></i>
                                    {{ __('frontend.ads.use_my_location') }}
                                </button>
                                <div id="ad_main_map" class="w-full h-64 border border-gray-300 rounded-lg bg-gray-100 z-0"></div>
                                <p id="ad_main_map_hint" class="text-xs text-gray-500">{{ __('frontend.ads.click_map_to_select') }}</p>
                                <p id="ad_main_map_address" class="text-sm text-gray-700 min-h-[1.5rem]"></p>
                            </div>

                            <div>
                                <label for="location_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.ads.location_street') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                                </label>
                                <input type="text" name="location_address" id="location_address" value="{{ old('location_address') }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                       placeholder="{{ __('frontend.ads.location_street_placeholder') }}">
                                @error('location_address')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @if($canFeatured || $canUrgent)
                    <!-- Featured / Urgent (from package) -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('frontend.ads.promote_ad') }}</h3>
                        <div class="flex flex-wrap gap-4">
                            @if($canFeatured)
                                <label class="flex items-center gap-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg cursor-pointer hover:bg-yellow-100">
                                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                           class="w-5 h-5 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                                    <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.ads.featured') }}" class="w-4 h-4">
                                    <span class="font-semibold text-gray-800">{{ __('frontend.ads.featured') }}</span>
                                    <span class="text-sm text-gray-600">({{ __('frontend.ads.remaining') }}: {{ $remainingFeatured }})</span>
                                </label>
                            @endif
                            @if($canUrgent)
                                <label class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg cursor-pointer hover:bg-red-100">
                                    <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent') ? 'checked' : '' }}
                                           class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                    <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.ads.urgent') }}" class="w-4 h-4">
                                    <span class="font-semibold text-gray-800">{{ __('frontend.ads.urgent') }}</span>
                                    <span class="text-sm text-gray-600">({{ __('frontend.ads.remaining') }}: {{ $remainingUrgent }})</span>
                                </label>
                            @endif
                        </div>
                        @error('is_featured')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        @error('is_urgent')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @php
                        $adImagesCfg = $adImagesConfig ?? ['mode' => 'user_upload', 'gallery_paths' => [], 'gallery_urls' => []];
                        $useAdminGallery = ($adImagesCfg['mode'] ?? 'user_upload') === \App\Support\AdImagesConfig::MODE_ADMIN_GALLERY;
                        $galleryPaths = $adImagesCfg['gallery_paths'] ?? [];
                        $galleryItems = [];
                        foreach ($galleryPaths as $path) {
                            if ($path) {
                                $galleryItems[] = ['path' => $path, 'url' => asset('storage/' . $path)];
                            }
                        }
                    @endphp

                    <!-- Images -->
                    @if($useAdminGallery && count($galleryItems) > 0)
                        <div class="border-t pt-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.images') }} <span class="text-red-500">*</span>
                            </label>
                            <p class="text-sm text-gray-600 mb-4">{{ __('frontend.ads.gallery_pick_one_hint') }}</p>
                            <input type="hidden" name="gallery_image" id="gallery_image_input" value="{{ old('gallery_image') }}" required>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                @foreach($galleryItems as $idx => $item)
                                    <label class="gallery-tile relative block cursor-pointer rounded-xl border-2 border-gray-200 hover:border-primary p-2 bg-white transition has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/30">
                                        <input type="radio" name="gallery_pick" value="{{ $item['path'] }}" class="gallery-radio sr-only"
                                            {{ old('gallery_image') === $item['path'] ? 'checked' : '' }}>
                                        <div class="relative rounded-lg bg-gray-50">
                                            <img src="{{ $item['url'] }}" alt="" class="w-full h-44 object-contain rounded-lg pointer-events-none select-none">
                                            <button type="button"
                                                class="gallery-zoom-btn absolute top-2 left-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-white shadow-md transition hover:bg-black/75 focus:outline-none focus:ring-2 focus:ring-primary"
                                                data-gallery-index="{{ $idx }}"
                                                title="{{ __('frontend.ads.gallery_zoom_aria') }}"
                                                aria-label="{{ __('frontend.ads.gallery_zoom_aria') }}">
                                                <i class="fas fa-search-plus text-sm"></i>
                                            </button>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <div id="gallery-lightbox" class="fixed inset-0 z-[200] hidden flex-col items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-hidden="true">
                                <button type="button" id="gallery-lightbox-close" class="absolute top-4 right-4 z-20 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white" title="{{ __('frontend.ads.gallery_lightbox_close') }}" aria-label="{{ __('frontend.ads.gallery_lightbox_close') }}">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                                <button type="button" id="gallery-lightbox-prev" class="absolute left-2 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white sm:left-4" aria-label="{{ __('frontend.ads.gallery_lightbox_prev') }}">
                                    <i class="fas fa-chevron-left text-xl" aria-hidden="true"></i>
                                </button>
                                <button type="button" id="gallery-lightbox-next" class="absolute right-2 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white sm:right-4" aria-label="{{ __('frontend.ads.gallery_lightbox_next') }}">
                                    <i class="fas fa-chevron-right text-xl" aria-hidden="true"></i>
                                </button>
                                <div class="flex max-h-[78vh] w-full max-w-5xl flex-1 items-center justify-center overflow-auto">
                                    <img id="gallery-lightbox-img" src="" alt="" class="max-h-[78vh] max-w-full object-contain">
                                </div>
                                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                    <button type="button" id="gallery-lightbox-select" class="btn-primary rounded-xl px-6 py-3 font-semibold shadow-lg">
                                        <i class="fas fa-check ml-2"></i>
                                        {{ __('frontend.ads.gallery_lightbox_select') }}
                                    </button>
                                </div>
                            </div>
                            @error('gallery_image')
                                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    @elseif($useAdminGallery)
                        <div class="border-t pt-6 text-red-600 text-sm">
                            {{ __('frontend.ads.gallery_not_configured') }}
                        </div>
                    @else
                        <div class="border-t pt-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.ads.images') }} <span class="text-red-500">*</span>
                            </label>
                            <p class="text-sm text-gray-600 mb-3">{{ __('frontend.ads.reorder_images_hint') }}</p>

                            <div id="images-inputs" class="space-y-3">
                                <input type="file"
                                       name="images[]"
                                       id="images"
                                       multiple
                                       accept="image/*"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary images-input">
                            </div>

                            <button type="button"
                                    id="add-more-images"
                                    class="mt-3 inline-flex items-center px-4 py-2 border border-dashed border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-plus ml-2"></i>
                                {{ __('frontend.ads.add_more_images') }}
                            </button>

                            <p class="text-xs text-gray-500 mt-2">
                                {{ __('frontend.ads.images_hint') }}
                            </p>
                            @error('images.*')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="images-preview" class="hidden border-t pt-6" data-remove-label="{{ __('frontend.ads.remove_image') }}"
                             data-primary-label="{{ __('frontend.ads.image_primary_badge') }}"
                             data-set-primary-label="{{ __('frontend.ads.set_as_primary_image') }}"
                             data-up-label="{{ __('frontend.ads.move_image_up') }}"
                             data-down-label="{{ __('frontend.ads.move_image_down') }}">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('frontend.ads.selected_images') }}:</h3>
                            <div id="images-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"></div>
                        </div>
                    @endif

                    @php
                        $vDur = isset($adVideoMaxDurationSeconds) ? (int) $adVideoMaxDurationSeconds : 60;
                        $vMb = isset($adVideoMaxSizeMb) ? (int) $adVideoMaxSizeMb : 50;
                    @endphp
                    <div class="border-t pt-6 mt-6">
                        <label for="ad_video_input" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('frontend.ads.video_optional') }}
                        </label>
                        <input type="file"
                               name="video"
                               id="ad_video_input"
                               accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-primary/10 file:text-primary">
                        <p class="text-xs text-gray-500 mt-2">
                            {{ __('frontend.ads.video_hint', ['seconds' => $vDur, 'max' => $vMb]) }}
                        </p>
                        @error('video')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-between gap-4 pt-4 border-t">
                        <a href="{{ route('ads.create.subcategory') }}"
                           class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold">
                            <i class="fas fa-arrow-right ml-2"></i>
                            {{ __('frontend.back') }}
                        </a>
                        <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                            <i class="fas fa-check ml-2"></i>
                            {{ __('frontend.ads.submit_ad') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    (function initAdLocationHierarchy() {
        const section = document.getElementById('ad-location-section');
        if (!section) return;
        const geoApi = (section.getAttribute('data-geo-api') || '').replace(/\/$/, '');
        const pageLocale = @json(app()->getLocale());
        const countrySel = document.getElementById('location_country');
        const methodInput = document.getElementById('location_input_method');
        const mapBox = document.getElementById('ad-map-location');
        const scSel = document.getElementById('location_state_code');
        const ccSel = document.getElementById('location_city_code');
        const dcSel = document.getElementById('location_district_code');
        const stTxt = document.getElementById('ad_loc_state_txt');
        const ciTxt = document.getElementById('ad_loc_city_txt');
        const diTxt = document.getElementById('ad_loc_district_txt');
        if (!countrySel || !methodInput || !mapBox) return;

        let stateItems = [];

        function effectiveLocale() {
            // Requirement: if country is Turkey, show location lists in Turkish even if site locale is Arabic.
            const cc = (countrySel && countrySel.value) ? String(countrySel.value).toUpperCase() : '';
            if (cc === 'TR') return 'tr';
            return (pageLocale || 'ar').toLowerCase();
        }

        function pickDisplayName(item) {
            if (!item) return '';
            if (item.name) return item.name;
            var loc = effectiveLocale();
            if (loc === 'tr' && item.name_tr) return item.name_tr;
            if (loc === 'en' && item.name_en) return item.name_en;
            return item.name_ar || item.name_en || item.name_tr || item.code || '';
        }

        function geoItemNode(it) {
            return {
                code: it.code,
                name: pickDisplayName(it),
                match_names: (it.match_names && it.match_names.length) ? it.match_names : [pickDisplayName(it)]
            };
        }

        function fetchGeoJson(url) {
            var loc = effectiveLocale();
            return fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Locale': loc,
                    'Accept-Language': loc === 'en' ? 'en' : (loc === 'tr' ? 'tr' : 'ar'),
                },
            }).then(function (r) { return r.json(); });
        }

        function setMode(m) {
            methodInput.value = m;
            if (m === 'map') {
                mapBox.classList.remove('hidden');
                if (scSel) { scSel.setAttribute('required', 'required'); ccSel.setAttribute('required', 'required'); }
                if (dcSel) { dcSel.removeAttribute('required'); }
                window.dispatchEvent(new CustomEvent('ad-main-loc-mode-map'));
            } else {
                mapBox.classList.add('hidden');
                if (scSel) { scSel.setAttribute('required', 'required'); ccSel.setAttribute('required', 'required'); }
                if (dcSel) { dcSel.setAttribute('required', 'required'); }
                window.dispatchEvent(new CustomEvent('ad-main-loc-mode-manual'));
            }
        }

        // Requirement: remove "list/map" toggle — prefer map mode when Google Maps is available.
        const hasGoogleMaps = (section.getAttribute('data-has-google-maps') || '0') === '1';
        if (hasGoogleMaps) {
            setMode('map');
        } else {
            // Fallback: keep manual if map is not available.
            setMode('manual');
        }

        const optStatePh = @json(__('frontend.ads.select_state_first'));
        const optCityPh = @json(__('frontend.ads.select_city_after_state'));
        const optDistPh = @json(__('frontend.ads.select_district_after_city'));

        function populateCcSel(items) {
            ccSel.innerHTML = '<option value="">' + optCityPh + '</option>';
            dcSel.innerHTML = '<option value="">' + optDistPh + '</option>';
            dcSel.disabled = true;
            if (!items || !items.length) {
                ccSel.disabled = true;
                return;
            }
            ccSel.disabled = false;
            items.forEach(function (c) {
                var o = document.createElement('option');
                o.value = c.code;
                o.textContent = pickDisplayName(c);
                o.setAttribute('data-geo-id', String(c.id));
                ccSel.appendChild(o);
            });
        }

        function populateDcSel(items) {
            dcSel.innerHTML = '<option value="">' + optDistPh + '</option>';
            if (!items || !items.length) {
                dcSel.disabled = true;
                return;
            }
            dcSel.disabled = false;
            items.forEach(function (d) {
                var o = document.createElement('option');
                o.value = d.code;
                o.textContent = pickDisplayName(d);
                dcSel.appendChild(o);
            });
        }

        function fillStates() {
            if (!scSel) return;
            scSel.innerHTML = '<option value="">' + optStatePh + '</option>';
            populateCcSel([]);
            ccSel.disabled = true;
            stateItems.forEach(function (s) {
                var o = document.createElement('option');
                o.value = s.code;
                o.textContent = pickDisplayName(s);
                o.setAttribute('data-geo-id', String(s.id));
                scSel.appendChild(o);
            });
            window.__adRegionStates = stateItems.map(function (s) { return geoItemNode(s); });
        }

        function syncHiddenLabels() {
            stTxt.value = (scSel.selectedIndex > 0 && scSel.options[scSel.selectedIndex])
                ? scSel.options[scSel.selectedIndex].textContent.trim() : '';
            ciTxt.value = (ccSel.selectedIndex > 0 && ccSel.options[ccSel.selectedIndex])
                ? ccSel.options[ccSel.selectedIndex].textContent.trim() : '';
            diTxt.value = (dcSel.selectedIndex > 0 && dcSel.options[dcSel.selectedIndex])
                ? dcSel.options[dcSel.selectedIndex].textContent.trim() : '';
        }

        // Forward geocode (manual selects -> lat/lng) so map pin can be auto-set.
        const latInput = document.getElementById('ad_main_lat');
        const lngInput = document.getElementById('ad_main_lng');
        const addrInput = document.getElementById('location_address');
        let fwdTimer = null;
        let fwdSeq = 0;

        function buildManualAddressQuery() {
            const country = (countrySel && countrySel.value) ? countrySel.value.toUpperCase() : '';
            const countryName = country === 'TR' ? 'Turkey' : (country === 'SY' ? 'Syria' : '');
            const parts = [];
            const street = addrInput && addrInput.value ? addrInput.value.trim() : '';
            const district = diTxt && diTxt.value ? diTxt.value.trim() : '';
            const city = ciTxt && ciTxt.value ? ciTxt.value.trim() : '';
            const state = stTxt && stTxt.value ? stTxt.value.trim() : '';
            if (street) parts.push(street);
            if (district) parts.push(district);
            if (city) parts.push(city);
            if (state) parts.push(state);
            if (countryName) parts.push(countryName);
            return parts.filter(Boolean).join(', ');
        }

        function applyLatLng(lat, lng) {
            if (!latInput || !lngInput) return;
            latInput.value = String(lat);
            lngInput.value = String(lng);
            // If map is already initialized (map mode), move marker to the geocoded point.
            if (adMainMapBundle && adMainMapBundle.marker && adMainMapBundle.map && typeof adMainMapBundle.updateFromPosition === 'function') {
                try {
                    adMainMapBundle.marker.setPosition({ lat: lat, lng: lng });
                    adMainMapBundle.map.setCenter({ lat: lat, lng: lng });
                    adMainMapBundle.map.setZoom(13);
                    // do NOT reverse-geocode-selects here (to avoid loops). We already have manual selects.
                } catch (e) {}
            }
        }

        function forwardGeocodeManualNow() {
            if (!countrySel || !countrySel.value) return;
            if (!scSel) return;
            if (!scSel.value) return;
            syncHiddenLabels();
            const q = buildManualAddressQuery();
            // Query string may be empty if user didn't type street/district labels yet.
            // We'll first try to derive coords directly from geo_divisions by codes.

            const mySeq = ++fwdSeq;
            const loc = effectiveLocale();

            function tryCoordsFromCatalog() {
                if (!geoApi) return Promise.resolve(false);
                const cc = countrySel.value.toUpperCase();
                const url = geoApi + '/geo-coords?country=' + encodeURIComponent(cc)
                    + '&state_code=' + encodeURIComponent(scSel.value || '')
                    + '&city_code=' + encodeURIComponent(ccSel.value || '')
                    + '&district_code=' + encodeURIComponent((dcSel && dcSel.value) ? dcSel.value : '');
                return fetchGeoJson(url).then(function (j) {
                    if (mySeq !== fwdSeq) return false;
                    const d = (j && j.success && j.data) ? j.data : null;
                    const lat = d && typeof d.latitude !== 'undefined' ? parseFloat(d.latitude) : NaN;
                    const lng = d && typeof d.longitude !== 'undefined' ? parseFloat(d.longitude) : NaN;
                    if (isFinite(lat) && isFinite(lng)) {
                        applyLatLng(lat, lng);
                        return true;
                    }
                    return false;
                }).catch(function () { return false; });
            }

            // 1) Prefer catalog coordinates (geo_divisions) by selected codes.
            tryCoordsFromCatalog().then(function (ok) {
                if (ok) return;
                // 2) If we have an address query, fallback to external forward geocode.
                if (!q) return;

            // Prefer Google forward geocode if available; fallback to Nominatim.
            if (typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
                try {
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ address: q }, function (results, status) {
                        if (mySeq !== fwdSeq) return;
                        if (status === 'OK' && results && results[0] && results[0].geometry && results[0].geometry.location) {
                            const p = results[0].geometry.location;
                            applyLatLng(p.lat(), p.lng());
                        }
                    });
                    return;
                } catch (e) {}
            }

            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q), {
                headers: {
                    'Accept': 'application/json',
                    'Accept-Language': (loc === 'tr' ? 'tr' : (loc === 'en' ? 'en' : 'ar')),
                },
            })
                .then(function (r) { return r.json(); })
                .then(function (arr) {
                    if (mySeq !== fwdSeq) return;
                    if (!Array.isArray(arr) || !arr.length) return;
                    const it = arr[0];
                    const lat = parseFloat(it.lat);
                    const lng = parseFloat(it.lon);
                    if (isFinite(lat) && isFinite(lng)) {
                        applyLatLng(lat, lng);
                    }
                })
                .catch(function () {});
            });
        }

        function scheduleForwardGeocodeManual() {
            if (fwdTimer) clearTimeout(fwdTimer);
            fwdTimer = setTimeout(forwardGeocodeManualNow, 650);
        }

        function loadDistrictsForStateGeoId(stateGeoId) {
            populateCcSel([]);
            ccSel.disabled = true;
            if (!geoApi || !stateGeoId) {
                syncHiddenLabels();
                return Promise.resolve();
            }
            return fetchGeoJson(geoApi + '/districts/' + encodeURIComponent(stateGeoId)).then(function (j) {
                var items = (j && j.success && j.data && j.data.items) ? j.data.items : [];
                populateCcSel(items);
                syncHiddenLabels();
            }).catch(function () {
                populateCcSel([]);
                syncHiddenLabels();
            });
        }

        scSel.addEventListener('change', function () {
            var opt = scSel.options[scSel.selectedIndex];
            var gid = opt && opt.getAttribute('data-geo-id');
            loadDistrictsForStateGeoId(gid);
            scheduleForwardGeocodeManual();
        });

        ccSel.addEventListener('change', function () {
            var opt = ccSel.options[ccSel.selectedIndex];
            var gid = opt && opt.getAttribute('data-geo-id');
            if (!geoApi || !gid) {
                populateDcSel([]);
                syncHiddenLabels();
                scheduleForwardGeocodeManual();
                return;
            }
            fetchGeoJson(geoApi + '/neighborhoods/' + encodeURIComponent(gid)).then(function (j) {
                var items = (j && j.success && j.data && j.data.items) ? j.data.items : [];
                populateDcSel(items);
                syncHiddenLabels();
                scheduleForwardGeocodeManual();
            }).catch(function () {
                populateDcSel([]);
                syncHiddenLabels();
                scheduleForwardGeocodeManual();
            });
        });

        dcSel.addEventListener('change', function () {
            syncHiddenLabels();
            scheduleForwardGeocodeManual();
        });

        if (addrInput) {
            addrInput.addEventListener('change', scheduleForwardGeocodeManual);
            addrInput.addEventListener('blur', scheduleForwardGeocodeManual);
        }

        function matchGeocodeToSelects(admin1, admin2, locality, neighborhood, routeName, premise) {
            if (!geoApi || !scSel || !ccSel || !dcSel || !stateItems.length) {
                return Promise.resolve();
            }

            function norm(s) {
                if (!s) return '';
                var t = String(s).trim().replace(/\u0130/g, 'i').replace(/\u0131/g, 'i').toLowerCase();
                t = t.replace(/ş/g, 's').replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ö/g, 'o').replace(/ç/g, 'c');
                return t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            function strMatch(a, b) {
                if (!a || !b) return false;
                var x = norm(a);
                var y = norm(b);
                if (!x || !y) return false;
                if (x === y) return true;
                return x.indexOf(y) !== -1 || y.indexOf(x) !== -1;
            }

            function labelsForNode(node) {
                var a = node.match_names;
                if (Array.isArray(a) && a.length) return a;
                return node.name ? [node.name] : [];
            }

            function nameMatchesLabelAgainstPool(label, pool) {
                if (!label) return false;
                for (var i = 0; i < pool.length; i++) {
                    if (pool[i] && strMatch(label, pool[i])) return true;
                }
                return false;
            }

            function nodeMatchesPool(node, pool) {
                var labels = labelsForNode(node);
                for (var li = 0; li < labels.length; li++) {
                    if (nameMatchesLabelAgainstPool(labels[li], pool)) return true;
                }
                return false;
            }

            function nodeStrMatchSingle(node, s) {
                if (!s) return false;
                var labels = labelsForNode(node);
                for (var li = 0; li < labels.length; li++) {
                    if (strMatch(labels[li], s)) return true;
                }
                return false;
            }

            function scoreNodeAgainstNeedles(node, pool) {
                var labels = labelsForNode(node);
                var sc = 0;
                for (var li = 0; li < labels.length; li++) {
                    if (!labels[li]) continue;
                    for (var pi = 0; pi < pool.length; pi++) {
                        if (pool[pi] && strMatch(labels[li], pool[pi])) sc += 3;
                    }
                }
                return sc;
            }

            function pickFromList(list, needles, locality, admin2, neighborhood) {
                var cities = list.map(function (raw) { return geoItemNode(raw); });
                var city = null;
                var i;
                for (i = 0; i < cities.length; i++) {
                    if (nodeMatchesPool(cities[i], needles)) { city = cities[i]; break; }
                }
                if (!city && locality) {
                    for (i = 0; i < cities.length; i++) {
                        if (nodeStrMatchSingle(cities[i], locality)) { city = cities[i]; break; }
                    }
                }
                if (!city && admin2) {
                    for (i = 0; i < cities.length; i++) {
                        if (nodeStrMatchSingle(cities[i], admin2)) { city = cities[i]; break; }
                    }
                }
                if (!city) {
                    var best = 0;
                    var bestC = null;
                    for (i = 0; i < cities.length; i++) {
                        var sc = scoreNodeAgainstNeedles(cities[i], needles);
                        if (sc > best) { best = sc; bestC = cities[i]; }
                    }
                    if (best > 0) city = bestC;
                }
                if (!city && cities.length === 1) city = cities[0];
                return { city: city, rawList: list };
            }

            var needles = [admin1, admin2, locality, neighborhood, routeName, premise].filter(Boolean);
            var st = null;
            var si;
            for (si = 0; si < stateItems.length; si++) {
                if (nodeMatchesPool(geoItemNode(stateItems[si]), needles)) { st = stateItems[si]; break; }
            }
            if (!st && admin1) {
                for (si = 0; si < stateItems.length; si++) {
                    if (nodeStrMatchSingle(geoItemNode(stateItems[si]), admin1)) { st = stateItems[si]; break; }
                }
            }
            if (!st) return Promise.resolve();

            scSel.value = st.code;
            return fetchGeoJson(geoApi + '/districts/' + encodeURIComponent(st.id)).then(function (j) {
                var rawCities = (j && j.success && j.data && j.data.items) ? j.data.items : [];
                populateCcSel(rawCities);
                var picked = pickFromList(rawCities, needles, locality, admin2, neighborhood);
                if (!picked.city) {
                    syncHiddenLabels();
                    return Promise.resolve();
                }
                ccSel.value = picked.city.code;
                var rawCity = null;
                for (var ri = 0; ri < rawCities.length; ri++) {
                    if (rawCities[ri].code === picked.city.code) { rawCity = rawCities[ri]; break; }
                }
                if (!rawCity) {
                    syncHiddenLabels();
                    return Promise.resolve();
                }
                return fetchGeoJson(geoApi + '/neighborhoods/' + encodeURIComponent(rawCity.id)).then(function (j2) {
                    var dists = (j2 && j2.success && j2.data && j2.data.items) ? j2.data.items : [];
                    populateDcSel(dists);
                    var dn = dists.map(function (raw) { return geoItemNode(raw); });
                    var d = null;
                    var dj;
                    for (dj = 0; dj < dn.length; dj++) {
                        if (nodeMatchesPool(dn[dj], needles)) { d = dn[dj]; break; }
                    }
                    if (!d && neighborhood) {
                        for (dj = 0; dj < dn.length; dj++) {
                            if (nodeStrMatchSingle(dn[dj], neighborhood)) { d = dn[dj]; break; }
                        }
                    }
                    if (!d) {
                        var bd = 0;
                        var bestD = null;
                        for (dj = 0; dj < dn.length; dj++) {
                            var sc2 = scoreNodeAgainstNeedles(dn[dj], needles);
                            if (sc2 > bd) { bd = sc2; bestD = dn[dj]; }
                        }
                        if (bd > 0) d = bestD;
                    }
                    if (!d && dn.length) d = dn[0];
                    if (d) dcSel.value = d.code;
                    syncHiddenLabels();
                });
            }).catch(function () {
                syncHiddenLabels();
            });
        }

        window.adMatchGeocodeToSelects = matchGeocodeToSelects;
        window.__adSyncHiddenLabels = syncHiddenLabels;

        function loadRegions(cc) {
            if (!geoApi || !cc) return Promise.resolve();
            return fetchGeoJson(geoApi + '/states?country=' + encodeURIComponent(cc)).then(function (j) {
                if (j && j.success && j.data && j.data.items) {
                    stateItems = j.data.items;
                } else {
                    stateItems = [];
                }
                fillStates();
            }).catch(function () {
                stateItems = [];
                fillStates();
            });
        }

        window.__adLoadRegions = loadRegions;

        var discoverMapUrl = geoApi ? geoApi + '/regions/discover-map' : '';


        window.__adAfterGeocodeMatchTryDiscover = function (lat, lng, ctx) {
            var methodEl = document.getElementById('location_input_method');
            if (!methodEl || methodEl.value !== 'map' || !discoverMapUrl) {
                return Promise.resolve();
            }
            var scEl = document.getElementById('location_state_code');
            var ccEl = document.getElementById('location_city_code');
            var dcEl = document.getElementById('location_district_code');
            if (!scEl || !ccEl) return Promise.resolve();
            if (scEl.value && ccEl.value) return Promise.resolve();

            var cSel = document.getElementById('location_country');
            var cc = cSel && cSel.value;
            if (cc !== 'SY' && cc !== 'TR') return Promise.resolve();

            var needles = [
                ctx.admin1, ctx.admin2, ctx.locality, ctx.neighborhood,
                ctx.routeName, ctx.premise, ctx.formatted || ''
            ].filter(Boolean);

            var body = {
                country: cc,
                latitude: lat,
                longitude: lng,
                primary: {
                    administrative_area: ctx.admin1 || '',
                    sub_administrative_area: ctx.admin2 || '',
                    locality: ctx.locality || '',
                    sub_locality: ctx.neighborhood || ''
                },
                needles: needles
            };

            return fetch(discoverMapUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j || !j.success || !j.data) return;
                    var d = j.data;
                    var st = d.location_state_code;
                    var cityC = d.location_city_code;
                    var distC = d.location_district_code;
                    if (!st || !cityC) return;
                    var reload = typeof window.__adLoadRegions === 'function'
                        ? window.__adLoadRegions(cc)
                        : Promise.resolve();
                    return reload.then(function () {
                        var stObj = stateItems.find(function (x) { return x.code === st; });
                        if (!stObj) {
                            if (typeof window.__adSyncHiddenLabels === 'function') window.__adSyncHiddenLabels();
                            return;
                        }
                        scEl.value = st;
                        return fetchGeoJson(geoApi + '/districts/' + encodeURIComponent(stObj.id)).then(function (dj) {
                            var cities = (dj && dj.success && dj.data && dj.data.items) ? dj.data.items : [];
                            populateCcSel(cities);
                            ccEl.value = cityC;
                            var copt = ccEl.options[ccEl.selectedIndex];
                            var cid = copt && copt.getAttribute('data-geo-id');
                            if (!cid) {
                                if (typeof window.__adSyncHiddenLabels === 'function') window.__adSyncHiddenLabels();
                                return;
                            }
                            return fetchGeoJson(geoApi + '/neighborhoods/' + encodeURIComponent(cid)).then(function (nj) {
                                var nh = (nj && nj.success && nj.data && nj.data.items) ? nj.data.items : [];
                                populateDcSel(nh);
                                if (dcEl && distC) dcEl.value = distC;
                                if (typeof window.__adSyncHiddenLabels === 'function') window.__adSyncHiddenLabels();
                            });
                        });
                    });
                })
                .catch(function () {});
        };

        countrySel.addEventListener('change', function () {
            loadRegions(countrySel.value);
            scheduleForwardGeocodeManual();
        });
        loadRegions(countrySel.value);
    })();

    const useAdminGallery = @json($useAdminGallery && count($galleryItems) > 0);
    const galleryInput = document.getElementById('gallery_image_input');
    if (useAdminGallery && galleryInput) {
        const galleryItems = @json($galleryItems);
        document.querySelectorAll('.gallery-radio').forEach(function(r) {
            r.addEventListener('change', function() {
                galleryInput.value = this.value;
            });
        });
        const checked = document.querySelector('.gallery-radio:checked');
        if (checked) {
            galleryInput.value = checked.value;
        }

        const lightbox = document.getElementById('gallery-lightbox');
        const lightboxImg = document.getElementById('gallery-lightbox-img');
        const lightboxClose = document.getElementById('gallery-lightbox-close');
        const lightboxPrev = document.getElementById('gallery-lightbox-prev');
        const lightboxNext = document.getElementById('gallery-lightbox-next');
        const lightboxSelect = document.getElementById('gallery-lightbox-select');
        let lightboxIndex = 0;

        function openLightbox(index) {
            if (!lightbox || !lightboxImg || !galleryItems.length) {
                return;
            }
            const n = galleryItems.length;
            lightboxIndex = ((index % n) + n) % n;
            lightboxImg.src = galleryItems[lightboxIndex].url;
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            if (!lightbox) {
                return;
            }
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function lightboxShowDelta(delta) {
            if (!galleryItems.length || !lightboxImg) {
                return;
            }
            const n = galleryItems.length;
            lightboxIndex = (lightboxIndex + delta + n) % n;
            lightboxImg.src = galleryItems[lightboxIndex].url;
        }

        document.querySelectorAll('.gallery-zoom-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const idx = parseInt(this.getAttribute('data-gallery-index'), 10);
                if (!isNaN(idx)) {
                    openLightbox(idx);
                }
            });
        });

        if (lightboxClose) {
            lightboxClose.addEventListener('click', function(e) {
                e.stopPropagation();
                closeLightbox();
            });
        }
        if (lightbox) {
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });
        }
        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', function(e) {
                e.stopPropagation();
                lightboxShowDelta(-1);
            });
        }
        if (lightboxNext) {
            lightboxNext.addEventListener('click', function(e) {
                e.stopPropagation();
                lightboxShowDelta(1);
            });
        }
        if (lightboxSelect) {
            lightboxSelect.addEventListener('click', function(e) {
                e.stopPropagation();
                const path = galleryItems[lightboxIndex] && galleryItems[lightboxIndex].path;
                if (!path) {
                    return;
                }
                document.querySelectorAll('.gallery-radio').forEach(function(r) {
                    if (r.value === path) {
                        r.checked = true;
                        galleryInput.value = path;
                        r.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                closeLightbox();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (!lightbox || lightbox.classList.contains('hidden')) {
                return;
            }
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                lightboxShowDelta(-1);
            } else if (e.key === 'ArrowRight') {
                lightboxShowDelta(1);
            }
        });

        return;
    }

    const MAX_IMAGES = {{ max(1, (int) ($adImagesMax ?? \App\Support\AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES)) }};
    const imagesInputsContainer = document.getElementById('images-inputs');
    const addMoreImagesBtn = document.getElementById('add-more-images');
    const imagesPreview = document.getElementById('images-preview');
    const imagesList = document.getElementById('images-list');
    const form = document.querySelector('form');
    if (!imagesInputsContainer || !imagesPreview || !imagesList) {
        return;
    }

    const removeLabel = imagesPreview.dataset.removeLabel || 'Remove';
    const primaryLabel = imagesPreview.dataset.primaryLabel || 'Primary';
    const setPrimaryLabel = imagesPreview.dataset.setPrimaryLabel || 'Set primary';
    const upLabel = imagesPreview.dataset.upLabel || 'Up';
    const downLabel = imagesPreview.dataset.downLabel || 'Down';

    let selectedFiles = [];

    function renderPreviews() {
        imagesList.innerHTML = '';
        selectedFiles.forEach(function(item, index) {
            const imageItem = document.createElement('div');
            imageItem.className = 'relative border border-gray-200 rounded-lg p-2 bg-white flex flex-col gap-2';
            const isPrimary = index === 0;
            imageItem.innerHTML = `
                <div class="relative bg-gray-50 rounded-lg overflow-hidden" style="min-height: 8rem;">
                    <img src="${item.dataUrl}" alt="${item.file.name}" class="w-full h-40 object-contain rounded-lg">
                    ${isPrimary ? '<span class="absolute top-2 left-2 bg-primary text-white text-xs font-bold px-2 py-1 rounded">' + primaryLabel + '</span>' : ''}
                    <span class="absolute top-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
                        ${(item.file.size / 1024 / 1024).toFixed(2)} MB
                    </span>
                </div>
                <div class="flex flex-wrap gap-1">
                    <button type="button" class="flex-1 min-w-[4rem] bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs py-2 px-2 rounded move-up-btn" data-index="${index}" ${index === 0 ? 'disabled' : ''}>${upLabel}</button>
                    <button type="button" class="flex-1 min-w-[4rem] bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs py-2 px-2 rounded move-down-btn" data-index="${index}" ${index === selectedFiles.length - 1 ? 'disabled' : ''}>${downLabel}</button>
                    <button type="button" class="flex-1 min-w-[4rem] bg-primary/90 hover:bg-primary text-white text-xs py-2 px-2 rounded set-primary-btn" data-index="${index}" ${isPrimary ? 'disabled' : ''}>${setPrimaryLabel}</button>
                </div>
                <button type="button" class="w-full bg-red-500 hover:bg-red-600 text-white text-xs font-medium py-2 px-3 rounded-lg remove-image-btn" data-index="${index}"><i class="fas fa-trash-alt ml-1"></i> ${removeLabel}</button>
            `;
            imagesList.appendChild(imageItem);
        });

        imagesList.querySelectorAll('.remove-image-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'), 10);
                selectedFiles.splice(index, 1);
                syncFileInput();
                renderPreviews();
                if (selectedFiles.length === 0) {
                    imagesPreview.classList.add('hidden');
                }
            });
        });
        imagesList.querySelectorAll('.move-up-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const i = parseInt(this.getAttribute('data-index'), 10);
                if (i <= 0) return;
                const t = selectedFiles[i - 1];
                selectedFiles[i - 1] = selectedFiles[i];
                selectedFiles[i] = t;
                syncFileInput();
                renderPreviews();
            });
        });
        imagesList.querySelectorAll('.move-down-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const i = parseInt(this.getAttribute('data-index'), 10);
                if (i >= selectedFiles.length - 1) return;
                const t = selectedFiles[i + 1];
                selectedFiles[i + 1] = selectedFiles[i];
                selectedFiles[i] = t;
                syncFileInput();
                renderPreviews();
            });
        });
        imagesList.querySelectorAll('.set-primary-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const i = parseInt(this.getAttribute('data-index'), 10);
                if (i <= 0) return;
                const item = selectedFiles.splice(i, 1)[0];
                selectedFiles.unshift(item);
                syncFileInput();
                renderPreviews();
            });
        });
    }

    function addFiles(files) {
        const remaining = MAX_IMAGES - selectedFiles.length;
        if (remaining <= 0) return;
        const toAdd = Array.from(files).filter(function(f) { return f.type && f.type.startsWith('image/'); }).slice(0, remaining);
        if (toAdd.length === 0) return;
        let processed = 0;
        toAdd.forEach(function(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                selectedFiles.push({ file: file, dataUrl: e.target.result });
                processed++;
                if (processed === toAdd.length) {
                    syncFileInput();
                    renderPreviews();
                    imagesPreview.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(file);
        });
    }

    function handleFiles(input) {
        if (input.files.length === 0) return;
        addFiles(input.files);
    }

    function syncFileInput() {
        const firstInput = imagesInputsContainer.querySelector('.images-input');
        if (!firstInput) return;
        const dt = new DataTransfer();
        selectedFiles.forEach(function(item) {
            if (item && item.file && item.file instanceof File) {
                dt.items.add(item.file);
            }
        });
        firstInput.files = dt.files;
    }

    imagesInputsContainer.querySelectorAll('.images-input').forEach(function(input) {
        input.addEventListener('change', function() {
            handleFiles(this);
        });
    });

    if (addMoreImagesBtn) {
        addMoreImagesBtn.addEventListener('click', function() {
            if (selectedFiles.length >= MAX_IMAGES) return;
            const firstInput = imagesInputsContainer.querySelector('.images-input');
            if (firstInput) firstInput.click();
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (selectedFiles.length > 0) {
                e.preventDefault();
                syncFileInput();
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> {{ __("frontend.ads.submit_ad") }}';
                }
                form.submit();
            }
        });
    }
});
</script>

@if(config('services.google_maps.api_key'))
@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&language={{ app()->getLocale() }}"></script>
<script>
const locationFields = @json(collect($customFields)->where('type', 'location')->pluck('id')->toArray());
const maps = {};

function initMap(fieldId) {
    if (maps[fieldId] || typeof google === 'undefined' || !google.maps) return;
    const mapDiv = document.getElementById('map_' + fieldId);
    if (!mapDiv) return;

    const latInput = document.getElementById('custom_fields_' + fieldId + '_lat');
    const lngInput = document.getElementById('custom_fields_' + fieldId + '_lng');
    const addressInput = document.getElementById('custom_fields_' + fieldId + '_address');
    const addressDisplay = document.getElementById('map_' + fieldId + '_address');

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

    const useLocationBtn = document.getElementById('btn_use_my_location_' + fieldId);
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
                    if (error.code === 1) {
                        alert('{{ __("frontend.ads.location_permission_denied") }}');
                    } else {
                        alert('{{ __("frontend.ads.location_error") }}');
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }
}

let adMainMapBundle = null;

function initAdMainLocationMap() {
    if (adMainMapBundle || typeof google === 'undefined' || !google.maps) return;
    const mapDiv = document.getElementById('ad_main_map');
    const latInput = document.getElementById('ad_main_lat');
    const lngInput = document.getElementById('ad_main_lng');
    const countrySel = document.getElementById('location_country');
    const methodInput = document.getElementById('location_input_method');
    const addrLine = document.getElementById('ad_main_map_address');
    if (!mapDiv || !latInput || !lngInput) return;

    function centerForSelectedCountry() {
        const c = countrySel && countrySel.value;
        if (c === 'TR') return { lat: 41.0082, lng: 28.9784 };
        return { lat: 33.5138, lng: 36.2765 };
    }

    const fallback = centerForSelectedCountry();
    const defaultLat = parseFloat(latInput.value) || fallback.lat;
    const defaultLng = parseFloat(lngInput.value) || fallback.lng;
    const pos = { lat: defaultLat, lng: defaultLng };

    const map = new google.maps.Map(mapDiv, {
        center: pos,
        zoom: 12,
        mapTypeControl: true,
        streetViewControl: false,
    });
    const marker = new google.maps.Marker({
        position: pos,
        map: map,
        draggable: true,
    });
    const geocoder = new google.maps.Geocoder();

    function pickComponent(components, types) {
        if (!components) return '';
        for (let i = 0; i < components.length; i++) {
            const c = components[i];
            if (!c.types) continue;
            for (let t = 0; t < types.length; t++) {
                if (c.types.indexOf(types[t]) !== -1) {
                    return c.long_name || '';
                }
            }
        }
        return '';
    }

    function pickCountryShort(components) {
        if (!components) return '';
        for (let i = 0; i < components.length; i++) {
            const c = components[i];
            if (!c.types || c.types.indexOf('country') === -1) continue;
            return (c.short_name || c.long_name || '').toUpperCase();
        }
        return '';
    }

    function applyGeocodeResult(res, lat, lng) {
        if (!res || !res.address_components) return;
        const ac = res.address_components;
        const admin1 = pickComponent(ac, ['administrative_area_level_1']);
        const admin2 = pickComponent(ac, ['administrative_area_level_2']);
        const locality = pickComponent(ac, ['locality']);
        const neighborhood = pickComponent(ac, ['neighborhood', 'sublocality', 'sublocality_level_1']);
        const routeName = pickComponent(ac, ['route']);
        const premise = pickComponent(ac, ['premise', 'point_of_interest', 'establishment']);
        const countryShort = pickCountryShort(ac);
        if (addrLine) addrLine.textContent = res.formatted_address || '';

        const ctx = {
            admin1: admin1,
            admin2: admin2,
            locality: locality,
            neighborhood: neighborhood,
            routeName: routeName,
            premise: premise,
            formatted: res.formatted_address || ''
        };

        function runMatch() {
            var p = Promise.resolve();
            if (typeof window.adMatchGeocodeToSelects === 'function') {
                p = Promise.resolve(window.adMatchGeocodeToSelects(admin1, admin2, locality, neighborhood, routeName, premise));
            }
            p.then(function () {
                if (typeof window.__adSyncHiddenLabels === 'function') {
                    window.__adSyncHiddenLabels();
                }
            });
        }

        function tryDiscoverFromServer() {
            if (typeof lat !== 'number' || typeof lng !== 'number') return;
            if (typeof window.__adAfterGeocodeMatchTryDiscover === 'function') {
                window.__adAfterGeocodeMatchTryDiscover(lat, lng, ctx);
            }
        }

        if ((countryShort === 'SY' || countryShort === 'TR') && countrySel && countrySel.value !== countryShort) {
            countrySel.value = countryShort;
            const p = typeof window.__adLoadRegions === 'function' ? window.__adLoadRegions(countryShort) : Promise.resolve();
            p.then(function () {
                setTimeout(function () {
                    runMatch();
                    setTimeout(tryDiscoverFromServer, 50);
                }, 0);
            });
        } else {
            runMatch();
            setTimeout(tryDiscoverFromServer, 50);
        }
    }

    function updateFromPosition(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
        geocoder.geocode({ location: { lat: lat, lng: lng } }, function (results, status) {
            if (status === 'OK' && results[0]) {
                applyGeocodeResult(results[0], lat, lng);
            } else if (addrLine) {
                addrLine.textContent = '';
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

    if (latInput.value && lngInput.value) {
        updateFromPosition(parseFloat(latInput.value), parseFloat(lngInput.value));
    }

    adMainMapBundle = { map: map, marker: marker, updateFromPosition: updateFromPosition };

    if (countrySel) {
        countrySel.addEventListener('change', function () {
            if (!methodInput || methodInput.value !== 'map') return;
            const c = centerForSelectedCountry();
            marker.setPosition(c);
            map.setCenter(c);
            map.setZoom(11);
            updateFromPosition(c.lat, c.lng);
        });
    }

    const useBtn = document.getElementById('btn_ad_main_use_my_location');
    if (useBtn) {
        useBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('{{ __("frontend.ads.geolocation_not_supported") }}');
                return;
            }
            useBtn.disabled = true;
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const gpos = { lat: lat, lng: lng };
                    marker.setPosition(gpos);
                    map.setCenter(gpos);
                    map.setZoom(15);
                    updateFromPosition(lat, lng);
                    useBtn.disabled = false;
                },
                function () {
                    useBtn.disabled = false;
                    alert('{{ __("frontend.ads.location_error") }}');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }
}

window.addEventListener('ad-main-loc-mode-map', function () {
    initAdMainLocationMap();
});

document.addEventListener('DOMContentLoaded', function () {
    locationFields.forEach(function (fieldId) {
        const mapDiv = document.getElementById('map_' + fieldId);
        if (mapDiv) initMap(fieldId);
    });
    if (document.getElementById('location_input_method') && document.getElementById('location_input_method').value === 'map') {
        initAdMainLocationMap();
    }
});
</script>
@endpush
@endif
@endsection

