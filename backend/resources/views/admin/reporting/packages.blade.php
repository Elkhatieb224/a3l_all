@extends('admin.layouts.app')

@section('title', __('admin.reporting.packages_report'))
@section('page-title', __('admin.reporting.packages_report'))

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reporting.index') }}" class="text-gray-600 hover:text-primary flex items-center gap-2">
            <i class="fas fa-arrow-right"></i>
            <span>العودة للتقارير</span>
        </a>
        <a href="{{ route('admin.reporting.packages.export') }}" 
           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
            <i class="fas fa-file-excel"></i>
            {{ __('admin.export_excel') }}
        </a>
    </div>

    <!-- Overall Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-blue-500">
            <p class="text-sm text-gray-600 mb-1">إجمالي الباقات</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($overallStats['total_packages']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">الباقات النشطة</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($overallStats['active_packages']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-purple-500">
            <p class="text-sm text-gray-600 mb-1">إجمالي الاشتراكات</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($overallStats['total_subscriptions']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 border-r-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">إجمالي الإيرادات</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($overallStats['total_revenue'], 0) }} ل.س</p>
        </div>
    </div>

    <!-- Package Statistics -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-6">{{ __('admin.reporting.package_statistics') }}</h3>

        <div class="space-y-6">
            @foreach($packageStats as $stat)
                <div class="border-r-4 border-secondary bg-gray-50 rounded-lg p-6">
                    <!-- Package Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-xl font-bold text-gray-800">{{ $stat['package']->name_ar }}</h4>
                            <p class="text-sm text-gray-600">{{ $stat['package']->name_en }} | {{ $stat['package']->name_tr }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-primary">{{ number_format($stat['package']->price, 0) }}</p>
                            <p class="text-xs text-gray-600">{{ __('admin.currency_syp') }}</p>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-white p-3 rounded-lg text-center">
                            <p class="text-xs text-gray-600 mb-1">إجمالي الاشتراكات</p>
                            <p class="text-xl font-bold text-blue-600">{{ $stat['total_subscriptions'] }}</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center">
                            <p class="text-xs text-gray-600 mb-1">اشتراكات نشطة</p>
                            <p class="text-xl font-bold text-green-600">{{ $stat['active_subscriptions'] }}</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center">
                            <p class="text-xs text-gray-600 mb-1">اشتراكات منتهية</p>
                            <p class="text-xl font-bold text-gray-600">{{ $stat['expired_subscriptions'] }}</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center">
                            <p class="text-xs text-gray-600 mb-1">إجمالي الإيرادات</p>
                            <p class="text-xl font-bold text-purple-600">{{ number_format($stat['total_revenue'], 0) }}</p>
                            <p class="text-xs text-gray-500">ل.س</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg text-center">
                            <p class="text-xs text-gray-600 mb-1">{{ __('admin.reporting.avg_revenue') }}</p>
                            <p class="text-xl font-bold text-orange-600">{{ number_format($stat['avg_revenue'] ?? 0, 0) }}</p>
                            <p class="text-xs text-gray-500">ل.س</p>
                        </div>
                    </div>

                    <!-- Package Features -->
                    <div class="mt-4 pt-4 border-t flex flex-wrap gap-2">
                        @if($stat['package']->featured_ads)
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                                <i class="fas fa-star"></i> إعلانات مميزة
                            </span>
                        @endif
                        @if($stat['package']->urgent_ads)
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs">
                                <i class="fas fa-bolt"></i> إعلانات عاجلة
                            </span>
                        @endif
                        @if($stat['package']->priority_support)
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                <i class="fas fa-headset"></i> دعم متميز
                            </span>
                        @endif
                        @if($stat['package']->homepage_display)
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
                                <i class="fas fa-home"></i> عرض رئيسي
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Performance Analysis -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-bold text-primary mb-4">تحليل الأداء</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($packageStats as $stat)
                <div class="p-4 bg-gradient-to-br from-primary to-blue-900 text-white rounded-lg">
                    <p class="text-sm mb-2">{{ $stat['package']->name_ar }}</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-bold">{{ $stat['total_subscriptions'] > 0 ? number_format(($stat['total_revenue'] / $stat['total_subscriptions']), 0) : 0 }}</p>
                        <p class="text-xs opacity-80">ل.س/اشتراك</p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-white/20 text-xs">
                        معدل النجاح: {{ $stat['total_subscriptions'] > 0 ? number_format(($stat['active_subscriptions'] / $stat['total_subscriptions']) * 100, 1) : 0 }}%
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

