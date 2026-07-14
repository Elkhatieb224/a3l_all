@extends('admin.layouts.app')

@section('title', __('admin.reporting.users_report'))
@section('page-title', __('admin.reporting.users_report'))

@section('content')
<div class="space-y-6">
    <!-- Back Button + Export -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reporting.index') }}" class="text-gray-600 hover:text-primary flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            <span>العودة للتقارير</span>
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.reporting.users.export', request()->query()) }}" 
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                <i class="fas fa-file-excel"></i>
                {{ __('admin.export_excel') }}
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">إجمالي المستخدمين</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">المستخدمين النشطين</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-purple-500">
            <p class="text-sm text-gray-600 mb-1">الموثقين</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['verified']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">لديهم إعلانات</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['with_ads']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-red-500">
            <p class="text-sm text-gray-600 mb-1">{{ __('admin.reporting.new_this_month') }}</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['new_this_month']) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.filter') }}</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="date"
                   name="from_date"
                   value="{{ request('from_date') }}"
                   placeholder="{{ __('admin.reporting.from_date') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <input type="date"
                   name="to_date"
                   value="{{ request('to_date') }}"
                   placeholder="{{ __('admin.reporting.to_date') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.all_statuses') }}</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('admin.active') }}</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('admin.inactive') }}</option>
            </select>

            <select name="verified" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">الكل</option>
                <option value="yes" {{ request('verified') === 'yes' ? 'selected' : '' }}>{{ __('admin.verified') }}</option>
                <option value="no" {{ request('verified') === 'no' ? 'selected' : '' }}>{{ __('admin.unverified') }}</option>
            </select>

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-filter ml-2"></i>
                {{ __('admin.filter') }}
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.email') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.phone') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.nav.ads') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.join_date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                                         alt="{{ $user->name }}"
                                         class="w-8 h-8 rounded-full">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $user->name }}</p>
                                        @if($user->is_verified)
                                            <span class="text-xs text-green-600">
                                                <i class="fas fa-check-circle"></i> {{ __('admin.verified') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm">{{ $user->phone ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                                    {{ $user->ads_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $user->is_active ? __('admin.active') : __('admin.inactive') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                {{ __('admin.no_users') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

