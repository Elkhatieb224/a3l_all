@extends('admin.layouts.app')

@section('title', 'لوحة التحكم الرئيسية')
@section('page-title', __('admin.dashboard'))

@section('content')
<div class="space-y-6">
    <!-- Welcome Message -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">{{ __('admin.welcome') }}</h2>
                <p class="text-gray-200">{{ __('admin.welcome_user') }}، {{ auth('admin')->user()->name }}</p>
            </div>
            <div class="text-6xl opacity-20">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-r-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.total_users') }}</p>
                    <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['total_users']) }}</h3>
                    <p class="text-xs text-green-600 mt-2">
                        <i class="fas fa-check-circle"></i>
                        {{ number_format($stats['verified_users']) }} {{ __('admin.verified') }}
                    </p>
                </div>
                <div class="bg-blue-100 text-blue-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Ads -->
        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-r-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.total_ads') }}</p>
                    <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['total_ads']) }}</h3>
                    <p class="text-xs text-green-600 mt-2">
                        <i class="fas fa-check"></i>
                        {{ number_format($stats['active_ads']) }} {{ __('admin.active') }}
                    </p>
                </div>
                <div class="bg-green-100 text-green-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Ads -->
        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-r-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.pending_ads') }}</p>
                    <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['pending_ads']) }}</h3>
                    <p class="text-xs text-orange-600 mt-2">
                        <i class="fas fa-clock"></i>
                        {{ __('admin.needs_review') }}
                    </p>
                </div>
                <div class="bg-yellow-100 text-yellow-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-hourglass-half text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="stat-card bg-white rounded-xl shadow-md p-6 border-r-4 border-secondary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.total_revenue') }}</p>
                    <h3 class="text-3xl font-bold text-primary">{{ number_format($stats['total_revenue'], 0) }}</h3>
                    <p class="text-xs text-gray-600 mt-2">
                        {{ __('admin.currency_syp') }}
                    </p>
                </div>
                <div class="bg-yellow-100 text-yellow-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center gap-4">
                <div class="bg-purple-100 text-purple-600 rounded-lg p-4">
                    <i class="fas fa-box text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">{{ __('admin.active_packages') }}</p>
                    <h4 class="text-2xl font-bold text-primary">{{ number_format($stats['total_packages']) }}</h4>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center gap-4">
                <div class="bg-red-100 text-red-600 rounded-lg p-4">
                    <i class="fas fa-flag text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">{{ __('admin.pending_reports') }}</p>
                    <h4 class="text-2xl font-bold text-primary">{{ number_format($stats['pending_reports']) }}</h4>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center gap-4">
                <div class="bg-orange-100 text-orange-600 rounded-lg p-4">
                    <i class="fas fa-credit-card text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">{{ __('admin.pending_payments') }}</p>
                    <h4 class="text-2xl font-bold text-primary">{{ number_format($stats['pending_payments']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Ads -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                <i class="fas fa-bullhorn text-secondary"></i>
                {{ __('admin.recent_ads') }}
            </h3>
            <div class="space-y-3">
                @forelse($recentAds as $ad)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">{{ Str::limit($ad->title, 40) }}</h4>
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-user text-primary"></i> {{ $ad->user->name }}
                                <span class="mx-2">•</span>
                                <i class="fas fa-folder text-secondary"></i> {{ $ad->category->name_ar }}
                            </p>
                        </div>
                        <span class="px-3 py-1 text-xs rounded-full
                            {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' :
                               ($ad->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ $ad->status }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">{{ __('admin.no_recent_ads') }}</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                <i class="fas fa-users text-secondary"></i>
                {{ __('admin.recent_users') }}
            </h3>
            <div class="space-y-3">
                @forelse($recentUsers as $user)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                             alt="{{ $user->name }}"
                             class="w-10 h-10 rounded-full border-2 border-secondary">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">{{ $user->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        <div class="text-left">
                            <p class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                            @if($user->is_verified)
                                <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> {{ __('admin.verified') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">{{ __('admin.no_new_users') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Activity Logs -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <i class="fas fa-history text-secondary"></i>
            {{ __('admin.recent_activities') }}
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr class="text-right">
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">{{ __('admin.admin_name') }}</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">{{ __('admin.action') }}</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-700">{{ __('admin.time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentActivities as $log)
                        @php
                            $actionKey = 'admin.logs.action_labels.' . $log->action;
                            $actionLabel = __($actionKey);
                            if ($actionLabel === $actionKey) {
                                $actionLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', $log->action));
                            }
                            $subjectUrl = $log->subject_url ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user-shield text-primary"></i>
                                    <span class="text-sm">{{ $log->admin->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @if($subjectUrl)
                                    <a href="{{ $subjectUrl }}" class="text-primary hover:underline inline-flex items-center gap-1">
                                        {{ $actionLabel }}
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                @else
                                    {{ $actionLabel }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500">{{ __('admin.no_recent_activities') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

