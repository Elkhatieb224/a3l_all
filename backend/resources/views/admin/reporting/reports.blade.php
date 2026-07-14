@extends('admin.layouts.app')

@section('title', __('admin.reporting.reports_report'))
@section('page-title', __('admin.reporting.reports_report'))

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reporting.index') }}" class="text-gray-600 hover:text-primary flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            <span>العودة للتقارير</span>
        </a>
        <a href="{{ route('admin.reporting.reports.export', request()->query()) }}" 
           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
            <i class="fas fa-file-excel"></i>
            {{ __('admin.export_excel') }}
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">الإجمالي</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">معلقة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-purple-500">
            <p class="text-sm text-gray-600 mb-1">تمت المراجعة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['reviewed']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">تم الحل</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['resolved']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-red-500">
            <p class="text-sm text-gray-600 mb-1">مرفوضة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['rejected']) }}</p>
        </div>
    </div>

    <!-- Type Breakdown -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.reporting.type_breakdown') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @foreach($typeBreakdown as $item)
                <div class="p-4 bg-gray-50 rounded-lg text-center">
                    <p class="text-sm text-gray-600 mb-1">{{ $item->type }}</p>
                    <p class="text-2xl font-bold text-primary">{{ $item->count }}</p>
                </div>
            @endforeach
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
                <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>{{ __('admin.reviewed') }}</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>{{ __('admin.resolved') }}</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('admin.rejected') }}</option>
            </select>

            <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">كل الأنواع</option>
                <option value="spam" {{ request('type') === 'spam' ? 'selected' : '' }}>Spam</option>
                <option value="fraud" {{ request('type') === 'fraud' ? 'selected' : '' }}>احتيال</option>
                <option value="inappropriate" {{ request('type') === 'inappropriate' ? 'selected' : '' }}>محتوى غير لائق</option>
                <option value="duplicate" {{ request('type') === 'duplicate' ? 'selected' : '' }}>تكرار</option>
            </select>

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg md:col-span-4">
                <i class="fas fa-filter ml-2"></i>
                {{ __('admin.filter') }}
            </button>
        </form>
    </div>

    <!-- Reports List -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.reports.type') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.reports.reported_by') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.reports.ad') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.reports.reason') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reports as $report)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">
                                    {{ $report->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $report->user->name }}</td>
                            <td class="px-6 py-4 text-sm">{{ $report->ad ? Str::limit($report->ad->title, 30) : '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ Str::limit($report->reason, 50) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                       ($report->status === 'reviewed' ? 'bg-blue-100 text-blue-700' :
                                       ($report->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')) }}">
                                    {{ $report->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $report->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_reports') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $reports->links() }}
        </div>
    </div>
</div>
@endsection

