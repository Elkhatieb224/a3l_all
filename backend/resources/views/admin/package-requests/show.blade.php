@extends('admin.layouts.app')

@section('title', __('admin.package_requests.show_title') . ' #' . $packageRequest->id)
@section('page-title', __('admin.package_requests.show_title') . ' #' . $packageRequest->id)

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
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.package_requests.show_title') }} #{{ $packageRequest->id }}</h2>
            <span class="px-4 py-2 rounded-full text-sm font-semibold
                {{ $packageRequest->status === 'approved' ? 'bg-green-100 text-green-700' :
                   ($packageRequest->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                {{ __('admin.package_requests.status_' . $packageRequest->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.package_requests.user') }}</p>
                @if($packageRequest->user)
                    <a href="{{ route('admin.users.show', $packageRequest->user->id) }}" class="flex items-center gap-3 p-2 -m-2 rounded-lg hover:bg-gray-50 transition group inline-flex">
                        <img src="{{ $packageRequest->user->avatar ? asset('storage/' . $packageRequest->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($packageRequest->user->name) }}"
                             alt="" class="w-10 h-10 rounded-full">
                        <div>
                            <p class="font-semibold text-primary group-hover:underline">{{ $packageRequest->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $packageRequest->user->email }}</p>
                        </div>
                        <i class="fas fa-external-link-alt text-gray-400 text-sm group-hover:text-primary"></i>
                    </a>
                @else
                    <p>—</p>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.package_requests.package') }}</p>
                @if($packageRequest->package)
                    <p class="font-semibold">{{ $packageRequest->package->name_ar ?? $packageRequest->package->name_en ?? $packageRequest->package->name }}</p>
                    <p class="text-sm text-gray-500">{{ number_format($packageRequest->package->price, 0) }} {{ $packageRequest->package->currency }} / {{ $packageRequest->package->duration_days }} {{ __('admin.package_requests.days') }}</p>
                @else
                    <p>—</p>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.package_requests.created_at') }}</p>
                <p class="font-semibold">{{ $packageRequest->created_at->format('Y-m-d H:i') }}</p>
            </div>
            @if($packageRequest->responded_at)
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.package_requests.responded_at') }}</p>
                <p class="font-semibold">{{ $packageRequest->responded_at->format('Y-m-d H:i') }}</p>
                @if($packageRequest->responder)
                    <p class="text-xs text-gray-500">{{ __('admin.package_requests.responded_by') }}: {{ $packageRequest->responder->name }}</p>
                @endif
            </div>
            @endif
        </div>

        {{-- رصيد المستخدم (المحفظة) --}}
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-3">{{ __('admin.package_requests.user_wallet_balance') }}</h3>
            @if(!empty($walletBalances))
                <div class="flex flex-wrap gap-4">
                    @foreach($walletBalances as $currency => $amount)
                        <div class="px-4 py-2 bg-white rounded-lg border border-gray-200">
                            <span class="font-bold text-primary">{{ number_format($amount, 2) }}</span>
                            <span class="text-gray-600">{{ $currency }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">{{ __('admin.package_requests.no_balance') }}</p>
            @endif
        </div>

        @if($packageRequest->admin_response)
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('admin.package_requests.admin_response') }}</p>
            <p class="text-gray-800">{{ $packageRequest->admin_response }}</p>
        </div>
        @endif

        @if($packageRequest->status === 'pending')
        <div class="border-t pt-6 space-y-6">
            <form action="{{ route('admin.package-requests.approve', $packageRequest->id) }}" method="POST" class="p-4 bg-green-50 rounded-lg">
                @csrf
                <p class="text-sm text-gray-700 mb-3">{{ __('admin.package_requests.approve_hint') }}</p>
                <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold">
                    <i class="fas fa-check ml-2"></i>
                    {{ __('admin.package_requests.approve_button') }}
                </button>
            </form>

            <form action="{{ route('admin.package-requests.reject', $packageRequest->id) }}" method="POST" class="p-4 bg-red-50 rounded-lg">
                @csrf
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.package_requests.reject_response_label') }}</label>
                <textarea name="admin_response" rows="3" required maxlength="1000"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg mb-3"
                          placeholder="{{ __('admin.package_requests.reject_response_placeholder') }}"></textarea>
                @error('admin_response')
                    <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
                @enderror
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700">
                    <i class="fas fa-times ml-2"></i>
                    {{ __('admin.package_requests.reject_button') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
