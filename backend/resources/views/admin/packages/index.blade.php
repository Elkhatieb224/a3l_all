@extends('admin.layouts.app')

@section('title', __('admin.packages.title'))
@section('page-title', __('admin.packages.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.packages.all_packages') }}</h2>
            <a href="{{ route('admin.packages.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-flex items-center gap-2">
                <i class="fas fa-plus"></i>
                {{ __('admin.packages.add_new') }}
            </a>
        </div>
    </div>

    <!-- Packages Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($packages as $package)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-4
                {{ $package->is_active ? 'border-secondary' : 'border-gray-400' }}
                transform hover:scale-105 transition duration-300">
                <div class="p-6">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-primary mb-2">{{ $package->name_ar }}</h3>
                        <p class="text-sm text-gray-600">{{ $package->name_en }}</p>
                    </div>

                    <!-- Price -->
                    <div class="text-center mb-6 py-4 bg-gradient-to-r from-primary to-blue-900 rounded-lg">
                        <div class="text-4xl font-bold text-secondary">
                            {{ number_format($package->price, 0) }}
                        </div>
                        <div class="text-white text-sm mt-1">
                            @if($package->currency === 'SYP')
                                {{ __('admin.currency_syp') }}
                            @else
                                {{ $package->currency }}
                            @endif
                            / {{ $package->duration_days }} {{ __('admin.days') }}
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span>{{ $package->ads_limit }} {{ __('admin.ads.ad') }}</span>
                        </div>

                        @if($package->featured_ads)
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-star text-secondary"></i>
                                <span>{{ __('admin.packages.featured_ads') }}
                                    @if((int)($package->featured_ads_limit ?? 0) > 0)
                                        ({{ $package->featured_ads_limit }})
                                    @else
                                        ({{ __('admin.packages.unlimited') }})
                                    @endif
                                </span>
                            </div>
                        @endif

                        @if($package->urgent_ads)
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-bolt text-red-500"></i>
                                <span>{{ __('admin.packages.urgent_ads') }}
                                    @if((int)($package->urgent_ads_limit ?? 0) > 0)
                                        ({{ $package->urgent_ads_limit }})
                                    @else
                                        ({{ __('admin.packages.unlimited') }})
                                    @endif
                                </span>
                            </div>
                        @endif

                        @if($package->priority_support)
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-headset text-blue-500"></i>
                                <span>{{ __('admin.packages.priority_support') }}</span>
                            </div>
                        @endif

                        @if($package->homepage_display)
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-home text-purple-500"></i>
                                <span>{{ __('admin.packages.homepage_display') }}</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm pt-3 border-t">
                            <i class="fas fa-users text-gray-500"></i>
                            <span class="font-semibold">{{ $package->subscriptions_count }} اشتراك</span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-4 text-center">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold inline-block
                            {{ $package->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $package->is_active ? __('admin.packages.index.status_active') : __('admin.packages.index.status_inactive') }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.packages.edit', $package->id) }}"
                           class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg transition">
                            <i class="fas fa-edit"></i> {{ __('admin.edit') }}
                        </a>

                        <form action="{{ route('admin.packages.toggle-status', $package->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 py-2 rounded-lg transition">
                                <i class="fas fa-power-off"></i>
                                {{ $package->is_active ? __('admin.disable') : __('admin.enable') }}
                            </button>
                        </form>

                        <form action="{{ route('admin.packages.destroy', $package->id) }}"
                              method="POST"
                              onsubmit="return confirm('{{ __('admin.packages.index.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.packages.index.no_packages') }}</p>
                <a href="{{ route('admin.packages.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-block mt-4">
                    {{ __('admin.packages.index.add_new_button') }}
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection

