@extends('admin.layouts.app')

@section('title', __('admin.dynamic_regions.create_title'))
@section('page-title', __('admin.dynamic_regions.create_title'))

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.dynamic-regions.index') }}" class="text-primary hover:underline inline-flex items-center gap-2">
                <i class="fas fa-arrow-right"></i>
                {{ __('admin.dynamic_regions.back_list') }}
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

        <form action="{{ route('admin.dynamic-regions.store') }}" method="POST" class="space-y-8">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.dynamic_regions.country') }} <span class="text-red-500">*</span></label>
                    <select name="country" id="dr_country" required class="w-full border rounded-lg px-3 py-2 text-base">
                        <option value="SY" @selected(old('country', 'SY') === 'SY')>{{ __('admin.dynamic_regions.country_SY') }}</option>
                        <option value="TR" @selected(old('country') === 'TR')>{{ __('admin.dynamic_regions.country_TR') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.dynamic_regions.type') }} <span class="text-red-500">*</span></label>
                    <select name="type" id="dr_type" required class="w-full border rounded-lg px-3 py-2 text-base">
                        <option value="state" @selected(old('type') === 'state')>{{ __('admin.dynamic_regions.type_state') }}</option>
                        <option value="city" @selected(old('type') === 'city')>{{ __('admin.dynamic_regions.type_city') }}</option>
                        <option value="district" @selected(old('type') === 'district')>{{ __('admin.dynamic_regions.type_district') }}</option>
                    </select>
                </div>
            </div>

            <div class="border rounded-xl p-6 bg-gray-50 space-y-4">
                <h3 class="font-bold text-gray-800">{{ __('admin.dynamic_regions.names_section') }}</h3>
                <p class="text-sm text-gray-600">{{ __('admin.dynamic_regions.names_hint') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('admin.dynamic_regions.name_ar') }}</label>
                        <input type="text" name="name_ar" value="{{ old('name_ar') }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('admin.dynamic_regions.name_en') }}</label>
                        <input type="text" name="name_en" value="{{ old('name_en') }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('admin.dynamic_regions.name_tr') }}</label>
                        <input type="text" name="name_tr" value="{{ old('name_tr') }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
            </div>

            <div id="dr_block_city" class="border rounded-xl p-6 bg-amber-50/50 space-y-4 hidden">
                <h3 class="font-bold text-gray-800">{{ __('admin.dynamic_regions.city_parent_section') }}</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="city_parent_mode" value="anchor_static" class="dr_city_mode" @checked(old('city_parent_mode', 'anchor_static') === 'anchor_static')>
                        <span>{{ __('admin.dynamic_regions.mode_anchor_static') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="city_parent_mode" value="dynamic_state" class="dr_city_mode" @checked(old('city_parent_mode') === 'dynamic_state')>
                        <span>{{ __('admin.dynamic_regions.mode_dynamic_state') }}</span>
                    </label>
                </div>
                <div id="dr_anchor_wrap" class="space-y-2">
                    <label class="block text-sm font-semibold">{{ __('admin.dynamic_regions.anchor_state') }}</label>
                    <select name="anchor_state_code" id="anchor_state_code" class="w-full border rounded-lg px-3 py-2">
                        <option value="">{{ __('admin.dynamic_regions.select_placeholder') }}</option>
                        <optgroup label="{{ __('admin.dynamic_regions.country_SY') }}" data-dr-country="SY">
                            @foreach($staticSy as $s)
                                <option value="{{ $s['code'] ?? '' }}" @selected(old('anchor_state_code') === ($s['code'] ?? ''))>
                                    {{ \App\Support\RegionCatalog::labelForLocale($s, app()->getLocale()) }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ __('admin.dynamic_regions.country_TR') }}" data-dr-country="TR">
                            @foreach($staticTr as $s)
                                <option value="{{ $s['code'] ?? '' }}" @selected(old('anchor_state_code') === ($s['code'] ?? ''))>
                                    {{ \App\Support\RegionCatalog::labelForLocale($s, app()->getLocale()) }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <p class="text-xs text-amber-800">{{ __('admin.dynamic_regions.anchor_pick_hint') }}</p>
                </div>
                <div id="dr_dyn_state_wrap" class="space-y-2 hidden">
                    <label class="block text-sm font-semibold">{{ __('admin.dynamic_regions.parent_dynamic_state') }}</label>
                    <select name="parent_state_id" class="w-full border rounded-lg px-3 py-2 dr-opt-state">
                        <option value="">{{ __('admin.dynamic_regions.select_placeholder') }}</option>
                        @foreach($dynamicStates as $st)
                            <option value="{{ $st->id }}" data-cc="{{ $st->country }}" @selected((string)old('parent_state_id') === (string)$st->id)>
                                {{ $st->name_ar ?: $st->name_en ?: $st->name_tr ?: $st->code }} @if($st->country === 'SY')({{ __('admin.dynamic_regions.country_SY') }})@else({{ __('admin.dynamic_regions.country_TR') }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="dr_block_district" class="border rounded-xl p-6 bg-blue-50/50 space-y-4 hidden">
                <h3 class="font-bold text-gray-800">{{ __('admin.dynamic_regions.district_parent_section') }}</h3>
                <div>
                    <label class="block text-sm font-semibold mb-2">{{ __('admin.dynamic_regions.parent_city') }} <span class="text-red-500">*</span></label>
                    <select name="parent_city_id" id="parent_city_id" class="w-full border rounded-lg px-3 py-2 dr-opt-city">
                        <option value="">{{ __('admin.dynamic_regions.select_placeholder') }}</option>
                        @foreach($dynamicCities as $ct)
                            <option value="{{ $ct->id }}" data-cc="{{ $ct->country }}" @selected((string)old('parent_city_id') === (string)$ct->id)>
                                {{ $ct->name_ar ?: $ct->name_en ?: $ct->name_tr ?: $ct->code }} @if($ct->country === 'SY')({{ __('admin.dynamic_regions.country_SY') }})@else({{ __('admin.dynamic_regions.country_TR') }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <details class="border rounded-xl p-4 bg-gray-50/80 open:bg-gray-50">
                <summary class="font-semibold text-gray-800 cursor-pointer select-none py-1">{{ __('admin.dynamic_regions.optional_fields_toggle') }}</summary>
                <div class="space-y-4 pt-4 mt-2 border-t border-gray-200">
                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('admin.dynamic_regions.extra_match_names') }}</label>
                        <textarea name="extra_match_names" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm bg-white" placeholder="{{ __('admin.dynamic_regions.extra_placeholder') }}">{{ old('extra_match_names') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">{{ __('admin.dynamic_regions.latitude') }}</label>
                            <input type="text" name="latitude" value="{{ old('latitude') }}" class="w-full border rounded-lg px-3 py-2 bg-white" placeholder="33.5138">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">{{ __('admin.dynamic_regions.longitude') }}</label>
                            <input type="text" name="longitude" value="{{ old('longitude') }}" class="w-full border rounded-lg px-3 py-2 bg-white" placeholder="36.2765">
                        </div>
                    </div>
                </div>
            </details>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-semibold">{{ __('admin.faqs.save') }}</button>
                <a href="{{ route('admin.dynamic-regions.index') }}" class="px-8 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('admin.faqs.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var typeSel = document.getElementById('dr_type');
    var countrySel = document.getElementById('dr_country');
    var blockCity = document.getElementById('dr_block_city');
    var blockDist = document.getElementById('dr_block_district');
    var anchorWrap = document.getElementById('dr_anchor_wrap');
    var dynStateWrap = document.getElementById('dr_dyn_state_wrap');

    function filterByCountry(selectEl) {
        if (!selectEl || !countrySel) return;
        var cc = countrySel.value;
        var opts = selectEl.querySelectorAll('option[data-cc]');
        for (var i = 0; i < opts.length; i++) {
            var o = opts[i];
            o.hidden = o.getAttribute('data-cc') !== cc;
        }
    }

    function syncAnchorOptgroups() {
        var sel = document.getElementById('anchor_state_code');
        if (!sel || !countrySel) return;
        var cc = countrySel.value;
        var groups = sel.querySelectorAll('optgroup[data-dr-country]');
        for (var g = 0; g < groups.length; g++) {
            var og = groups[g];
            var show = og.getAttribute('data-dr-country') === cc;
            og.style.display = show ? '' : 'none';
            if (!show) {
                var oopts = og.querySelectorAll('option');
                for (var j = 0; j < oopts.length; j++) {
                    if (oopts[j].selected) {
                        sel.value = '';
                        break;
                    }
                }
            }
        }
    }

    function syncCityMode() {
        var mode = document.querySelector('input.dr_city_mode:checked');
        var m = mode ? mode.value : 'anchor_static';
        if (m === 'anchor_static') {
            anchorWrap.classList.remove('hidden');
            dynStateWrap.classList.add('hidden');
        } else {
            anchorWrap.classList.add('hidden');
            dynStateWrap.classList.remove('hidden');
        }
        filterByCountry(document.querySelector('.dr-opt-state'));
    }

    function syncType() {
        var t = typeSel.value;
        blockCity.classList.toggle('hidden', t !== 'city');
        blockDist.classList.toggle('hidden', t !== 'district');
        if (t === 'city') syncCityMode();
        filterByCountry(document.querySelector('.dr-opt-state'));
        filterByCountry(document.querySelector('.dr-opt-city'));
    }

    typeSel.addEventListener('change', syncType);
    countrySel.addEventListener('change', function () {
        syncAnchorOptgroups();
        filterByCountry(document.querySelector('.dr-opt-state'));
        filterByCountry(document.querySelector('.dr-opt-city'));
    });
    document.querySelectorAll('.dr_city_mode').forEach(function (r) {
        r.addEventListener('change', syncCityMode);
    });

    syncType();
    syncAnchorOptgroups();
    filterByCountry(document.querySelector('.dr-opt-state'));
    filterByCountry(document.querySelector('.dr-opt-city'));
})();
</script>
@endsection
