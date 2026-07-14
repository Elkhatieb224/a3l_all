@extends('admin.layouts.app')

@section('title', __('admin.geo_divisions.title'))
@section('page-title', __('admin.geo_divisions.title'))

@section('content')
@php
    $levelLabel = function ($lv) {
        return match ((int) $lv) {
            0 => __('admin.geo_divisions.level_0'),
            1 => __('admin.geo_divisions.level_1'),
            2 => __('admin.geo_divisions.level_2'),
            default => (string) $lv,
        };
    };
    $loc = app()->getLocale();
    $displayName = function ($r) use ($loc) {
        if ($loc === 'ar') {
            return $r->name_ar ?: $r->name_en ?: $r->name_tr ?: $r->code;
        }
        if ($loc === 'tr') {
            return $r->name_tr ?: $r->name_ar ?: $r->name_en ?: $r->code;
        }
        return $r->name_en ?: $r->name_ar ?: $r->name_tr ?: $r->code;
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
                <h2 class="text-xl md:text-2xl font-bold text-primary leading-snug">{{ __('admin.geo_divisions.list_title') }}</h2>
            </div>
            <a href="{{ route('admin.geo-divisions.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-flex items-center gap-2 justify-center shrink-0">
                <i class="fas fa-plus"></i>
                {{ __('admin.geo_divisions.add_new') }}
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
                <div class="text-sm text-gray-600">{{ __('admin.geo_divisions.stat_total') }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-emerald-900">{{ number_format($stats['sy']) }}</div>
                <div class="text-sm text-emerald-800">{{ __('admin.geo_divisions.country_SY') }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3 text-center">
                <div class="text-2xl font-bold text-amber-900">{{ number_format($stats['tr']) }}</div>
                <div class="text-sm text-amber-800">{{ __('admin.geo_divisions.country_TR') }}</div>
            </div>
        </div>

        <form method="get" action="{{ route('admin.geo-divisions.index') }}" class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end mb-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.geo_divisions.filter_country') }}</label>
                <select name="country" class="border rounded-lg px-3 py-2 w-full min-w-[10rem]">
                    <option value="">{{ __('admin.geo_divisions.all') }}</option>
                    <option value="SY" @selected(request('country') === 'SY')>{{ __('admin.geo_divisions.country_SY') }}</option>
                    <option value="TR" @selected(request('country') === 'TR')>{{ __('admin.geo_divisions.country_TR') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.geo_divisions.filter_level') }}</label>
                <select name="level" class="border rounded-lg px-3 py-2 w-full min-w-[10rem]">
                    <option value="">{{ __('admin.geo_divisions.level_all') }}</option>
                    <option value="0" @selected(request('level') === '0')>{{ __('admin.geo_divisions.level_0') }}</option>
                    <option value="1" @selected(request('level') === '1')>{{ __('admin.geo_divisions.level_1') }}</option>
                    <option value="2" @selected(request('level') === '2')>{{ __('admin.geo_divisions.level_2') }}</option>
                </select>
            </div>
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.geo_divisions.search') }}</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('admin.geo_divisions.search_placeholder') }}"
                       class="border rounded-lg px-3 py-2 w-full">
            </div>
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg font-semibold">{{ __('admin.geo_divisions.search') }}</button>
        </form>

        @if($items->isEmpty())
            <p class="text-gray-500 text-center py-12">{{ __('admin.geo_divisions.empty') }}</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_id') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_code') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_level') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_names') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_parent') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_sort') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('admin.geo_divisions.col_coords') }}</th>
                            <th class="px-4 py-3 text-right font-semibold w-48">{{ __('admin.geo_divisions.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($items as $row)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-4 py-3 font-mono text-xs">{{ $row->id }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $row->code }}</td>
                                <td class="px-4 py-3">{{ $levelLabel($row->level) }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $displayName($row) }}</div>
                                    <div class="text-xs text-gray-500 mt-1 line-clamp-2">
                                        {{ $row->name_ar }} @if($row->name_en) · {{ $row->name_en }} @endif @if($row->name_tr) · {{ $row->name_tr }} @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($row->parent)
                                        <span class="text-gray-600">{{ $row->parent->code }}</span>
                                    @else
                                        {{ __('admin.geo_divisions.no_parent') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $row->sort_order }}</td>
                                <td class="px-4 py-3 text-xs font-mono">
                                    @if($row->latitude !== null && $row->longitude !== null)
                                        {{ number_format((float) $row->latitude, 5) }}, {{ number_format((float) $row->longitude, 5) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        @if($row->level < 2)
                                            <a href="{{ route('admin.geo-divisions.create', ['parent_id' => $row->id]) }}"
                                               class="text-xs px-2 py-1 rounded border border-primary text-primary hover:bg-primary hover:text-white transition">
                                                {{ __('admin.geo_divisions.add_child') }}
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.geo-divisions.edit', $row) }}"
                                           class="text-xs px-2 py-1 rounded bg-primary text-white hover:opacity-90">{{ __('admin.geo_divisions.edit') }}</a>
                                        <form action="{{ route('admin.geo-divisions.destroy', $row) }}" method="POST" class="inline"
                                              onsubmit="return confirm(@json(__('admin.geo_divisions.delete_confirm')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs px-2 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50">{{ __('admin.geo_divisions.delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
