@extends('frontend.layouts.app')

@section('title', __('frontend.packages.request_detail'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ __('frontend.packages.request_detail') }}</h1>
                        <a href="{{ route('profile.package-requests.index') }}" class="text-primary hover:underline text-sm">
                            <i class="fas fa-arrow-right ml-1"></i>
                            {{ __('frontend.packages.back_to_requests') }}
                        </a>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500">{{ __('frontend.packages.package_name') }}</p>
                            <p class="font-semibold">{{ $packageRequest->package ? ($packageRequest->package->getName(app()->getLocale()) ?? $packageRequest->package->name_ar) : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('frontend.packages.request_status') }}</p>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                {{ $packageRequest->status === 'approved' ? 'bg-green-100 text-green-700' :
                                   ($packageRequest->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ __('frontend.packages.request_status_' . $packageRequest->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ __('frontend.packages.request_date') }}</p>
                            <p class="font-semibold">{{ $packageRequest->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        @if($packageRequest->responded_at)
                        <div>
                            <p class="text-sm text-gray-500">{{ __('frontend.packages.responded_at') }}</p>
                            <p class="font-semibold">{{ $packageRequest->responded_at->format('Y-m-d H:i') }}</p>
                        </div>
                        @endif
                    </div>

                    @if($packageRequest->admin_response)
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.packages.admin_response') }}</p>
                        <p class="text-gray-800 whitespace-pre-line">{{ $packageRequest->admin_response }}</p>
                    </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
