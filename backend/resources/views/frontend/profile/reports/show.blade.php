@extends('frontend.layouts.app')

@section('title', __('frontend.reports.report_details'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            <!-- Sidebar -->
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                            {{ __('frontend.reports.report_details') }}
                        </h1>
                        <a href="{{ route('profile.reports.index') }}" 
                           class="text-primary hover:text-primary-dark">
                            <i class="fas fa-arrow-right ml-2"></i>
                            {{ __('frontend.back') }}
                        </a>
                    </div>

                    <!-- Status Badge -->
                    <div class="mb-6">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold
                            {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                               ($report->status === 'reviewed' ? 'bg-blue-100 text-blue-700' : 
                               ($report->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')) }}">
                            @if($report->status === 'pending')
                                {{ __('frontend.reports.status_pending') }}
                            @elseif($report->status === 'reviewed')
                                {{ __('frontend.reports.status_reviewed') }}
                            @elseif($report->status === 'resolved')
                                {{ __('frontend.reports.status_resolved') }}
                            @else
                                {{ __('frontend.reports.status_rejected') }}
                            @endif
                        </span>
                    </div>

                    <!-- Report Info -->
                    <div class="space-y-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">{{ __('frontend.reports.report_type') }}</p>
                                <p class="font-semibold text-gray-800">{{ __('frontend.reports.type_' . $report->type) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">{{ __('frontend.reports.submitted_at') }}</p>
                                <p class="font-semibold text-gray-800">{{ $report->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                        </div>

                        @if($report->ad)
                        <div>
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.reports.report_about_ad') }}</p>
                            <p class="font-semibold text-gray-800">{{ $report->ad->title }}</p>
                            <a href="{{ route('ads.show', $report->ad->uid) }}" 
                               class="text-primary hover:underline text-sm mt-1 inline-block">
                                {{ __('frontend.view_ad') }}
                            </a>
                        </div>
                        @endif

                        @if($report->reportedUser)
                        <div>
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.reports.report_about_user') }}</p>
                            <p class="font-semibold text-gray-800">{{ $report->reportedUser->name }}</p>
                            <p class="text-sm text-gray-500">{{ $report->reportedUser->email }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Reason -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">{{ __('frontend.reports.reason') }}</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700 whitespace-pre-line">{{ $report->reason }}</p>
                        </div>
                    </div>

                    {{-- صور البلاغ (المرفقات) --}}
                    @if($report->images && count($report->images) > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">{{ __('frontend.reports.report_images') }}</h3>
                        <div class="flex flex-wrap gap-4">
                            @foreach($report->images as $path)
                                @php
                                    $url = is_string($path) ? (str_starts_with(trim($path), 'http') ? $path : asset('storage/' . ltrim($path, '/'))) : asset('storage/' . ltrim($path['path'] ?? $path['url'] ?? '', '/'));
                                @endphp
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block border border-gray-200 rounded-lg overflow-hidden hover:border-primary transition shadow-sm hover:shadow-md" title="{{ __('frontend.reports.view_image') }}">
                                    <img src="{{ $url }}" alt="" class="w-48 h-36 object-cover" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Admin Response -->
                    @if($report->admin_notes)
                    <div class="mb-6 p-4 bg-blue-50 border-r-4 border-blue-500 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-comment-dots"></i>
                            {{ __('frontend.reports.admin_response') }}
                        </h3>
                        <p class="text-blue-700 whitespace-pre-line mb-3">{{ $report->admin_notes }}</p>
                        @if($report->reviewer)
                            <p class="text-sm text-blue-600">
                                {{ __('frontend.reports.reviewed_by') }}: {{ $report->reviewer->name }}
                            </p>
                        @endif
                        @if($report->reviewed_at)
                            <p class="text-sm text-blue-600 mt-1">
                                {{ __('frontend.reports.reviewed_at') }}: {{ $report->reviewed_at->format('Y-m-d H:i') }}
                            </p>
                        @endif
                    </div>
                    @else
                    <div class="mb-6 p-4 bg-yellow-50 border-r-4 border-yellow-500 rounded-lg">
                        <p class="text-yellow-800">
                            <i class="fas fa-clock ml-1"></i>
                            {{ __('frontend.reports.pending_review') }}
                        </p>
                    </div>
                    @endif

                    <!-- Back Button -->
                    <div class="flex justify-end">
                        <a href="{{ route('profile.reports.index') }}" 
                           class="btn-primary px-6 py-3 rounded-lg font-bold">
                            <i class="fas fa-arrow-right ml-2"></i>
                            {{ __('frontend.back') }}
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

