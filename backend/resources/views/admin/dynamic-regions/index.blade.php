@extends('admin.layouts.app')

@section('title', __('admin.dynamic_regions.title'))
@section('page-title', __('admin.dynamic_regions.title'))

@section('content')
@php
    $loc = app()->getLocale();
    /** @var array<string, array<string, string>> $stateLabels */
    $stateLabelLookup = \is_array($stateLabels ?? null)
        ? $stateLabels
        : ['SY' => [], 'TR' => []];
    $nameFor = function ($r) use ($loc) {
        if ($loc === 'ar') {
            return $r->name_ar ?: $r->name_en ?: $r->name_tr ?: $r->code;
        }
        if ($loc === 'tr') {
            return $r->name_tr ?: $r->name_ar ?: $r->name_en ?: $r->code;
        }

        return $r->name_en ?: $r->name_ar ?: $r->name_tr ?: $r->code;
    };
    $hierarchyLine = function ($r) use ($stateLabelLookup) {
        if ($r->type === 'state') {
            return __('admin.dynamic_regions.hierarchy_state');
        }
        if ($r->type === 'city') {
            if ($r->anchor_state_code) {
                $gov = $stateLabelLookup[$r->country][$r->anchor_state_code] ?? $r->anchor_state_code;

                return __('admin.dynamic_regions.hierarchy_city_anchor', ['gov' => $gov]);
            }
            if ($r->parent) {
                $p = $r->parent;
                $pn = $p->name_ar ?: $p->name_en ?: $p->name_tr ?: $p->code;

                return __('admin.dynamic_regions.hierarchy_city_dynamic', ['name' => $pn]);
            }
        }
        if ($r->type === 'district' && $r->parent) {
            $p = $r->parent;
            $pn = $p->name_ar ?: $p->name_en ?: $p->name_tr ?: $p->code;

            return __('admin.dynamic_regions.hierarchy_district', ['city' => $pn]);
        }

        return '—';
    };
@endphp
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between mb-4">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-primary leading-snug">{{ __('admin.dynamic_regions.list_title') }}</h2>
                <p class="text-sm text-gray-600 mt-2 max-w-2xl">{{ __('admin.dynamic_regions.list_intro') }}</p>
            </div>
            <a href="{{ route('admin.dynamic-regions.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-flex items-center gap-2 justify-center shrink-0">
                <i class="fas fa-plus"></i>
                {{ __('admin.dynamic_regions.add_new') }}
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
                <div class="text-sm text-gray-600">{{ __('admin.dynamic_regions.stat_total') }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-emerald-900">{{ number_format($stats['sy']) }}</div>
                <div class="text-sm text-emerald-800">{{ __('admin.dynamic_regions.country_SY') }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-amber-900">{{ number_format($stats['tr']) }}</div>
                <div class="text-sm text-amber-800">{{ __('admin.dynamic_regions.country_TR') }}</div>
            </div>
        </div>
        <p class="text-xs text-gray-500 mb-4">{{ __('admin.dynamic_regions.list_filter_note') }}</p>

        <form method="get" action="{{ route('admin.dynamic-regions.index') }}" class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end mb-6">
            @if(request('country'))
                <input type="hidden" name="country" value="{{ request('country') }}">
            @endif
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm font-semibold text-gray-700 w-full sm:w-auto">{{ __('admin.dynamic_regions.filter_country') }}</span>
                <a href="{{ route('admin.dynamic-regions.index', array_filter(['type' => request('type'), 'q' => request('q')])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border {{ request('country') === null || request('country') === '' ? 'bg-primary text-white border-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                    {{ __('admin.dynamic_regions.all') }}
                </a>
                <a href="{{ route('admin.dynamic-regions.index', array_filter(['country' => 'SY', 'type' => request('type'), 'q' => request('q')])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border {{ request('country') === 'SY' ? 'bg-primary text-white border-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                    {{ __('admin.dynamic_regions.country_SY') }}
                </a>
                <a href="{{ route('admin.dynamic-regions.index', array_filter(['country' => 'TR', 'type' => request('type'), 'q' => request('q')])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border {{ request('country') === 'TR' ? 'bg-primary text-white border-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                    {{ __('admin.dynamic_regions.country_TR') }}
                </a>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.dynamic_regions.filter_type') }}</label>
                <select name="type" class="border rounded-lg px-3 py-2 min-w-[200px]" onchange="this.form.submit()">
                    <option value="">{{ __('admin.dynamic_regions.all') }}</option>
                    <option value="state" @selected(request('type') === 'state')>{{ __('admin.dynamic_regions.type_state') }}</option>
                    <option value="city" @selected(request('type') === 'city')>{{ __('admin.dynamic_regions.type_city') }}</option>
                    <option value="district" @selected(request('type') === 'district')>{{ __('admin.dynamic_regions.type_district') }}</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.dynamic_regions.search') }}</label>
                <div class="flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" class="border rounded-lg px-3 py-2 w-full" placeholder="{{ __('admin.dynamic_regions.search_placeholder') }}">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg shrink-0">{{ __('admin.filter') }}</button>
                </div>
            </div>
        </form>

        @if($regions->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($regions as $r)
            <article class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow p-4 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-bold text-gray-900 truncate" title="{{ $nameFor($r) }}">{{ $nameFor($r) }}</h3>
                        <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-full
                            @if($r->type === 'state') bg-violet-100 text-violet-800
                            @elseif($r->type === 'city') bg-sky-100 text-sky-800
                            @else bg-teal-100 text-teal-800 @endif">
                            @if($r->type === 'state') {{ __('admin.dynamic_regions.type_state') }}
                            @elseif($r->type === 'city') {{ __('admin.dynamic_regions.type_city') }}
                            @else {{ __('admin.dynamic_regions.type_district') }} @endif
                        </span>
                    </div>
                    <form action="{{ route('admin.dynamic-regions.destroy', $r->id) }}" method="POST" class="shrink-0"
                          onsubmit="return confirm(@json(__('admin.confirm_delete')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 text-sm font-medium border border-transparent hover:border-red-100" title="{{ __('admin.delete') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $hierarchyLine($r) }}</p>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 border-t border-gray-100 pt-3">
                    <span><span class="font-medium text-gray-700">{{ __('admin.dynamic_regions.col_country') }}:</span> {{ $r->country === 'SY' ? __('admin.dynamic_regions.country_SY') : __('admin.dynamic_regions.country_TR') }}</span>
                    <span><span class="font-medium text-gray-700">{{ __('admin.dynamic_regions.card_ref') }}:</span> <code class="text-[11px] bg-gray-100 px-1 rounded break-all">{{ $r->code }}</code></span>
                    <span><span class="font-medium text-gray-700">{{ __('admin.dynamic_regions.col_uses') }}:</span> {{ $r->use_count }}</span>
                    <span><span class="font-medium text-gray-700">{{ __('admin.dynamic_regions.card_added') }}:</span> {{ $r->created_at?->translatedFormat('Y-m-d H:i') }}</span>
                </div>
                @if($r->name_ar || $r->name_en || $r->name_tr)
                    <div class="text-xs text-gray-500 space-y-0.5 border-t border-dashed border-gray-100 pt-2">
                        @if($r->name_ar)<div><span class="font-medium text-gray-600">AR</span> {{ $r->name_ar }}</div>@endif
                        @if($r->name_en)<div><span class="font-medium text-gray-600">EN</span> {{ $r->name_en }}</div>@endif
                        @if($r->name_tr)<div><span class="font-medium text-gray-600">TR</span> {{ $r->name_tr }}</div>@endif
                    </div>
                @endif
            </article>
            @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-16 text-center text-gray-500">
                {{ __('admin.dynamic_regions.empty') }}
            </div>
        @endif

        <div class="mt-6">{{ $regions->links() }}</div>
    </div>
</div>
@endsection
