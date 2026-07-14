@extends('admin.layouts.app')

@section('title', __('admin.reporting.title'))
@section('page-title', __('admin.reporting.dashboard'))

@section('content')
<div class="space-y-6">
    <!-- Welcome Card -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">{{ __('admin.reporting.title') }}</h2>
                <p class="text-gray-200">تقارير شاملة لمتابعة أداء المنصة</p>
            </div>
            <div class="text-6xl opacity-20">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Users Report -->
        <a href="{{ route('admin.reporting.users') }}" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition border-r-4 border-blue-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 text-blue-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <i class="fas fa-arrow-left text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.reporting.users_report') }}</h3>
            <p class="text-sm text-gray-600">تقرير شامل عن المستخدمين والنشاطات</p>
            <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                <span class="text-gray-600">إجمالي المستخدمين</span>
                <span class="font-bold text-primary">{{ \App\Models\User::count() }}</span>
            </div>
        </a>

        <!-- Ads Report -->
        <a href="{{ route('admin.reporting.ads') }}" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition border-r-4 border-green-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 text-green-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-2xl"></i>
                </div>
                <i class="fas fa-arrow-left text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.reporting.ads_report') }}</h3>
            <p class="text-sm text-gray-600">تقرير الإعلانات والمشاهدات</p>
            <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                <span class="text-gray-600">إجمالي الإعلانات</span>
                <span class="font-bold text-primary">{{ \App\Models\Ad::count() }}</span>
            </div>
        </a>

        <!-- Financial Report -->
        <a href="{{ route('admin.reporting.financial') }}" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition border-r-4 border-yellow-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-yellow-100 text-yellow-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
                <i class="fas fa-arrow-left text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.reporting.financial_report') }}</h3>
            <p class="text-sm text-gray-600">تقرير الإيرادات والمدفوعات</p>
            <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                <span class="text-gray-600">إجمالي الإيرادات</span>
                <span class="font-bold text-primary">{{ number_format(\App\Models\Payment::where('status', 'completed')->sum('amount'), 0) }} ل.س</span>
            </div>
        </a>

        <!-- Reports Report -->
        <a href="{{ route('admin.reporting.reports') }}" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition border-r-4 border-red-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-red-100 text-red-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-flag text-2xl"></i>
                </div>
                <i class="fas fa-arrow-left text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.reporting.reports_report') }}</h3>
            <p class="text-sm text-gray-600">تقرير البلاغات والمخالفات</p>
            <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                <span class="text-gray-600">إجمالي البلاغات</span>
                <span class="font-bold text-primary">{{ \App\Models\Report::count() }}</span>
            </div>
        </a>

        <!-- Activity Report -->
        <a href="{{ route('admin.reporting.activity') }}" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition border-r-4 border-purple-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 text-purple-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-history text-2xl"></i>
                </div>
                <i class="fas fa-arrow-left text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.reporting.activity_report') }}</h3>
            <p class="text-sm text-gray-600">تقرير نشاطات المديرين</p>
            <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                <span class="text-gray-600">إجمالي النشاطات</span>
                <span class="font-bold text-primary">{{ \App\Models\ActivityLog::count() }}</span>
            </div>
        </a>

        <!-- Packages Report -->
        <a href="{{ route('admin.reporting.packages') }}" class="stat-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition border-r-4 border-indigo-500">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-100 text-indigo-600 rounded-full w-16 h-16 flex items-center justify-center">
                    <i class="fas fa-box text-2xl"></i>
                </div>
                <i class="fas fa-arrow-left text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('admin.reporting.packages_report') }}</h3>
            <p class="text-sm text-gray-600">تقرير الباقات والاشتراكات</p>
            <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                <span class="text-gray-600">إجمالي الباقات</span>
                <span class="font-bold text-primary">{{ \App\Models\Package::count() }}</span>
            </div>
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-primary mb-6">إحصائيات سريعة</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ \App\Models\User::whereDate('created_at', now())->count() }}</div>
                <div class="text-sm text-gray-600 mt-1">مستخدمين جدد اليوم</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ \App\Models\Ad::whereDate('created_at', now())->count() }}</div>
                <div class="text-sm text-gray-600 mt-1">إعلانات جديدة اليوم</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ number_format(\App\Models\Payment::where('status', 'completed')->whereDate('created_at', now())->sum('amount'), 0) }}</div>
                <div class="text-sm text-gray-600 mt-1">إيرادات اليوم (ل.س)</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ \App\Models\ActivityLog::whereDate('created_at', now())->count() }}</div>
                <div class="text-sm text-gray-600 mt-1">نشاطات اليوم</div>
            </div>
        </div>
    </div>
</div>
@endsection

