@extends('admin.layouts.app')

@section('title', __('admin.logs.title'))
@section('page-title', __('admin.logs.title'))

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text"
                   name="action"
                   value="{{ request('action') }}"
                   placeholder="{{ __('admin.logs.search_placeholder') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <input type="date"
                   name="from_date"
                   value="{{ request('from_date') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <input type="date"
                   name="to_date"
                   value="{{ request('to_date') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.search') }}
            </button>
        </form>
    </div>

    <!-- Logs List -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.logs.admin') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.logs.action') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.logs.model') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.logs.ip_address') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.logs.timestamp') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                        @php
                            $actionKey = 'admin.logs.action_labels.' . $log->action;
                            $actionLabel = __($actionKey);
                            if ($actionLabel === $actionKey) {
                                $actionLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', $log->action));
                            }
                            $modelBasename = $log->model_type ? class_basename($log->model_type) : null;
                            $modelKey = $modelBasename ? 'admin.logs.model_labels.' . $modelBasename : null;
                            $modelLabel = $modelKey ? __($modelKey) : null;
                            if ($modelKey && $modelLabel === $modelKey) {
                                $modelLabel = $modelBasename;
                            }
                            $subjectUrl = $log->subject_url;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($log->admin)
                                        <img src="{{ $log->admin->avatar ? asset('storage/' . $log->admin->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($log->admin->name) }}"
                                             alt="{{ $log->admin->name }}"
                                             class="w-8 h-8 rounded-full">
                                        <span class="text-sm font-semibold">{{ $log->admin->name }}</span>
                                    @else
                                        <span class="text-sm text-gray-500">System</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                    {{ $actionLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($modelLabel || $modelBasename)
                                    @if($subjectUrl)
                                        <a href="{{ $subjectUrl }}" class="text-primary hover:underline font-medium inline-flex items-center gap-1">
                                            {{ $modelLabel ?? $modelBasename }}@if($log->model_id) #{{ $log->model_id }}@endif
                                            <i class="fas fa-external-link-alt text-xs"></i>
                                        </a>
                                    @else
                                        <span>{{ $modelLabel ?? $modelBasename }}@if($log->model_id) #{{ $log->model_id }}@endif</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-gray-600">{{ $log->ip_address }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                <div class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.logs.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

