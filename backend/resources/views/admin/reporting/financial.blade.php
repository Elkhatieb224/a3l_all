@extends('admin.layouts.app')

@section('title', __('admin.reporting.financial_report'))
@section('page-title', __('admin.reporting.financial_report'))

@section('content')
<div class="space-y-6">
    <!-- Back Button + Export -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reporting.index') }}" class="text-gray-600 hover:text-primary flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            <span>العودة للتقارير</span>
        </a>
        <a href="{{ route('admin.reporting.financial.export', request()->query()) }}" 
           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
            <i class="fas fa-file-excel"></i>
            {{ __('admin.export_excel') }}
        </a>
    </div>

    <!-- Main Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">إجمالي الإيرادات</p>
            <p class="text-3xl font-bold text-primary">{{ number_format($stats['total_revenue'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.currency_syp') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">مدفوعات مكتملة</p>
            <p class="text-3xl font-bold text-primary">{{ number_format($stats['completed_count']) }}</p>
            <p class="text-xs text-gray-500 mt-1">عملية</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">معلقة</p>
            <p class="text-3xl font-bold text-primary">{{ number_format($stats['pending_amount'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.currency_syp') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-red-500">
            <p class="text-sm text-gray-600 mb-1">فاشلة</p>
            <p class="text-3xl font-bold text-primary">{{ number_format($stats['failed_count']) }}</p>
            <p class="text-xs text-gray-500 mt-1">عملية</p>
        </div>
    </div>

    <!-- Period Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-primary mb-4">
                <i class="fas fa-calendar-day text-secondary ml-2"></i>
                {{ __('admin.reporting.today_revenue') }}
            </h3>
            <p class="text-4xl font-bold text-green-600">{{ number_format($stats['today'], 0) }} ل.س</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-primary mb-4">
                <i class="fas fa-calendar-alt text-secondary ml-2"></i>
                إيرادات هذا الشهر
            </h3>
            <p class="text-4xl font-bold text-blue-600">{{ number_format($stats['this_month'], 0) }} ل.س</p>
        </div>
    </div>

    <!-- Revenue by Package -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.reporting.revenue_by_package') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($packageRevenue as $package)
                <div class="p-4 bg-gradient-to-br from-primary to-blue-900 text-white rounded-lg">
                    <p class="text-sm mb-2">{{ $package->name_ar }}</p>
                    <p class="text-2xl font-bold">{{ number_format($package->revenue ?? 0, 0) }}</p>
                    <p class="text-xs mt-1 opacity-80">{{ __('admin.currency_syp') }}</p>
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
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('admin.completed') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.pending') }}</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('admin.failed') }}</option>
            </select>

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-filter ml-2"></i>
                {{ __('admin.filter') }}
            </button>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.transaction_number') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.package') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.amount') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payment_method') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-semibold text-primary">{{ $payment->transaction_id }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $payment->user->name }}</td>
                            <td class="px-6 py-4 text-sm">{{ $payment->package->name_ar ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-primary">{{ number_format($payment->amount, 0) }}</span>
                                <span class="text-xs text-gray-500">ل.س</span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $payment->payment_method }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $payment->status === 'completed' ? 'bg-green-100 text-green-700' :
                                       ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $payment->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">{{ __('admin.no_payments') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection

