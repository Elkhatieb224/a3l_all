@extends('admin.layouts.app')

@section('title', __('admin.ads.show_page.title'))
@section('page-title', __('admin.ads.show_page.title'))

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Ad Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-primary mb-2">{{ $ad->title }}</h2>
                <p class="text-sm text-gray-600 mb-2">
                    <span class="font-semibold">UID:</span> {{ $ad->uid }}
                </p>
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                        {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' :
                           ($ad->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                           ($ad->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')) }}">
                        {{ __('admin.' . $ad->status) ?? $ad->status }}
                    </span>

                    @if($ad->is_featured)
                        <span class="px-3 py-1 bg-gradient-to-r from-yellow-400 to-amber-500 text-white rounded-full text-xs font-bold shadow-sm ring-1 ring-amber-300/70">
                            <i class="fas fa-star"></i> {{ __('admin.ads.badges.featured') }}
                        </span>
                    @endif

                    @if($ad->is_urgent)
                        <span class="px-3 py-1 bg-red-500 text-white rounded-full text-xs font-bold">
                            <i class="fas fa-bolt"></i> {{ __('admin.ads.badges.urgent') }}
                        </span>
                    @endif
                    
                    @if($ad->pending_changes)
                        <span class="px-3 py-1 bg-orange-500 text-white rounded-full text-xs font-bold">
                            <i class="fas fa-edit"></i> {{ __('admin.ads.badges.pending_changes') }}
                        </span>
                    @endif
                </div>

                @php
                    $displayPrice = $ad->price;
                    $displayCurrency = $ad->currency;
                    if (isset($ad->pending_changes['price'])) {
                        $displayPrice = $ad->pending_changes['price'];
                        $displayCurrency = $ad->pending_changes['currency'] ?? $ad->currency;
                    }
                    $displayPriceFormatted = null;
                    if ($displayPrice !== null && $displayPrice !== '') {
                        if (is_array($displayPrice) && isset($displayPrice['value'])) {
                            $displayPriceFormatted = format_price($displayPrice['value'], 2, $displayPrice['currency'] ?? $displayCurrency);
                        } else {
                            $displayPriceFormatted = format_price($displayPrice, 2, $displayCurrency);
                        }
                    }
                    if (!$displayPriceFormatted) $displayPriceFormatted = $ad->display_price;
                @endphp
                @if($displayPriceFormatted)
                    <div class="text-3xl font-bold text-primary mb-4">
                        {{ $displayPriceFormatted }}
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($ad->pending_changes)
                        <form action="{{ route('admin.ads.approve', $ad->uid) }}" method="POST" class="inline admin-action-form">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition disabled:opacity-70 disabled:cursor-not-allowed">
                                <i class="fas fa-check ml-2"></i>
                                {{ __('admin.ads.show_page.approve_changes') }}
                            </button>
                        </form>
                        <button type="button" onclick="document.getElementById('reject-changes-form').classList.toggle('hidden')"
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg transition">
                            <i class="fas fa-times ml-2"></i>
                            {{ __('admin.ads.show_page.reject_changes') }}
                        </button>
                    @elseif($ad->status === 'pending')
                        <form action="{{ route('admin.ads.approve', $ad->uid) }}" method="POST" class="inline admin-action-form">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition disabled:opacity-70 disabled:cursor-not-allowed">
                                <i class="fas fa-check ml-2"></i>
                                {{ __('admin.ads.show_page.approve') }}
                            </button>
                        </form>
                        <button type="button" onclick="document.getElementById('reject-pending-form').classList.toggle('hidden')"
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg transition">
                            <i class="fas fa-times ml-2"></i>
                            {{ __('admin.ads.show_page.reject_ad') }}
                        </button>
                    @elseif(in_array($ad->status, ['active', 'suspended']))
                        <button type="button" onclick="document.getElementById('deactivate-form').classList.toggle('hidden')"
                                class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg transition">
                            <i class="fas fa-ban ml-2"></i>
                            {{ __('admin.ads.show_page.deactivate_ad') }}
                        </button>
                    @endif

                    <form action="{{ route('admin.ads.toggle-featured', $ad->uid) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-yellow-50 text-yellow-600 hover:bg-yellow-100 px-4 py-3 rounded-lg transition">
                        <i class="fas fa-star ml-2"></i>
                        {{ $ad->is_featured ? __('admin.ads.show_page.toggle_featured_off') : __('admin.ads.show_page.toggle_featured_on') }}
                    </button>
                </form>

                @if(auth('admin')->user()->isAdmin())
                <form action="{{ route('admin.ads.destroy', $ad->uid) }}" method="POST" class="inline admin-action-form"
                      onsubmit="return confirm('{{ __('admin.ads.show_page.delete_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg transition disabled:opacity-70 disabled:cursor-not-allowed">
                        <i class="fas fa-trash ml-2"></i>
                        {{ __('admin.ads.show_page.delete_ad') }}
                    </button>
                </form>
                @endif
                </div>

                @if($ad->pending_changes)
                <div id="reject-changes-form" class="hidden w-full p-4 bg-red-50 rounded-lg border border-red-200">
                    <form action="{{ route('admin.ads.reject', $ad->uid) }}" method="POST" class="admin-action-form">
                        @csrf
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.ads.rejection_reason') }}</label>
                        <textarea name="rejection_reason" rows="3" required
                                  placeholder="{{ __('admin.ads.show_page.reject_reason_placeholder') }}"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-2"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                                {{ __('admin.ads.show_page.reject_changes') }}
                            </button>
                            <button type="button" onclick="document.getElementById('reject-changes-form').classList.add('hidden')"
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">
                                {{ __('admin.ads.create.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
                @endif
                @if($ad->status === 'pending' && !$ad->pending_changes)
                <div id="reject-pending-form" class="hidden w-full p-4 bg-red-50 rounded-lg border border-red-200">
                    <form action="{{ route('admin.ads.reject', $ad->uid) }}" method="POST" class="admin-action-form">
                        @csrf
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.ads.rejection_reason') }}</label>
                        <textarea name="rejection_reason" rows="3" required
                                  placeholder="{{ __('admin.ads.show_page.reject_reason_placeholder') }}"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-2"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                                {{ __('admin.ads.show_page.reject_ad') }}
                            </button>
                            <button type="button" onclick="document.getElementById('reject-pending-form').classList.add('hidden')"
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">
                                {{ __('admin.ads.create.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
                @endif
                @if(in_array($ad->status, ['active', 'suspended']))
                <div id="deactivate-form" class="hidden w-full p-4 bg-orange-50 rounded-lg border border-orange-200">
                    <form action="{{ route('admin.ads.reject', $ad->uid) }}" method="POST" class="admin-action-form">
                        @csrf
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.ads.rejection_reason') }}</label>
                        <textarea name="rejection_reason" rows="3" required
                                  placeholder="{{ __('admin.ads.show_page.reject_reason_placeholder') }}"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-2"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm">
                                {{ __('admin.ads.show_page.deactivate_ad') }}
                            </button>
                            <button type="button" onclick="document.getElementById('deactivate-form').classList.add('hidden')"
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">
                                {{ __('admin.ads.create.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Pending Changes -->
    @if($ad->pending_changes)
        <div class="bg-orange-50 border border-orange-200 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-orange-800 mb-4 flex items-center gap-2">
                <i class="fas fa-edit"></i>
                {{ __('admin.ads.show_page.pending_changes_title') }}
            </h3>
            
            <div class="space-y-4">
                @php $pending = $ad->pending_changes; @endphp
                
                @if(isset($pending['title']))
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">{{ __('admin.ads.show_page.current_title') }}:</h4>
                        <p class="text-gray-600 mb-2">{{ $ad->title }}</p>
                        <h4 class="font-semibold text-orange-700 mb-2">{{ __('admin.ads.show_page.pending_title') }}:</h4>
                        <p class="text-orange-600">{{ $pending['title'] }}</p>
                    </div>
                @endif
                
                @if(isset($pending['description']))
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">{{ __('admin.ads.show_page.current_description') }}:</h4>
                        <p class="text-gray-600 mb-2 whitespace-pre-wrap">{{ $ad->description }}</p>
                        <h4 class="font-semibold text-orange-700 mb-2">{{ __('admin.ads.show_page.pending_description') }}:</h4>
                        <p class="text-orange-600 whitespace-pre-wrap">{{ $pending['description'] }}</p>
                    </div>
                @endif
                
                @if(isset($pending['price']))
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">{{ __('admin.ads.show_page.current_price') }}:</h4>
                        <p class="text-gray-600 mb-2">{{ $ad->display_price ?? '-' }}</p>
                        <h4 class="font-semibold text-orange-700 mb-2">{{ __('admin.ads.show_page.pending_price') }}:</h4>
                        <p class="text-orange-600">{{ format_price($pending['price'], 2, $pending['currency'] ?? $ad->currency) }}</p>
                    </div>
                @endif
                
                @if(isset($pending['images']))
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">{{ __('admin.ads.show_page.current_images') }}:</h4>
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            @php
                                $currentImages = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                                $currentImages = $currentImages ?? [];
                            @endphp
                            @foreach($currentImages as $image)
                                @php
                                    $imagePath = is_string($image) ? $image : (is_array($image) ? ($image['path'] ?? $image) : '');
                                @endphp
                                @if($imagePath)
                                    <img src="{{ asset('storage/' . $imagePath) }}" alt="" class="w-full h-20 object-cover rounded">
                                @endif
                            @endforeach
                        </div>
                        <h4 class="font-semibold text-orange-700 mb-2">{{ __('admin.ads.show_page.pending_images') }}:</h4>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($pending['images'] ?? [] as $image)
                                @php
                                    $imagePath = is_string($image) ? $image : (is_array($image) ? ($image['path'] ?? $image) : '');
                                @endphp
                                @if($imagePath)
                                    <img src="{{ asset('storage/' . $imagePath) }}" alt="" class="w-full h-20 object-cover rounded">
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
                
                @if(isset($pending['custom_fields']) && !empty($pending['custom_fields']))
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">{{ __('admin.ads.show_page.custom_fields') }}:</h4>
                        @php
                            $categoryFields = $ad->category->custom_fields ?? [];
                            $currentCustomFields = $ad->custom_fields ?? [];
                            $pendingCustomFields = $pending['custom_fields'] ?? [];
                        @endphp
                        @if($categoryFields && count($categoryFields) > 0)
                            <div class="space-y-3">
                                @foreach($categoryFields as $field)
                                    @php
                                        $fieldId = $field['id'];
                                        $fieldLabel = $field['label'][app()->getLocale()] ?? $field['label']['ar'] ?? $fieldId;
                                        $hasChange = isset($pendingCustomFields[$fieldId]) && 
                                                    isset($currentCustomFields[$fieldId]) && 
                                                    $pendingCustomFields[$fieldId] != $currentCustomFields[$fieldId];
                                        $isNew = isset($pendingCustomFields[$fieldId]) && !isset($currentCustomFields[$fieldId]);
                                        $isRemoved = !isset($pendingCustomFields[$fieldId]) && isset($currentCustomFields[$fieldId]);
                                    @endphp
                                    @if($hasChange || $isNew || $isRemoved)
                                        <div class="bg-white rounded-lg p-4 border border-orange-200">
                                            <h5 class="font-semibold text-gray-700 mb-2">{{ $fieldLabel }}:</h5>
                                            @if($isRemoved)
                                                <div>
                                                    <p class="text-gray-500 text-sm mb-1">{{ __('admin.ads.show_page.current_value') }}:</p>
                                                    <p class="text-gray-600 mb-2">
                                                        @if($field['type'] === 'select')
                                                            @php
                                                                $option = collect($field['options'])->firstWhere('id', $currentCustomFields[$fieldId]);
                                                            @endphp
                                                                {{ $option ? ($option[app()->getLocale()] ?? $option['ar'] ?? $currentCustomFields[$fieldId]) : format_custom_field_display($currentCustomFields[$fieldId]) }}
                                                        @else
                                                            {{ format_custom_field_display($currentCustomFields[$fieldId]) }}
                                                        @endif
                                                    </p>
                                                    <p class="text-orange-600 font-semibold">{{ __('admin.ads.show_page.pending_value') }}: <span class="text-gray-400">{{ __('admin.ads.show_page.removed') }}</span></p>
                                                </div>
                                            @else
                                                <div>
                                                    @if(isset($currentCustomFields[$fieldId]))
                                                        <p class="text-gray-500 text-sm mb-1">{{ __('admin.ads.show_page.current_value') }}:</p>
                                                        <p class="text-gray-600 mb-2">
                                                            @if($field['type'] === 'select')
                                                                @php
                                                                    $option = collect($field['options'])->firstWhere('id', $currentCustomFields[$fieldId]);
                                                                @endphp
                                                                {{ $option ? ($option[app()->getLocale()] ?? $option['ar'] ?? format_custom_field_display($currentCustomFields[$fieldId])) : format_custom_field_display($currentCustomFields[$fieldId]) }}
                                                            @elseif($field['type'] === 'location' && is_array($currentCustomFields[$fieldId]))
                                                                {{ $currentCustomFields[$fieldId]['address'] ?? __('admin.ads.show_page.location') }}
                                                            @else
                                                                {{ format_custom_field_display($currentCustomFields[$fieldId]) }}
                                                            @endif
                                                        </p>
                                                    @endif
                                                    <p class="text-orange-600 font-semibold">{{ __('admin.ads.show_page.pending_value') }}:</p>
                                                    <p class="text-orange-600">
                                                        @if($field['type'] === 'select')
                                                            @php
                                                                $option = collect($field['options'])->firstWhere('id', $pendingCustomFields[$fieldId]);
                                                            @endphp
                                                            {{ $option ? ($option[app()->getLocale()] ?? $option['ar'] ?? format_custom_field_display($pendingCustomFields[$fieldId])) : format_custom_field_display($pendingCustomFields[$fieldId]) }}
                                                        @elseif($field['type'] === 'location' && is_array($pendingCustomFields[$fieldId]))
                                                            {{ $pendingCustomFields[$fieldId]['address'] ?? __('admin.ads.show_page.location') }}
                                                        @else
                                                            {{ format_custom_field_display($pendingCustomFields[$fieldId]) }}
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Ad Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-primary mb-4">{{ __('admin.ads.show_page.description') }}</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $ad->description }}</p>
            </div>

            <!-- Images with Lightbox (zoom + navigation) -->
            @php
                $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                $images = $images ?? [];
                $imagePaths = [];
                foreach ($images as $img) {
                    $p = is_string($img) ? $img : (is_array($img) ? ($img['path'] ?? $img) : '');
                    if ($p) $imagePaths[] = $p;
                }
            @endphp
            @if(!empty($imagePaths))
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-primary mb-4">{{ __('admin.ads.show_page.images') }} ({{ count($imagePaths) }})</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($imagePaths as $index => $imagePath)
                        <div class="relative group cursor-pointer" onclick="openAdminLightbox({{ $index }})">
                            <img src="{{ asset('storage/' . $imagePath) }}" 
                                 alt="{{ __('admin.ads.show_page.images') }}" 
                                 class="w-full h-40 object-cover rounded-lg transition group-hover:opacity-90"
                                 onerror="this.style.display='none';">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/20 rounded-lg transition">
                                <i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- User Info -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.ads.show_page.user') }}</h3>
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ $ad->user->avatar ? asset('storage/' . $ad->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($ad->user->name) }}"
                         alt="{{ $ad->user->name }}"
                         class="w-12 h-12 rounded-full border-2 border-secondary">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $ad->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $ad->user->email }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.users.show', $ad->user->id) }}"
                   class="block text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg transition">
                    <i class="fas fa-user"></i> {{ __('admin.ads.show_page.view_profile') }}
                </a>
            </div>

            <!-- Category Info (full path: main > sub1 > sub2 > ...) -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.ads.show_page.category') }}</h3>
                <div class="space-y-2">
                    @php
                        $categoryPath = [];
                        $categoryPath[] = $ad->category->name_ar ?? $ad->category->getName(app()->getLocale());
                        if ($ad->subcategory) {
                            $subPath = [];
                            $sub = $ad->subcategory;
                            while ($sub) {
                                array_unshift($subPath, $sub->name_ar ?? $sub->getName(app()->getLocale()));
                                $sub = $sub->parent;
                            }
                            $categoryPath = array_merge($categoryPath, $subPath);
                        }
                    @endphp
                    <p class="text-sm text-gray-700 flex flex-wrap items-center gap-1">
                        @foreach($categoryPath as $i => $name)
                            @if($i > 0)<span class="text-gray-400 mx-1">›</span>@endif
                            <span>{{ $name }}</span>
                        @endforeach
                    </p>
                </div>
            </div>

            <!-- Custom Fields (دمج التغييرات المعلقة إن وُجدت ليعكس العرض بعد الموافقة) -->
            @if($ad->category->custom_fields)
                @php
                    $categoryFields = $ad->category->custom_fields ?? [];
                    $adCustomFields = array_merge($ad->custom_fields ?? [], $ad->pending_changes['custom_fields'] ?? []);
                @endphp
                @if($categoryFields && (count($adCustomFields) > 0 || count($ad->custom_fields ?? []) > 0))
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.ads.show_page.custom_fields') }}</h3>
                        <div class="space-y-2">
                            @foreach($categoryFields as $field)
                                @if(isset($adCustomFields[$field['id']]))
                                    <div class="border-b border-gray-100 pb-2">
                                        <span class="text-sm font-semibold text-gray-600">{{ $field['label']['ar'] ?? $field['id'] }}:</span>
                                        <span class="text-sm text-gray-800 mr-2">
                                            @if($field['type'] === 'number' && is_array($adCustomFields[$field['id']]))
                                                @php
                                                    $numData = $adCustomFields[$field['id']];
                                                    $numVal = $numData['value'] ?? null;
                                                    $isTbd = !empty($numData['tbd']) || $numVal === null || $numVal === '';
                                                @endphp
                                                @if($isTbd)
                                                    {{ __('frontend.ads.price_tbd') }}
                                                @else
                                                    {{ format_price($numVal, 2, $numData['currency'] ?? null) }}
                                                @endif
                                            @else
                                                {{ format_custom_field_display($adCustomFields[$field['id']]) }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <!-- Location Info -->
            @if($ad->location_city || $ad->location_district)
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.ads.show_page.location') }}</h3>
                    <div class="space-y-2">
                        @if($ad->location_city)
                            <p class="text-sm">
                                <span class="font-semibold">{{ __('admin.ads.show_page.city') }}:</span>
                                <span class="text-gray-700">{{ $ad->location_city }}</span>
                            </p>
                        @endif
                        @if($ad->location_district)
                            <p class="text-sm">
                                <span class="font-semibold">{{ __('admin.ads.show_page.district') }}:</span>
                                <span class="text-gray-700">{{ $ad->location_district }}</span>
                            </p>
                        @endif
                        @if($ad->location_address)
                            <p class="text-sm">
                                <span class="font-semibold">{{ __('admin.ads.show_page.address') }}:</span>
                                <span class="text-gray-700">{{ $ad->location_address }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Stats -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.ads.show_page.stats') }}</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('admin.ads.show_page.views') }}</span>
                        <span class="font-bold text-primary">{{ $ad->views_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('admin.ads.show_page.published_at') }}</span>
                        <span class="text-sm">{{ $ad->created_at->format('Y-m-d') }}</span>
                    </div>
                    @if($ad->published_at)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('admin.ads.show_page.activated_at') }}</span>
                        <span class="text-sm">{{ $ad->published_at->format('Y-m-d') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(!empty($imagePaths))
@push('scripts')
<script>
const adminAdImages = @json($imagePaths);
const storageBase = '{{ asset("storage/") }}';

function openAdminLightbox(index) {
    let currentIdx = index;
    const lb = document.createElement('div');
    lb.id = 'admin-lightbox';
    lb.className = 'fixed inset-0 bg-black/95 z-[9999] flex flex-col items-center justify-center p-4';
    lb.innerHTML = `
        <button onclick="document.getElementById('admin-lightbox').remove()" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300 z-10 w-12 h-12 flex items-center justify-center">×</button>
        <div class="flex-1 flex items-center justify-center w-full overflow-auto">
            <img id="lb-img" src="${storageBase}/${adminAdImages[currentIdx]}" class="max-w-full max-h-[85vh] object-contain transition-transform duration-200 cursor-zoom-in" 
                 style="transform: scale(1)" onclick="zoomLb(1)">
        </div>
        <div class="flex items-center gap-4 py-4">
            <button onclick="zoomLb(-0.25)" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg"><i class="fas fa-search-minus"></i></button>
            <span id="lb-counter" class="text-white text-sm">${currentIdx + 1} / ${adminAdImages.length}</span>
            <button onclick="zoomLb(0.25)" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg"><i class="fas fa-search-plus"></i></button>
        </div>
        ${adminAdImages.length > 1 ? `
        <button onclick="navLb(-1)" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white p-3 rounded-full text-2xl">‹</button>
        <button onclick="navLb(1)" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white p-3 rounded-full text-2xl">›</button>
        ` : ''}
    `;
    document.body.appendChild(lb);

    window.zoomLb = function(delta) {
        const img = document.getElementById('lb-img');
        const s = parseFloat(img.style.transform.replace('scale(', '').replace(')', '')) || 1;
        const n = Math.max(0.25, Math.min(3, s + delta));
        img.style.transform = 'scale(' + n + ')';
    };
    window.navLb = function(dir) {
        currentIdx += dir;
        if (currentIdx < 0) currentIdx = adminAdImages.length - 1;
        if (currentIdx >= adminAdImages.length) currentIdx = 0;
        const img = document.getElementById('lb-img');
        if (img) {
            img.src = storageBase + '/' + adminAdImages[currentIdx];
            img.style.transform = 'scale(1)';
        }
        const cnt = document.getElementById('lb-counter');
        if (cnt) cnt.textContent = (currentIdx + 1) + ' / ' + adminAdImages.length;
    };
    lb.addEventListener('click', function(e) {
        if (e.target === lb) lb.remove();
    });
    document.addEventListener('keydown', function keyHandler(e) {
        if (!document.getElementById('admin-lightbox')) {
            document.removeEventListener('keydown', keyHandler);
            return;
        }
        if (e.key === 'Escape') document.getElementById('admin-lightbox').remove();
        if (adminAdImages.length > 1) {
            if (e.key === 'ArrowLeft') navLb(-1);
            if (e.key === 'ArrowRight') navLb(1);
        }
    });
}
</script>
@endpush
@endif
@endsection

