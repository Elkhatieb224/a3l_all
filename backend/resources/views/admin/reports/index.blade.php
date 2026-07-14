@extends('admin.layouts.app')

@section('title', __('admin.reports.index.title'))
@section('page-title', __('admin.reports.index.title'))

@section('content')
<div class="space-y-6">
    <!-- Status Tabs -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex items-center gap-2 overflow-x-auto">
            <a href="{{ route('admin.reports.index') }}" 
               class="px-6 py-3 rounded-lg transition whitespace-nowrap
                   {{ !request('status') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-list ml-2"></i>
                {{ __('admin.reports.index.all_count', ['count' => $statusCounts['all']]) }}
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" 
               class="px-6 py-3 rounded-lg transition whitespace-nowrap
                   {{ request('status') === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-clock ml-2"></i>
                {{ __('admin.reports.index.pending_count', ['count' => $statusCounts['pending']]) }}
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'reviewed']) }}" 
               class="px-6 py-3 rounded-lg transition whitespace-nowrap
                   {{ request('status') === 'reviewed' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-eye ml-2"></i>
                {{ __('admin.reports.index.reviewed_count', ['count' => $statusCounts['reviewed']]) }}
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}" 
               class="px-6 py-3 rounded-lg transition whitespace-nowrap
                   {{ request('status') === 'resolved' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-check ml-2"></i>
                {{ __('admin.reports.index.resolved_count', ['count' => $statusCounts['resolved']]) }}
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'rejected']) }}" 
               class="px-6 py-3 rounded-lg transition whitespace-nowrap
                   {{ request('status') === 'rejected' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.reports.index.rejected_count', ['count' => $statusCounts['rejected']]) }}
            </a>
        </div>
    </div>
    
    <!-- Reports List -->
    <div class="space-y-4">
        @forelse($reports as $report)
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                   ($report->status === 'reviewed' ? 'bg-blue-100 text-blue-700' : 
                                   ($report->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')) }}">
                                {{ __("admin.reports.show.status_options.{$report->status}") }}
                            </span>
                            
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">
                                <i class="fas fa-flag ml-1"></i>
                                {{ __('admin.reports.' . ($report->type ?? 'other')) }}
                            </span>
                            
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-clock ml-1"></i>
                                {{ $report->created_at->diffForHumans() }}
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <h3 class="font-bold text-gray-800 mb-2">{{ __('admin.reports.index.reason_label') }}</h3>
                            <p class="text-gray-700">{{ $report->reason }}</p>
                        </div>
                        
                        <div class="flex items-center gap-6 text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-user text-primary"></i>
                                <span class="text-gray-600">{{ __('admin.reports.index.reported_by_label') }}</span>
                                <span class="font-semibold">{{ $report->user->name }}</span>
                            </div>
                            
                            @if($report->ad)
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-bullhorn text-secondary"></i>
                                    <span class="text-gray-600">{{ __('admin.reports.index.ad_label') }}</span>
                                    <span class="font-semibold">{{ Str::limit($report->ad->title, 30) }}</span>
                                </div>
                            @endif
                        </div>
                        
                        @if($report->admin_notes)
                            <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm text-gray-700">
                                    <i class="fas fa-sticky-note text-blue-600 ml-2"></i>
                                    <strong>{{ __('admin.reports.index.admin_notes_label') }}</strong> {{ $report->admin_notes }}
                                </p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.reports.show', $report->id) }}" 
                           class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg transition">
                            <i class="fas fa-eye"></i> {{ __('admin.reports.index.view_button') }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-flag text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.reports.index.no_reports') }}</p>
            </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    @if($reports->hasPages())
        <div class="bg-white rounded-xl shadow-md p-4">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection

