@extends('admin.layouts.app')

@section('title', __('admin.hawala.show_title'))
@section('page-title', __('admin.hawala.show_title'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.hawala.show_title') }} #{{ $transfer->id }}</h2>
            <span class="px-4 py-2 rounded-full text-sm font-semibold
                {{ $transfer->status === 'approved' ? 'bg-green-100 text-green-700' :
                   ($transfer->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                {{ __('admin.hawala.' . $transfer->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.user') }}</p>
                <a href="{{ route('admin.users.show', $transfer->user->id) }}" class="flex items-center gap-3 p-2 -m-2 rounded-lg hover:bg-gray-50 transition group">
                    <img src="{{ $transfer->user->avatar ? asset('storage/' . $transfer->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($transfer->user->name) }}"
                         alt="{{ $transfer->user->name }}" class="w-10 h-10 rounded-full">
                    <div>
                        <p class="font-semibold text-primary group-hover:underline">{{ $transfer->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $transfer->user->email }}</p>
                    </div>
                    <i class="fas fa-external-link-alt text-gray-400 text-sm mr-2 group-hover:text-primary"></i>
                </a>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.amount') }}</p>
                <p class="text-xl font-bold">{{ number_format($transfer->amount, 2) }} {{ $transfer->currency }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.receipt_number') }}</p>
                <p class="font-mono font-semibold">{{ $transfer->receipt_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.created_at') }}</p>
                <p class="font-semibold">{{ $transfer->created_at->format('Y-m-d H:i') }}</p>
            </div>
            @if($transfer->admin_credited_amount !== null)
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.admin_credited_amount') }}</p>
                <p class="text-xl font-bold text-green-700">{{ number_format($transfer->admin_credited_amount, 2) }} {{ $transfer->admin_credited_currency }}</p>
            </div>
            @endif
            @if($transfer->approved_at)
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.approved_at') }}</p>
                <p class="font-semibold">{{ $transfer->approved_at->format('Y-m-d H:i') }}</p>
            </div>
            @endif
        </div>

        @if($transfer->receipt_image_path)
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-2">{{ __('admin.hawala.receipt_image') }}</p>
            <a href="{{ asset('storage/' . $transfer->receipt_image_path) }}" target="_blank" class="inline-block">
                <img src="{{ asset('storage/' . $transfer->receipt_image_path) }}" alt="Receipt" class="max-w-md rounded-lg border shadow">
            </a>
        </div>
        @endif

        @if($transfer->note)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('admin.hawala.note') }}</p>
            <p class="text-gray-800 whitespace-pre-line">{{ $transfer->note }}</p>
        </div>
        @endif

        @if($transfer->status === 'pending')
        <div class="border-t pt-6 space-y-6">
            <form action="{{ route('admin.hawala-transfers.approve', $transfer->id) }}" method="POST" class="flex flex-wrap items-end gap-4 p-4 bg-green-50 rounded-lg">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.hawala.admin_credited_amount') }}</label>
                    <input type="number" name="admin_credited_amount" step="0.01" min="0" required
                           placeholder="{{ __('admin.hawala.credited_amount_placeholder') }}"
                           class="px-3 py-2 border rounded-lg w-40">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.hawala.admin_credited_currency') }}</label>
                    <select name="admin_credited_currency" required class="px-3 py-2 border rounded-lg">
                        <option value="SYP">SYP (ل.س)</option>
                        <option value="USD">USD</option>
                        <option value="TRY">TRY</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                    <i class="fas fa-check ml-2"></i>
                    {{ __('admin.hawala.approve') }}
                </button>
            </form>

            <form action="{{ route('admin.hawala-transfers.reject', $transfer->id) }}" method="POST" class="p-4 bg-red-50 rounded-lg">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.hawala.rejection_reason') }}</label>
                <textarea name="rejection_reason" rows="2" class="w-full px-3 py-2 border rounded-lg mb-2"></textarea>
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-times ml-2"></i>
                    {{ __('admin.hawala.reject') }}
                </button>
            </form>
        </div>
        @endif

        @if($transfer->status === 'approved' && !$transfer->subscription_id && $packages->isNotEmpty())
        <div class="border-t pt-6">
            <h3 class="font-bold text-primary mb-3">{{ __('admin.hawala.activate_package') }}</h3>
            <form action="{{ route('admin.hawala-transfers.activate-package', $transfer->id) }}" method="POST" class="flex flex-wrap items-end gap-4 p-4 bg-blue-50 rounded-lg">
                @csrf
                <div class="min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.hawala.select_package') }}</label>
                    <select name="package_id" required class="w-full px-3 py-2 border rounded-lg">
                        @foreach($packages as $p)
                            <option value="{{ $p->id }}">{{ $p->name_ar ?? $p->name }} - {{ format_price($p->price, 2, $p->currency) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                    <i class="fas fa-box-open ml-2"></i>
                    {{ __('admin.hawala.activate') }}
                </button>
            </form>
        </div>
        @endif

        @if($transfer->subscription)
        <div class="border-t pt-6">
            <p class="text-sm text-gray-600">{{ __('admin.payments.table.package') }}: {{ $transfer->package->name_ar ?? $transfer->package->name }}</p>
            <p class="text-sm text-gray-600">{{ __('admin.hawala.approved_at') }}: {{ $transfer->subscription->created_at->format('Y-m-d H:i') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
