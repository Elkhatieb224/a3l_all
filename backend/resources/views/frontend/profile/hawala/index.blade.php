@extends('frontend.layouts.app')

@section('title', __('frontend.hawala.title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.hawala.title') }}
                    </h1>

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-600"><i class="fas fa-check-circle ml-1"></i> {{ session('success') }}</p>
                        </div>
                    @endif

                    <!-- Balance -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ __('frontend.hawala.balance') }}</h2>
                        @if($balances->isEmpty())
                            <p class="text-gray-600">0 ل.س</p>
                        @else
                            <div class="flex flex-wrap gap-3">
                                @foreach($balances as $currency => $total)
                                    <span class="px-4 py-2 bg-primary/10 text-primary rounded-lg font-bold">{{ number_format($total, 2) }} {{ $currency }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('profile.hawala.create') }}" class="inline-flex items-center gap-2 btn-primary px-4 py-2 rounded-lg mb-6">
                        <i class="fas fa-plus"></i>
                        {{ __('frontend.hawala.submit_transfer') }}
                    </a>

                    <!-- Recent transactions -->
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ __('frontend.hawala.recent_transactions') }}</h2>
                    @if($transactions->isEmpty())
                        <p class="text-gray-500">{{ __('frontend.hawala.no_transactions') }}</p>
                    @else
                        <ul class="divide-y divide-gray-200">
                            @foreach($transactions as $t)
                                <li class="py-3 flex justify-between items-center">
                                    <span class="{{ $t->amount >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                        {{ $t->amount >= 0 ? '+' : '' }}{{ number_format($t->amount, 2) }} {{ $t->currency }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ $t->description ?? '-' }}</span>
                                    <span class="text-xs text-gray-400">{{ $t->created_at->format('Y-m-d H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <!-- Transfer requests -->
                    <h2 class="text-lg font-semibold text-gray-800 mt-8 mb-3">{{ __('frontend.hawala.transfer_requests') }}</h2>
                    @if($transfers->isEmpty())
                        <p class="text-gray-500">{{ __('frontend.hawala.no_transfers') }}</p>
                    @else
                        <ul class="divide-y divide-gray-200">
                            @foreach($transfers as $tr)
                                <li class="py-3 flex justify-between items-center">
                                    <span>{{ number_format($tr->amount, 2) }} {{ $tr->currency }} - {{ $tr->receipt_number }}</span>
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        {{ $tr->status === 'approved' ? 'bg-green-100 text-green-700' :
                                           ($tr->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ __('frontend.hawala.status_' . $tr->status) }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $tr->created_at->format('Y-m-d') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
