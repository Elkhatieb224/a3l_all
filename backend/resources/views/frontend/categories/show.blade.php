@extends('frontend.layouts.app')

@section('title', $category->getName(app()->getLocale()))

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
    <!-- Breadcrumb -->
    <nav class="mb-4 sm:mb-6 text-xs sm:text-sm overflow-x-auto">
        <ol class="flex items-center gap-1 sm:gap-2 text-gray-600 whitespace-nowrap">
            <li><a href="{{ route('home') }}" class="hover:text-primary">{{ __('frontend.nav.home') }}</a></li>
            <li><i class="fas fa-chevron-left text-xs"></i></li>
            <li><a href="{{ route('categories.index') }}" class="hover:text-primary">{{ __('frontend.categories.title') }}</a></li>
            <li><i class="fas fa-chevron-left text-xs"></i></li>
            <li class="text-gray-800 font-semibold truncate">{{ $category->getName(app()->getLocale()) }}</li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-3 sm:gap-4">
            @if($category->icon)
                <img src="{{ asset('storage/' . $category->icon) }}" 
                     alt="{{ $category->getName(app()->getLocale()) }}"
                     class="w-12 h-12 sm:w-16 sm:h-16 object-contain flex-shrink-0">
            @else
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-primary rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-folder text-secondary text-xl sm:text-2xl"></i>
                </div>
            @endif
            <div class="text-center sm:text-right">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mb-2">
                    {{ $category->getName(app()->getLocale()) }}
                </h1>
                @if($category->getDescription(app()->getLocale()))
                    <p class="text-sm sm:text-base text-gray-600">{{ $category->getDescription(app()->getLocale()) }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Sidebar - Subcategories + Filters -->
        <aside class="lg:col-span-1">
            <div class="space-y-4 sm:space-y-6 sticky top-20">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-secondary ml-2"></i>
                        {{ __('frontend.categories.subcategories') }}
                    </h3>
                    @if($category->subcategories->count() > 0)
                        <ul class="space-y-2">
                            @foreach($category->subcategories as $subcategory)
                                <li>
                                    <a href="{{ route('categories.subcategory', [$category->slug, $subcategory->slug]) }}" 
                                       class="flex items-center justify-between p-2 rounded hover:bg-gray-50 transition">
                                        <span class="flex items-center gap-2 min-w-0">
                                            @if($subcategory->icon)
                                                <img src="{{ asset('storage/' . $subcategory->icon) }}"
                                                     alt="{{ $subcategory->getName(app()->getLocale()) }}"
                                                     class="w-5 h-5 object-contain flex-shrink-0">
                                            @else
                                                <i class="fas fa-folder text-gray-400 text-xs flex-shrink-0"></i>
                                            @endif
                                            <span class="text-gray-700 truncate">{{ $subcategory->getName(app()->getLocale()) }}</span>
                                        </span>
                                        <span class="text-xs text-gray-500">({{ $subcategory->ads_count }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-sm">{{ __('frontend.home.no_ads') }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-filter text-secondary ml-2"></i>
                        {{ __('frontend.filter') }}
                    </h3>
                    <form action="{{ route('categories.show', $category->slug) }}" method="GET" class="space-y-4 text-sm">
                        @php
                            $minPriceValue = request('min_price', !empty($priceFieldId) ? request('cf_'.$priceFieldId.'_min') : null);
                            $maxPriceValue = request('max_price', !empty($priceFieldId) ? request('cf_'.$priceFieldId.'_max') : null);
                        @endphp
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">{{ $priceFilterLabel[app()->getLocale()] ?? ($priceFilterLabel['ar'] ?? __('frontend.price')) }}</label>
                            <div class="flex gap-2">
                                <input type="number"
                                       name="min_price"
                                       value="{{ $minPriceValue }}"
                                       placeholder="{{ __('frontend.at_least') }}"
                                       class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                                <input type="number"
                                       name="max_price"
                                       value="{{ $maxPriceValue }}"
                                       placeholder="{{ __('frontend.at_most') }}"
                                       class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            </div>
                        </div>

                        @if(!empty($filterFields))
                            @foreach($filterFields as $field)
                                @php
                                    $fid = $field['id'] ?? null;
                                    $type = $field['type'] ?? 'text';
                                    $label = $field['label'][app()->getLocale()] ?? ($field['label']['ar'] ?? $fid);
                                @endphp
                                @if($fid && $type === 'number')
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">{{ $label }}</label>
                                        <div class="flex gap-2">
                                            <input type="number"
                                                   name="cf_{{ $fid }}_min"
                                                   value="{{ request('cf_'.$fid.'_min') }}"
                                                   placeholder="{{ __('frontend.at_least') }}"
                                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                                            <input type="number"
                                                   name="cf_{{ $fid }}_max"
                                                   value="{{ request('cf_'.$fid.'_max') }}"
                                                   placeholder="{{ __('frontend.at_most') }}"
                                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                                        </div>
                                    </div>
                                @elseif($fid && $type === 'select')
                                    @php
                                        $options = $field['options'] ?? [];
                                        $current = request('cf_'.$fid);
                                    @endphp
                                    @if(!empty($options))
                                        <div>
                                            <label class="block font-semibold text-gray-700 mb-2">{{ $label }}</label>
                                            <select name="cf_{{ $fid }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                                                <option value="">{{ __('frontend.select_option') }}</option>
                                                @foreach($options as $opt)
                                                    @php
                                                        $val = $opt[app()->getLocale()] ?? ($opt['ar'] ?? ($opt['en'] ?? ($opt['tr'] ?? '')));
                                                        $text = $val;
                                                    @endphp
                                                    <option value="{{ $val }}" {{ $current == $val ? 'selected' : '' }}>
                                                        {{ $text }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                @elseif($fid && $type === 'checkbox')
                                    @php
                                        $checked = request()->has('cf_'.$fid);
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox"
                                               name="cf_{{ $fid }}"
                                               value="1"
                                               id="cf_{{ $fid }}"
                                               class="w-4 h-4 text-secondary border-gray-300 rounded focus:ring-secondary"
                                               {{ $checked ? 'checked' : '' }}>
                                        <label for="cf_{{ $fid }}" class="text-gray-700">{{ $label }}</label>
                                    </div>
                                @elseif($fid && $type === 'date')
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">{{ $label }}</label>
                                        <p class="text-xs text-gray-500 mb-1">{{ __('frontend.filter_expires_after') }}</p>
                                        <input type="date"
                                               name="cf_{{ $fid }}_after"
                                               value="{{ request('cf_'.$fid.'_after') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                                    </div>
                                @elseif($fid && $type === 'car_body_map')
                                    @include('partials.car-body-map-filter', ['field' => $field])
                                @endif
                            @endforeach
                        @endif

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 bg-secondary text-white px-4 py-2 rounded-lg hover:bg-secondary-dark transition">
                                {{ __('frontend.apply_filter') }}
                            </button>
                            <a href="{{ route('categories.show', $category->slug) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                {{ __('frontend.clear') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content - Ads -->
        <main class="lg:col-span-3">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    {{ __('frontend.ads.title') }} ({{ $ads->total() }})
                </h2>
            </div>

            @if($ads->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                    @foreach($ads as $ad)
                        @include('frontend.partials.ad-card', ['ad' => $ad])
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $ads->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">{{ __('frontend.ads.no_ads_found') }}</p>
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

