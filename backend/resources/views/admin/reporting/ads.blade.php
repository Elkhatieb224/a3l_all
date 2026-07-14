@extends('admin.layouts.app')

@section('title', __('admin.reporting.ads_report'))
@section('page-title', __('admin.reporting.ads_report'))

@section('content')
<div class="space-y-6">
    <!-- Back Button + Export -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reporting.index') }}" class="text-gray-600 hover:text-primary flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            <span>العودة للتقارير</span>
        </a>
        <a href="{{ route('admin.reporting.ads.export', request()->query()) }}" 
           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
            <i class="fas fa-file-excel"></i>
            {{ __('admin.export_excel') }}
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-blue-500">
            <p class="text-xs text-gray-600 mb-1">الإجمالي</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-yellow-500">
            <p class="text-xs text-gray-600 mb-1">معلقة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-green-500">
            <p class="text-xs text-gray-600 mb-1">نشطة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-red-500">
            <p class="text-xs text-gray-600 mb-1">مرفوضة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['rejected']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-gray-500">
            <p class="text-xs text-gray-600 mb-1">منتهية</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['expired']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-purple-500">
            <p class="text-xs text-gray-600 mb-1">مميزة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['featured']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-orange-500">
            <p class="text-xs text-gray-600 mb-1">عاجلة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['urgent']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-indigo-500">
            <p class="text-xs text-gray-600 mb-1">المشاهدات</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['total_views']) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.filter') }}</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.all_statuses') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.pending') }}</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('admin.active') }}</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('admin.rejected') }}</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('admin.expired') }}</option>
            </select>

            <select name="category_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name_ar }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg md:col-span-4">
                <i class="fas fa-filter ml-2"></i>
                {{ __('admin.filter') }}
            </button>
        </form>
    </div>

    <!-- Category Breakdown -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.reporting.category_breakdown') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($categoryBreakdown as $category)
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        @if($category->icon)
                            <img src="{{ asset('storage/' . $category->icon) }}" class="w-6 h-6 object-contain">
                        @endif
                        <p class="font-semibold text-gray-800">{{ $category->name_ar }}</p>
                    </div>
                    <p class="text-2xl font-bold text-primary">{{ $category->ads_count }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Ads Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.ads.title_field') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.ads.category') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.ads.price') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.views') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($ads as $ad)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-gray-800">{{ Str::limit($ad->title, 40) }}</p>
                                    @if($ad->is_featured)
                                        <span class="px-2 py-1 bg-gradient-to-r from-yellow-400 to-amber-500 text-white rounded text-xs font-bold shadow-sm ring-1 ring-amber-300/70">
                                            <i class="fas fa-star ml-1"></i>{{ __('admin.featured') }}
                                        </span>
                                    @endif
                                    @if($ad->is_urgent)
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">{{ __('admin.urgent') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $ad->user->name }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ad->category->name_ar }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-primary">
                                {{ $ad->display_price ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' :
                                       ($ad->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                       ($ad->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')) }}">
                                    {{ $ad->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ number_format($ad->views_count) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $ad->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_ads') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $ads->links() }}
        </div>
    </div>
</div>
@endsection

