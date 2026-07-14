@extends('frontend.layouts.app')

@section('title', __('frontend.profile.reports'))

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
                            {{ __('frontend.profile.reports') }}
                        </h1>
                        <a href="{{ route('profile.reports.create') }}" 
                           class="btn-primary px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold text-sm sm:text-base">
                            <i class="fas fa-plus ml-2"></i>
                            {{ __('frontend.reports.submit_report') }}
                        </a>
                    </div>

                    <!-- Success Message -->
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-600">
                                <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                            </p>
                        </div>
                    @endif

                    <!-- Reports List -->
                    @if($reports->count() > 0)
                        <div class="space-y-4">
                            @foreach($reports as $report)
                                <div class="border border-gray-200 rounded-lg p-4 sm:p-6 hover:shadow-md transition">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-3">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold
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
                                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">
                                                    {{ __('frontend.reports.type_' . $report->type) }}
                                                </span>
                                            </div>

                                            <h3 class="font-semibold text-gray-800 mb-2">
                                                @if($report->ad)
                                                    {{ __('frontend.reports.report_about_ad') }}: {{ $report->ad->title }}
                                                @elseif($report->reportedUser)
                                                    {{ __('frontend.reports.report_about_user') }}: {{ $report->reportedUser->name }}
                                                @endif
                                            </h3>

                                            <p class="text-sm text-gray-600 mb-2">
                                                {{ __('frontend.reports.reason') }}: {{ Str::limit($report->reason, 150) }}
                                            </p>

                                            <p class="text-xs text-gray-500">
                                                {{ __('frontend.reports.submitted_at') }}: {{ $report->created_at->format('Y-m-d H:i') }}
                                            </p>

                                            @if($report->admin_notes)
                                                <div class="mt-3 p-3 bg-blue-50 border-r-4 border-blue-500 rounded">
                                                    <p class="text-sm font-semibold text-blue-800 mb-1">
                                                        {{ __('frontend.reports.admin_response') }}:
                                                    </p>
                                                    <p class="text-sm text-blue-700">{{ $report->admin_notes }}</p>
                                                    @if($report->reviewed_at)
                                                        <p class="text-xs text-blue-600 mt-2">
                                                            {{ __('frontend.reports.reviewed_at') }}: {{ $report->reviewed_at->format('Y-m-d H:i') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('profile.reports.show', $report->id) }}" 
                                               class="text-primary hover:text-primary-dark p-2 rounded hover:bg-gray-50"
                                               title="{{ __('frontend.view') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $reports->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-4">{{ __('frontend.reports.no_reports') }}</p>
                            <a href="{{ route('profile.reports.create') }}" 
                               class="btn-primary px-6 py-3 rounded-lg font-bold inline-block">
                                <i class="fas fa-plus ml-2"></i>
                                {{ __('frontend.reports.submit_report') }}
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

