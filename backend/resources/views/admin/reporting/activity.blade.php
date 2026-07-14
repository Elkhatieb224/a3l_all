@extends('admin.layouts.app')

@section('title', __('admin.reporting.activity_report'))
@section('page-title', __('admin.reporting.activity_report'))

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reporting.index') }}" class="text-gray-600 hover:text-primary flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            <span>العودة للتقارير</span>
        </a>
        <a href="{{ route('admin.reporting.activity.export', request()->query()) }}" 
           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
            <i class="fas fa-file-excel"></i>
            {{ __('admin.export_excel') }}
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">إجمالي النشاطات</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['total_actions']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">اليوم</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['today']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">{{ __('admin.reporting.this_week') }}</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['this_week']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-purple-500">
            <p class="text-sm text-gray-600 mb-1">هذا الشهر</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['this_month']) }}</p>
        </div>
    </div>

    <!-- Top Admins & Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Top Admins -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.reporting.top_admins') }}</h3>
            <div class="space-y-3">
                @foreach($topAdmins as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <img src="{{ $item->admin->avatar ? asset('storage/' . $item->admin->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($item->admin->name) }}"
                                 class="w-8 h-8 rounded-full">
                            <span class="font-semibold text-gray-800">{{ $item->admin->name }}</span>
                        </div>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">
                            {{ number_format($item->count) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top Actions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.reporting.top_actions') }}</h3>
            <div class="space-y-3">
                @foreach($topActions as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-700">{{ $item->action }}</span>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-bold">
                            {{ number_format($item->count) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.filter') }}</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <select name="admin_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">كل المديرين</option>
                @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                        {{ $admin->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="action" value="{{ request('action') }}" placeholder="{{ __('admin.search_in_actions') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg md:col-span-4">
                <i class="fas fa-filter ml-2"></i>
                {{ __('admin.filter') }}
            </button>
        </form>
    </div>

    <!-- Activity Logs Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.admin_name') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.action') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.model') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.ip_address') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($log->admin)
                                        <img src="{{ $log->admin->avatar ? asset('storage/' . $log->admin->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($log->admin->name) }}"
                                             class="w-8 h-8 rounded-full">
                                        <span class="text-sm font-semibold">{{ $log->admin->name }}</span>
                                    @else
                                        <span class="text-sm text-gray-500">System</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ $log->model_type ? class_basename($log->model_type) : '-' }}
                                @if($log->model_id)
                                    <span class="text-gray-500">#{{ $log->model_id }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-gray-600">{{ $log->ip_address }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

