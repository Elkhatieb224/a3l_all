@extends('admin.layouts.app')

@section('title', __('admin.payments.show_page.title'))
@section('page-title', __('admin.payments.show_page.title'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Payment Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary mb-2">{{ __('admin.payments.show_page.title') }}</h2>
                <p class="text-gray-600 font-mono">{{ $payment->transaction_id }}</p>
            </div>

            <span class="px-4 py-2 rounded-full text-sm font-semibold
                {{ $payment->status === 'completed' ? 'bg-green-100 text-green-700' :
                   ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                   ($payment->status === 'refunded' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700')) }}">
                {{ __('admin.payments.' . $payment->status) ?? $payment->status }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- User -->
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.payments.show_page.user') }}</p>
                <div class="flex items-center gap-3">
                    <img src="{{ $payment->user->avatar ? asset('storage/' . $payment->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($payment->user->name) }}"
                         alt="{{ $payment->user->name }}"
                         class="w-10 h-10 rounded-full">
                    <div>
                        <p class="font-semibold">{{ $payment->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->user->email }}</p>
                    </div>
                </div>
            </div>

            <!-- Package -->
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.payments.show_page.package') }}</p>
                <p class="font-bold text-gray-800">{{ $payment->package->name_ar ?? '-' }}</p>
            </div>

            <!-- Amount -->
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.payments.show_page.amount') }}</p>
                <p class="text-2xl font-bold text-primary">
                    {{ number_format($payment->amount, 0) }}
                    @if($payment->currency === 'SYP')
                        ل.س
                    @else
                        {{ $payment->currency }}
                    @endif
                </p>
            </div>

            <!-- Payment Method -->
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.payments.show_page.method') }}</p>
                <p class="font-semibold">{{ $payment->payment_method }}</p>
            </div>

            <!-- Date -->
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.payments.show_page.date') }}</p>
                <p class="font-semibold">{{ $payment->created_at->format('Y-m-d H:i') }}</p>
            </div>

            @if($payment->paid_at)
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.payments.show_page.confirmed_at') }}</p>
                <p class="font-semibold">{{ $payment->paid_at->format('Y-m-d H:i') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

