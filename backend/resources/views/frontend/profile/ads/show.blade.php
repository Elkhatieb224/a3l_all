@extends('frontend.layouts.app')

@section('title', $ad->title)

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                        <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        <i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm">
                        <i class="fas fa-info-circle ml-1"></i> {{ session('info') }}
                    </div>
                @endif
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 pb-6 border-b border-gray-200">
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-primary mb-2">{{ $ad->title }}</h1>
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' :
                                       ($ad->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                       ($ad->status === 'rejected' ? 'bg-red-100 text-red-700' :
                                       ($ad->status === 'suspended' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700'))) }}">
                                    {{ __("frontend.profile.my_ads_management.status.{$ad->status}") }}
                                </span>
                                @if($ad->is_featured)
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 inline-flex items-center gap-1">
                                        <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.ads.featured') }}" class="w-3.5 h-3.5">
                                        {{ __('frontend.ads.featured') }}
                                    </span>
                                @endif
                                @if($ad->is_urgent)
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 inline-flex items-center gap-1">
                                        <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.ads.urgent') }}" class="w-3.5 h-3.5">
                                        {{ __('frontend.ads.urgent') }}
                                    </span>
                                @endif
                                <span class="text-sm text-gray-500">UID: {{ $ad->uid }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-4 sm:mt-0">
                            <a href="{{ route('profile.ads.stats', $ad->uid) }}"
                               class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-sm transition">
                                <i class="fas fa-chart-bar ml-2"></i> {{ __('frontend.profile.my_ads_management.stats') }}
                            </a>
                            <a href="{{ route('profile.ads.edit', $ad->uid) }}"
                               class="px-4 py-2 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 rounded-lg text-sm transition">
                                <i class="fas fa-edit ml-2"></i> {{ __('frontend.edit') }}
                            </a>
                            @if($ad->status === 'suspended')
                                <form action="{{ route('profile.ads.unsuspend', $ad->uid) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_unsuspend') }}');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-sm transition">
                                        <i class="fas fa-play ml-2"></i> {{ __('frontend.profile.ads.unsuspend_ad') }}
                                    </button>
                                </form>
                            @elseif(in_array($ad->status, ['active', 'pending']))
                                <form action="{{ route('profile.ads.suspend', $ad->uid) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_suspend') }}');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-orange-50 text-orange-600 hover:bg-orange-100 rounded-lg text-sm transition">
                                        <i class="fas fa-pause ml-2"></i> {{ __('frontend.profile.ads.suspend_ad') }}
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('profile.ads.destroy', $ad->uid) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-sm transition">
                                    <i class="fas fa-trash ml-2"></i> {{ __('frontend.profile.ads.delete_ad') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Images -->
                    @php
                        $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                        $images = $images ?? [];
                    @endphp
                    @if(!empty($images) && is_array($images) && count($images) > 0)
                        <div class="mb-6">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach($images as $image)
                                    @php
                                        $imagePath = is_string($image) ? $image : (is_array($image) ? ($image['path'] ?? $image) : '');
                                    @endphp
                                    @if($imagePath)
                                        <img src="{{ asset('storage/' . $imagePath) }}"
                                             alt="{{ $ad->title }}"
                                             class="w-full h-48 object-cover rounded-lg"
                                             onerror="this.style.display='none';">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Category -->
                        <div class="bg-gray-50 rounded-lg p-4 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">{{ __('frontend.ads.category') }}</h3>
                            <div class="flex flex-wrap items-center gap-x-1 gap-y-2 text-gray-800 text-sm">
                                @include('frontend.partials.ad-category-path-links', [
                                    'ad' => $ad,
                                    'linkClass' => 'text-primary hover:underline font-medium break-words',
                                ])
                            </div>
                        </div>

                        <!-- Views -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">{{ __('frontend.profile.my_ads_management.views') }}</h3>
                            <p class="text-2xl font-bold text-primary">{{ $ad->views_count }}</p>
                        </div>

                        <!-- Published Date -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">{{ __('frontend.profile.my_ads_management.published_at') }}</h3>
                            <p class="text-gray-800">{{ $ad->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-primary mb-3">{{ __('frontend.ads.description') }}</h3>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $ad->description }}</p>
                    </div>

                    <!-- Custom Fields -->
                    @if($ad->custom_fields && count($ad->custom_fields) > 0)
                        @php
                            $categoryFields = \App\Support\CustomFieldsResolver::resolveActiveFields($ad->category, $ad->subcategory);
                            $adCustomFields = $ad->custom_fields;
                            $locale = app()->getLocale();
                        @endphp
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-primary mb-3">{{ __('frontend.profile.my_ads_management.additional_info') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($categoryFields && count($categoryFields) > 0)
                                    @foreach($categoryFields as $field)
                                        @if(isset($adCustomFields[$field['id']]))
                                            @php
                                                $value = $adCustomFields[$field['id']];
                                                $displayValue = $value;
                                                if (is_array($value)) {
                                                    if (!empty($value['address'])) {
                                                        $displayValue = $value['address'];
                                                    } elseif (isset($value['value']) || isset($value['currency']) || !empty($value['tbd'])) {
                                                        if (!empty($value['tbd']) || ($value['value'] ?? null) === null || ($value['value'] ?? '') === '') {
                                                            $displayValue = __('frontend.ads.price_tbd');
                                                        } else {
                                                            $displayValue = format_price($value['value'], 2, $value['currency'] ?? 'SYP');
                                                        }
                                                    } elseif (isset($value['latitude'], $value['longitude'])) {
                                                        $displayValue = ($value['latitude'] ?? '') . ', ' . ($value['longitude'] ?? '');
                                                    } elseif (($field['type'] ?? '') === 'select' && isset($field['options'])) {
                                                        $option = collect($field['options'])->firstWhere('id', $value);
                                                        $displayValue = $option ? ($option[$locale] ?? $option['ar'] ?? $value) : $value;
                                                    } else {
                                                        $displayValue = $value['value'] ?? json_encode($value);
                                                    }
                                                } elseif (($field['type'] ?? '') === 'select' && isset($field['options'])) {
                                                    $option = collect($field['options'])->firstWhere('id', $value);
                                                    $displayValue = $option ? ($option[$locale] ?? $option['ar'] ?? $value) : $value;
                                                }
                                                $displayValue = (string) $displayValue;
                                                $fieldLabel = $field['label'][$locale] ?? $field['label']['ar'] ?? $field['label']['en'] ?? $field['id'];
                                            @endphp
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <h4 class="text-sm font-semibold text-gray-600 mb-1">{{ $fieldLabel }}</h4>
                                                <p class="text-gray-800">{{ $displayValue }}</p>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    @foreach($ad->custom_fields as $key => $value)
                                        @if($value)
                                            @php
                                                $displayValue = $value;
                                                if (is_array($value)) {
                                                    if (!empty($value['address'])) {
                                                        $displayValue = $value['address'];
                                                    } elseif (isset($value['value']) || isset($value['currency']) || !empty($value['tbd'])) {
                                                        if (!empty($value['tbd']) || ($value['value'] ?? null) === null || ($value['value'] ?? '') === '') {
                                                            $displayValue = __('frontend.ads.price_tbd');
                                                        } else {
                                                            $displayValue = format_price($value['value'], 2, $value['currency'] ?? 'SYP');
                                                        }
                                                    } elseif (isset($value['latitude'], $value['longitude'])) {
                                                        $displayValue = ($value['latitude'] ?? '') . ', ' . ($value['longitude'] ?? '');
                                                    } else {
                                                        $displayValue = $value['value'] ?? json_encode($value);
                                                    }
                                                }
                                                $displayValue = (string) $displayValue;
                                                $fieldLabel = __('frontend.ads.custom_field_labels.' . $key);
                                                if ($fieldLabel === 'frontend.ads.custom_field_labels.' . $key) {
                                                    $fieldLabel = $key;
                                                }
                                            @endphp
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <h4 class="text-sm font-semibold text-gray-600 mb-1">{{ $fieldLabel }}</h4>
                                                <p class="text-gray-800">{{ $displayValue }}</p>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Promote Ad: Featured / Urgent (active ads only) -->
                    @if($ad->status === 'active' && ($canAddFeatured || $canRemoveFeatured || $canAddUrgent || $canRemoveUrgent || (!$ad->is_featured && !$canAddFeatured) || (!$ad->is_urgent && !$canAddUrgent)))
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-bold text-primary mb-3">{{ __('frontend.ads.promote_ad') }}</h3>
                            <div class="flex flex-wrap gap-3">
                                @if($canAddFeatured)
                                    <form action="{{ route('profile.ads.set-featured', $ad->uid) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2">
                                            <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_featured') }}" class="w-4 h-4">
                                            {{ __('frontend.profile.ads.add_featured') }}
                                            <span class="text-xs">({{ $remainingFeatured }})</span>
                                        </button>
                                    </form>
                                @elseif(!$ad->is_featured)
                                    <button type="button" disabled class="px-4 py-2 bg-gray-100 text-gray-500 border border-gray-200 rounded-lg text-sm font-semibold inline-flex items-center gap-2 cursor-not-allowed">
                                        <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_featured') }}" class="w-4 h-4">
                                        {{ __('frontend.profile.ads.add_featured') }} — {{ __('frontend.profile.ads.no_promote_quota') }}
                                    </button>
                                @endif
                                @if($canRemoveFeatured)
                                    <form action="{{ route('profile.ads.set-featured', $ad->uid) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_remove_featured') }}');">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2">
                                            <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.ads.remove_featured') }}" class="w-4 h-4">
                                            {{ __('frontend.profile.ads.remove_featured') }}
                                        </button>
                                    </form>
                                @endif
                                @if($canAddUrgent)
                                    <form action="{{ route('profile.ads.set-urgent', $ad->uid) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2">
                                            <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_urgent') }}" class="w-4 h-4">
                                            {{ __('frontend.profile.ads.add_urgent') }}
                                            <span class="text-xs">({{ $remainingUrgent }})</span>
                                        </button>
                                    </form>
                                @elseif(!$ad->is_urgent)
                                    <button type="button" disabled class="px-4 py-2 bg-gray-100 text-gray-500 border border-gray-200 rounded-lg text-sm font-semibold inline-flex items-center gap-2 cursor-not-allowed">
                                        <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_urgent') }}" class="w-4 h-4">
                                        {{ __('frontend.profile.ads.add_urgent') }} — {{ __('frontend.profile.ads.no_promote_quota') }}
                                    </button>
                                @endif
                                @if($canRemoveUrgent)
                                    <form action="{{ route('profile.ads.set-urgent', $ad->uid) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_remove_urgent') }}');">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2">
                                            <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.ads.remove_urgent') }}" class="w-4 h-4">
                                            {{ __('frontend.profile.ads.remove_urgent') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Rejection Reason -->
                    @if($ad->status === 'rejected' && $ad->rejection_reason)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <h3 class="text-lg font-bold text-red-700 mb-2">{{ __('frontend.profile.my_ads_management.rejection_reason') }}</h3>
                            <p class="text-red-600">{{ $ad->rejection_reason }}</p>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-6 border-t border-gray-200">
                        @if($ad->status === 'active')
                            <a href="{{ route('ads.show', $ad->uid) }}"
                               class="px-4 py-2 bg-primary text-white hover:bg-blue-700 rounded-lg text-sm transition">
                                <i class="fas fa-external-link-alt ml-2"></i> {{ __('frontend.profile.my_ads_management.view_public') }}
                            </a>
                        @else
                            <span class="px-4 py-2 bg-gray-200 text-gray-600 rounded-lg text-sm cursor-not-allowed inline-flex items-center"
                                  title="{{ __('frontend.profile.my_ads_management.ad_not_viewable') }}">
                                <i class="fas fa-ban ml-2"></i> {{ __('frontend.profile.my_ads_management.ad_not_viewable') }}
                            </span>
                        @endif
                        <a href="{{ route('profile.ads.index') }}"
                           class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-sm transition">
                            <i class="fas fa-arrow-right ml-2"></i> {{ __('frontend.back') }}
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

