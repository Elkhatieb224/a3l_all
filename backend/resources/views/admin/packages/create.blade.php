@extends('admin.layouts.app')

@section('title', __('admin.packages.create_title'))
@section('page-title', __('admin.packages.create_title'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.packages.index') }}" 
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.packages.add_new') }}</h2>
        </div>
    </div>

    <form action="{{ route('admin.packages.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-secondary"></i>
                {{ __('admin.packages.basic_info') }}
            </h3>

            <div class="space-y-4">
                <!-- Names -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.name_arabic') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name_ar" 
                               value="{{ old('name_ar') }}"
                               required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.name_english') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name_en" 
                               value="{{ old('name_en') }}"
                               required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.name_turkish') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name_tr" 
                               value="{{ old('name_tr') }}"
                               required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <!-- Descriptions -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.packages.description_arabic') ?? __('admin.packages.description') }}
                    </label>
                    <textarea name="description_ar" 
                              rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('description_ar') }}</textarea>
                </div>

                <!-- Price & Currency -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.price') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="price" 
                               value="{{ old('price') }}"
                               required 
                               step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.currency') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="currency" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            <option value="SYP" selected>{{ __('admin.currency_syp') }}</option>
                            <option value="TRY">🇹🇷 ₺ (TRY)</option>
                            <option value="USD">🇺🇸 $ (USD)</option>
                            <option value="EUR">🇪🇺 € (EUR)</option>
                        </select>
                    </div>
                </div>

                <!-- Duration & Ads Limit -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.duration_days') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="duration_days" 
                               value="{{ old('duration_days', 30) }}"
                               required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.packages.ads_limit') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="ads_limit" 
                               value="{{ old('ads_limit', 10) }}"
                               required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.packages.order') }}
                    </label>
                    <input type="number" 
                           name="order" 
                           value="{{ old('order', 0) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-star text-secondary"></i>
                {{ __('admin.packages.features') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 bg-gray-50 rounded-lg space-y-2">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" 
                               name="featured_ads" 
                               id="featured_ads"
                               value="1"
                               class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary featured-ads-toggle">
                        <label for="featured_ads" class="text-sm font-semibold text-gray-700">
                            <i class="fas fa-star text-yellow-500 ml-2"></i>
                            {{ __('admin.packages.featured_ads') }}
                        </label>
                    </div>
                    <div class="featured-limit-wrap {{ old('featured_ads') ? '' : 'hidden' }} ml-8">
                        <label class="block text-xs text-gray-600 mb-1">{{ __('admin.packages.featured_ads_limit') }}</label>
                        <input type="number" name="featured_ads_limit" value="{{ old('featured_ads_limit', 0) }}" min="0"
                               class="w-24 px-2 py-1 border border-gray-300 rounded text-sm">
                        <span class="text-xs text-gray-500 mr-1">(0 = {{ __('admin.packages.unlimited') }})</span>
                    </div>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg space-y-2">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" 
                               name="urgent_ads" 
                               id="urgent_ads"
                               value="1"
                               class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary urgent-ads-toggle">
                        <label for="urgent_ads" class="text-sm font-semibold text-gray-700">
                            <i class="fas fa-bolt text-red-500 ml-2"></i>
                            {{ __('admin.packages.urgent_ads') }}
                        </label>
                    </div>
                    <div class="urgent-limit-wrap {{ old('urgent_ads') ? '' : 'hidden' }} ml-8">
                        <label class="block text-xs text-gray-600 mb-1">{{ __('admin.packages.urgent_ads_limit') }}</label>
                        <input type="number" name="urgent_ads_limit" value="{{ old('urgent_ads_limit', 0) }}" min="0"
                               class="w-24 px-2 py-1 border border-gray-300 rounded text-sm">
                        <span class="text-xs text-gray-500 mr-1">(0 = {{ __('admin.packages.unlimited') }})</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" 
                           name="priority_support" 
                           value="1"
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-headset text-blue-500 ml-2"></i>
                        {{ __('admin.packages.priority_support') }}
                    </label>
                </div>

                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" 
                           name="homepage_display" 
                           value="1"
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-home text-purple-500 ml-2"></i>
                        {{ __('admin.packages.homepage_display') }}
                    </label>
                </div>

                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1"
                           checked
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-check-circle text-green-500 ml-2"></i>
                        {{ __('admin.packages.is_active') }}
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.save') }}
            </button>
            
            <a href="{{ route('admin.packages.index') }}" 
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleLimit(checkbox, wrapClass) {
        const wraps = document.querySelectorAll(wrapClass);
        wraps.forEach(function(w) { w.classList.toggle('hidden', !checkbox.checked); });
    }
    const feat = document.getElementById('featured_ads');
    const urg = document.getElementById('urgent_ads');
    if (feat) {
        feat.addEventListener('change', function() { toggleLimit(this, '.featured-limit-wrap'); });
        toggleLimit(feat, '.featured-limit-wrap');
    }
    if (urg) {
        urg.addEventListener('change', function() { toggleLimit(this, '.urgent-limit-wrap'); });
        toggleLimit(urg, '.urgent-limit-wrap');
    }
});
</script>
@endpush
@endsection

