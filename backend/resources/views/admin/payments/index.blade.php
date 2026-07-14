@extends('admin.layouts.app')

@section('title', __('admin.payments.title'))
@section('page-title', __('admin.payments.title'))

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">{{ __('admin.payments.stats.total') }}</p>
            <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['total'], 0) }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.payments.stats.currency_hint') }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">{{ __('admin.payments.stats.completed') }}</p>
            <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['completed'], 0) }}</h3>
            <p class="text-xs text-gray-500 mt-1">ل.س (SYP)</p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">{{ __('admin.payments.stats.pending') }}</p>
            <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['pending'], 0) }}</h3>
            <p class="text-xs text-gray-500 mt-1">ل.س (SYP)</p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-r-4 border-red-500">
            <p class="text-sm text-gray-600 mb-1">{{ __('admin.payments.stats.failed') }}</p>
            <h3 class="text-3xl font-bold text-primary">{{ $stats['failed'] }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ __('admin.payments.stats.operations') }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @if(request('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            @endif
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ __('admin.payments.filters.transaction_placeholder') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.payments.filters.all_statuses') }}</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('admin.payments.completed') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.payments.pending') }}</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('admin.payments.failed') }}</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>{{ __('admin.payments.refunded') }}</option>
            </select>

            <input type="date"
                   name="from_date"
                   value="{{ request('from_date') }}"
                   aria-label="{{ __('admin.payments.filters.from_date') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <input type="date"
                   name="to_date"
                   value="{{ request('to_date') }}"
                   aria-label="{{ __('admin.payments.filters.to_date') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.payments.filters.search') }}
            </button>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.transaction') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.package') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.amount') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.method') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.date') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-semibold text-primary">{{ $payment->transaction_id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $payment->user->avatar ? asset('storage/' . $payment->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($payment->user->name) }}"
                                         alt="{{ $payment->user->name }}"
                                         class="w-8 h-8 rounded-full">
                                    <span class="text-sm">{{ $payment->user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ $payment->package->name_ar ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-primary">{{ number_format($payment->amount, 0) }}</span>
                                <span class="text-xs text-gray-500">
                                    @if($payment->currency === 'SYP')
                                        ل.س
                                    @else
                                        {{ $payment->currency }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <i class="fas fa-credit-card text-primary ml-1"></i>
                                {{ $payment->payment_method }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $payment->status === 'completed' ? 'bg-green-100 text-green-700' :
                                       ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                       ($payment->status === 'refunded' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700')) }}">
                                    {{ __('admin.payments.' . $payment->status) ?? $payment->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $payment->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.payments.show', $payment->id) }}"
                                   class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-credit-card text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.payments.table.empty') }}</p>
                            </td>
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

