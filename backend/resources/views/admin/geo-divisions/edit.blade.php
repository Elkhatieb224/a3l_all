@extends('admin.layouts.app')

@section('title', __('admin.geo_divisions.edit_title'))
@section('page-title', __('admin.geo_divisions.edit_title'))

@section('content')
@php
    $extraOld = old('extra_match_names');
    if ($extraOld === null) {
        $extraOld = is_array($geo_division->extra_match_names)
            ? implode("\n", $geo_division->extra_match_names)
            : '';
    }
@endphp
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.geo-divisions.index', ['country' => $geo_division->country]) }}" class="text-primary hover:underline inline-flex items-center gap-2">
                <i class="fas fa-arrow-right"></i>
                {{ __('admin.geo_divisions.back_list') }}
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.geo-divisions.update', $geo_division) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 space-y-1">
                <p><span class="font-semibold">{{ __('admin.geo_divisions.readonly_country') }}:</span>
                    {{ $geo_division->country === 'TR' ? __('admin.geo_divisions.country_TR') : __('admin.geo_divisions.country_SY') }}</p>
                <p><span class="font-semibold">{{ __('admin.geo_divisions.readonly_level') }}:</span>
                    {{ __('admin.geo_divisions.level_' . (int) $geo_division->level) }}</p>
                <p><span class="font-semibold">{{ __('admin.geo_divisions.readonly_parent') }}:</span>
                    @if($geo_division->parent)
                        {{ $geo_division->parent->name_ar ?: $geo_division->parent->name_en ?: $geo_division->parent->name_tr ?: $geo_division->parent->code }}
                    @else
                        {{ __('admin.geo_divisions.no_parent') }}
                    @endif
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.geo_divisions.code') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $geo_division->code) }}" required maxlength="64" pattern="[A-Za-z0-9._\-]+"
                           class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.geo_divisions.sort_order') }}</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $geo_division->sort_order) }}" min="0" class="w-full border rounded-lg px-3 py-2" dir="ltr">
                </div>
            </div>

            <div class="border rounded-xl p-6 bg-gray-50 space-y-4">
                <h3 class="font-bold text-gray-800">{{ __('admin.geo_divisions.names_section') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('admin.geo_divisions.name_ar') }}</label>
                        <input type="text" name="name_ar" id="geo_name_ar" value="{{ old('name_ar', $geo_division->name_ar) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('admin.geo_divisions.name_en') }}</label>
                        <input type="text" name="name_en" id="geo_name_en" value="{{ old('name_en', $geo_division->name_en) }}" class="w-full border rounded-lg px-3 py-2" dir="ltr">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('admin.geo_divisions.name_tr') }}</label>
                        <input type="text" name="name_tr" id="geo_name_tr" value="{{ old('name_tr', $geo_division->name_tr) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
            </div>

            @if(!empty($googleMapsKey))
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">{{ __('admin.geo_divisions.maps_search_label') }}</label>
                    <input type="text" id="geo_places_search" placeholder="{{ __('admin.geo_divisions.maps_search_placeholder') }}"
                           class="w-full border rounded-lg px-3 py-2 bg-white max-w-xl" dir="ltr" autocomplete="off">
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.geo_divisions.latitude') }}</label>
                    <input type="text" name="latitude" id="geo_latitude" value="{{ old('latitude', $geo_division->latitude) }}" inputmode="decimal"
                           class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.geo_divisions.longitude') }}</label>
                    <input type="text" name="longitude" id="geo_longitude" value="{{ old('longitude', $geo_division->longitude) }}" inputmode="decimal"
                           class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr">
                </div>
            </div>

            <div class="border rounded-xl p-6 bg-white space-y-2">
                <label class="block text-sm font-semibold text-gray-800">{{ __('admin.geo_divisions.extra_section') }}</label>
                <textarea name="extra_match_names" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ $extraOld }}</textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">{{ __('admin.geo_divisions.save') }}</button>
                <a href="{{ route('admin.geo-divisions.index', ['country' => $geo_division->country]) }}" class="px-8 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('admin.geo_divisions.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@if(!empty($googleMapsKey))
<script>
function a3lGeoAdminInitMap() {
    var searchEl = document.getElementById('geo_places_search');
    if (!searchEl || !window.google || !google.maps || !google.maps.places) return;
    var ac = new google.maps.places.Autocomplete(searchEl, { fields: ['geometry', 'address_components', 'formatted_address'] });
    ac.addListener('place_changed', function () {
        var place = ac.getPlace();
        if (!place || !place.geometry || !place.geometry.location) return;
        var lat = place.geometry.location.lat();
        var lng = place.geometry.location.lng();
        var latIn = document.getElementById('geo_latitude');
        var lngIn = document.getElementById('geo_longitude');
        if (latIn) latIn.value = lat.toFixed(7);
        if (lngIn) lngIn.value = lng.toFixed(7);
        var comps = place.address_components || [];
        var locality = '';
        var subloc = '';
        var admin1 = '';
        comps.forEach(function (c) {
            var t = c.types || [];
            if (t.indexOf('locality') !== -1) locality = c.long_name || '';
            if (t.indexOf('administrative_area_level_1') !== -1) admin1 = c.long_name || '';
            if (t.indexOf('sublocality') !== -1 || t.indexOf('sublocality_level_1') !== -1) subloc = c.long_name || '';
        });
        var nameEn = document.getElementById('geo_name_en');
        if (nameEn && !nameEn.value.trim()) {
            nameEn.value = locality || subloc || admin1 || '';
        }
    });
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&libraries=places&callback=a3lGeoAdminInitMap"></script>
@endif
@endpush
