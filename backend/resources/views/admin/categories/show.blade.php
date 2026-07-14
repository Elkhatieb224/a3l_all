@extends('admin.layouts.app')

@section('title', __('admin.categories.details_title'))
@section('page-title', __('admin.categories.details_title'))

@section('content')
@php
    $adminUser = auth('admin')->user();
    $isSuper = $adminUser && $adminUser->isSuperAdmin();
    $isAdminRole = $adminUser && $adminUser->isAdmin();
@endphp
<div class="space-y-6">
    @if($adminUser && $isAdminRole && !$isSuper)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 text-sm flex items-start gap-3">
            <i class="fas fa-info-circle mt-0.5 text-amber-600"></i>
            <div>
                <p class="font-semibold">{{ __('admin.categories.show.limited_role_title') }}</p>
                <p class="mt-1 text-amber-800/90">{{ __('admin.categories.show.limited_role_body') }}</p>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5 text-green-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    <!-- Category Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
            <div class="flex items-center gap-4 min-w-0 flex-1">
                @if($category->icon)
                    <img src="{{ asset('storage/' . $category->icon) }}"
                         alt="{{ $category->getName('ar') }}"
                         class="w-20 h-20 object-contain rounded-xl border-2 border-secondary bg-white p-2 shadow-md">
                @else
                    <div class="w-20 h-20 bg-primary text-white rounded-xl flex items-center justify-center shadow-md">
                        <i class="fas fa-folder text-3xl"></i>
                    </div>
                @endif
                <div>
                    <h2 class="text-2xl font-bold text-primary">{{ $category->getName('ar') }}</h2>
                    <p class="text-gray-600">{{ $category->name_en }} / {{ $category->name_tr }}</p>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold mt-2 inline-block
                        {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $category->is_active ? __('admin.active') : __('admin.inactive') }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-stretch sm:items-center gap-3 shrink-0 w-full sm:w-auto justify-stretch sm:justify-end">
                @if($isAdminRole)
                <a href="{{ route('admin.categories.subcategories.create', $category->id) }}"
                   class="btn-primary px-6 py-3 rounded-lg inline-flex items-center justify-center gap-2 flex-1 sm:flex-initial min-h-[44px]">
                    <i class="fas fa-plus"></i>
                    {{ __('admin.categories.add_subcategory') }}
                </a>
                @endif

                @if($isAdminRole)
                <a href="{{ route('admin.categories.edit', $category->id) }}#category-ad-images-settings"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg inline-flex items-center justify-center gap-2 transition flex-1 sm:flex-initial min-h-[44px]">
                    <i class="fas fa-edit"></i>
                    {{ __('admin.categories.edit') }}
                </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.categories.show.subcategories_label') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $category->subcategories->count() }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.categories.show.ads_label') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $category->ads_count }}</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.categories.show.order_short') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $category->order }}</p>
            </div>
        </div>

        @php
            $adMode = $category->ad_images_mode ?? \App\Support\AdImagesConfig::MODE_USER_UPLOAD;
            if (!in_array($adMode, [\App\Support\AdImagesConfig::MODE_USER_UPLOAD, \App\Support\AdImagesConfig::MODE_ADMIN_GALLERY], true)) {
                $adMode = \App\Support\AdImagesConfig::MODE_USER_UPLOAD;
            }
            $adModeLabel = $adMode === \App\Support\AdImagesConfig::MODE_ADMIN_GALLERY
                ? __('admin.categories.ad_images_admin_gallery')
                : __('admin.categories.ad_images_user_upload');
            $galleryPaths = is_array($category->ad_gallery_images) ? array_filter($category->ad_gallery_images, fn ($p) => is_string($p) && $p !== '') : [];
        @endphp
        <div id="category-ad-images-summary" class="mt-6 rounded-xl border border-gray-200 bg-gray-50/80 p-4 md:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-secondary/10 text-secondary">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800">{{ __('admin.categories.show.ad_images_summary_title') }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ __('admin.categories.ad_images_help') }}</p>
                        <p class="mt-2 text-sm">
                            <span class="font-medium text-gray-700">{{ __('admin.categories.ad_images_mode_label') }}:</span>
                            <span class="text-primary font-semibold">{{ $adModeLabel }}</span>
                        </p>
                        @if($adMode === \App\Support\AdImagesConfig::MODE_ADMIN_GALLERY)
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.categories.show.ad_images_gallery_count', ['count' => count($galleryPaths)]) }}</p>
                        @endif
                    </div>
                </div>
                @if($isAdminRole)
                    <a href="{{ route('admin.categories.edit', $category->id) }}#category-ad-images-settings"
                       class="shrink-0 inline-flex items-center justify-center gap-2 rounded-lg border border-secondary bg-white px-4 py-2.5 text-sm font-semibold text-secondary hover:bg-secondary hover:text-white transition">
                        <i class="fas fa-sliders-h"></i>
                        {{ __('admin.categories.show.ad_images_change') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Custom Fields -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="min-w-0">
                <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                    <i class="fas fa-list text-secondary shrink-0"></i>
                    {{ __('admin.categories.show.custom_fields_title') }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('admin.categories.custom_fields.category_fallback_hint') }}</p>
            </div>
            @if($isSuper)
            <button type="button" onclick="showAddFieldModal()" class="btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2 shrink-0">
                <i class="fas fa-plus"></i>
                {{ __('admin.categories.show.add_field') }}
            </button>
            @endif
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($category->custom_fields && count($category->custom_fields) > 0)
            <div class="space-y-4">
                @foreach($category->custom_fields ?? [] as $index => $field)
                    <div class="border {{ ($field['is_active'] ?? true) ? 'border-gray-200' : 'border-gray-100 bg-gray-50 opacity-60' }} rounded-lg p-4 hover:border-secondary transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 bg-primary text-white rounded-lg text-xs font-semibold">
                                        {{ $field['type'] }}
                                    </span>
                                    @if($field['required'] ?? false)
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                            <i class="fas fa-star text-xs"></i> {{ __('admin.categories.show.required_badge') }}
                                        </span>
                                    @endif
                                    @if(!($field['is_active'] ?? true))
                                        <span class="px-2 py-1 bg-gray-200 text-gray-600 rounded text-xs font-semibold">
                                            <i class="fas fa-ban text-xs"></i> {{ __('admin.categories.show.inactive_badge') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">
                                            <i class="fas fa-check text-xs"></i> {{ __('admin.categories.show.active_badge') }}
                                        </span>
                                    @endif
                                    @if(($field['type'] ?? '') === 'number' && !empty($field['show_currency']))
                                        <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded text-xs font-semibold">
                                            <i class="fas fa-coins text-xs"></i> {{ __('admin.categories.show.currency_badge') }}
                                        </span>
                                    @endif
                                </div>

                                <h4 class="font-bold text-gray-800 mb-1">{{ $field['label']['ar'] ?? $field['id'] }}</h4>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">EN:</span> {{ $field['label']['en'] ?? '-' }} |
                                    <span class="font-semibold">TR:</span> {{ $field['label']['tr'] ?? '-' }}
                                </p>

                                @if(isset($field['options']) && count($field['options']) > 0)
                                    <div class="mt-3">
                                        <p class="text-xs text-gray-500 mb-2">{{ __('admin.categories.show.options_label') }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($field['options'] as $option)
                                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">
                                                    {{ $option['ar'] ?? $option['en'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 font-mono">{{ __('admin.categories.show.id_label') }} {{ $field['id'] }}</span>
                                @if($isSuper)
                                <button type="button" onclick="showEditFieldModal({{ $index }})" 
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50" 
                                        title="{{ __('admin.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.categories.custom-fields.toggle-status', [$category->id, $index]) }}" 
                                      method="POST" 
                                      class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-{{ ($field['is_active'] ?? true) ? 'yellow' : 'green' }}-600 hover:text-{{ ($field['is_active'] ?? true) ? 'yellow' : 'green' }}-800 p-2 rounded hover:bg-{{ ($field['is_active'] ?? true) ? 'yellow' : 'green' }}-50" 
                                            title="{{ ($field['is_active'] ?? true) ? __('admin.categories.show.toggle_disable') : __('admin.categories.show.toggle_enable') }}">
                                        <i class="fas fa-{{ ($field['is_active'] ?? true) ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.categories.custom-fields.delete', [$category->id, $index]) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50" 
                                            title="{{ __('admin.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-list text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.categories.show.no_custom_fields') }}</p>
                <p class="text-gray-400 text-sm mt-2">{{ __('admin.categories.show.add_custom_fields_hint') }}</p>
            </div>
        @endif

        @if($isSuper)
            @include('admin.partials.custom-fields-import-form', [
                'importRoute' => route('admin.categories.custom-fields.import-json', $category->id),
                'inputId' => 'category_custom_fields_import_file',
            ])
        @endif
    </div>

    <!-- Subcategories -->
    @if($category->subcategories->count() > 0)
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                <i class="fas fa-sitemap text-secondary"></i>
                {{ __('admin.categories.show.subcategories_title', ['count' => $category->subcategories->count()]) }}
            </h3>
        </div>

        <!-- Search -->
        <div class="mb-6">
            <form method="GET" action="{{ route('admin.categories.show', $category->id) }}" class="flex gap-3">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="{{ __('admin.categories.show.search_subcategories') }}"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                    <i class="fas fa-search ml-2"></i>
                    {{ __('admin.search') }}
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.categories.show', $category->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg">
                        <i class="fas fa-times ml-2"></i>
                        {{ __('admin.clear') }}
                    </a>
                @endif
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($category->subcategories as $subcategory)
                <div class="border rounded-lg p-4 hover:shadow-md transition {{ $subcategory->trashed() ? 'border-gray-300 bg-gray-50 opacity-75' : 'border-gray-200' }}">
                    @if($subcategory->trashed())
                        <div class="mb-2 flex items-center gap-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-semibold">
                            <i class="fas fa-trash"></i>
                            <span>{{ __('admin.categories.show.deleted_badge') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            @if($subcategory->icon)
                                <img src="{{ asset('storage/' . $subcategory->icon) }}"
                                     alt="{{ $subcategory->getName('ar') }}"
                                     class="w-8 h-8 object-contain rounded border border-gray-200 bg-white p-1">
                            @endif
                            <h4 class="font-bold {{ $subcategory->trashed() ? 'text-gray-500' : 'text-gray-800' }}">{{ $subcategory->getName('ar') }}</h4>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $subcategory->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $subcategory->is_active ? __('admin.active') : __('admin.inactive') }}
                        </span>
                    </div>
                    <p class="text-sm {{ $subcategory->trashed() ? 'text-gray-400' : 'text-gray-600' }} mb-3">{{ $subcategory->name_en }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span>
                            <i class="fas fa-bullhorn"></i>
                            {{ __('admin.categories.show.ads_count_label', ['count' => $subcategory->ads_count ?? 0]) }}
                        </span>
                        <span>{{ __('admin.categories.show.order_label', ['order' => $subcategory->order]) }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t">
                        @if($subcategory->trashed())
                            <form action="{{ route('admin.subcategories.restore', $subcategory->id) }}"
                                  method="POST"
                                  class="flex-1">
                                @csrf
                                @method('POST')
                                <button type="submit"
                                        class="w-full text-center bg-green-50 text-green-600 hover:bg-green-100 py-2 rounded-lg transition text-sm">
                                    <i class="fas fa-undo"></i> {{ __('admin.categories.show.restore') }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('admin.subcategories.show', $subcategory->id) }}"
                               class="flex-1 text-center bg-primary text-white hover:bg-primary-dark py-2 rounded-lg transition text-sm">
                                <i class="fas fa-eye"></i> {{ __('admin.view') }}
                            </a>
                            <a href="{{ route('admin.subcategories.edit', $subcategory->id) }}"
                               class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-edit"></i> {{ __('admin.edit') }}
                            </a>
                        @endif

                        @if(!$subcategory->trashed())
                            <form action="{{ route('admin.subcategories.destroy', $subcategory->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.subcategories.force-delete', $subcategory->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('{{ __('admin.categories.show.force_delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-2 rounded-lg transition text-sm"
                                        title="{{ __('admin.categories.show.force_delete') }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Ads -->
    <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
            <i class="fas fa-bullhorn text-secondary"></i>
            {{ __('admin.categories.show.recent_ads_title') }}
        </h3>

        @if($category->ads->count() > 0)
            <div class="space-y-3">
                @foreach($category->ads->take(5) as $ad)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">{{ Str::limit($ad->title, 50) }}</h4>
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-user"></i> {{ $ad->user->name }}
                                <span class="mx-2">•</span>
                                <i class="fas fa-clock"></i> {{ $ad->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' :
                                   ($ad->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ $ad->status }}
                            </span>
                            <a href="{{ route('admin.ads.show', $ad->uid) }}"
                               class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">{{ __('admin.categories.show.no_ads') }}</p>
        @endif
    </div>
</div>

<!-- Add Field Modal -->
<div id="addFieldModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">{{ __('admin.categories.show.modal.add_title') }}</h3>
            <button onclick="hideAddFieldModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addFieldForm" action="{{ route('admin.categories.custom-fields.store', $category->id) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.field_id') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary" placeholder="brand">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.field_type') }} <span class="text-red-500">*</span></label>
                        <select name="type" id="fieldType" required onchange="toggleFieldOptions()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="text">{{ __('admin.categories.show.modal.type_text') }}</option>
                            <option value="textarea">{{ __('admin.categories.show.modal.type_textarea') }}</option>
                            <option value="number">{{ __('admin.categories.show.modal.type_number') }}</option>
                            <option value="select">{{ __('admin.categories.show.modal.type_select') }}</option>
                            <option value="checkbox">{{ __('admin.categories.show.modal.type_checkbox') }}</option>
                            <option value="location">{{ __('admin.categories.show.modal.type_location') }}</option>
                            <option value="date">{{ __('admin.categories.show.modal.type_date') }}</option>
                            <option value="car_body_map">{{ __('admin.categories.show.modal.type_car_body_map') }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.label_ar') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="label_ar" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.label_en') }}</label>
                        <input type="text" name="label_en" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.label_tr') }}</label>
                        <input type="text" name="label_tr" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="required" id="required" value="1" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="required" class="text-sm text-gray-700">{{ __('admin.categories.show.modal.required_label') }}</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="is_active" class="text-sm text-gray-700">{{ __('admin.categories.show.modal.active_label') }}</label>
                    </div>
                </div>
                <div id="numberOptions" class="hidden space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.min') }}</label>
                            <input type="number" name="min" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.max') }}</label>
                            <input type="number" name="max" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.step') }}</label>
                            <input type="number" name="step" value="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="show_currency" id="show_currency" value="1" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="show_currency" class="text-sm text-gray-700">{{ __('admin.categories.show.modal.show_currency') }}</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="allow_tbd" id="allow_tbd" value="1" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="allow_tbd" class="text-sm text-gray-700">{{ __('admin.categories.show.modal.allow_tbd') }}</label>
                    </div>
                </div>
                <div id="selectOptions" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.categories.show.modal.options') }}</label>
                    <div id="optionsContainer" class="space-y-2">
                        <div class="flex gap-2">
                            <input type="text" name="options[0][ar]" placeholder="{{ __('admin.categories.show.modal.option_placeholder_ar') }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <input type="text" name="options[0][en]" placeholder="{{ __('admin.categories.show.modal.option_placeholder_en') }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <input type="text" name="options[0][tr]" placeholder="{{ __('admin.categories.show.modal.option_placeholder_tr') }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <button type="button" onclick="removeOption(this)" class="px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" onclick="addOption()" class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                        <i class="fas fa-plus ml-1"></i> {{ __('admin.categories.show.modal.add_option') }}
                    </button>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="hideAddFieldModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">{{ __('admin.categories.show.modal.cancel') }}</button>
                <button type="submit" class="btn-primary px-4 py-2 rounded-md">{{ __('admin.categories.show.modal.add') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Field Modal -->
<div id="editFieldModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">{{ __('admin.categories.show.edit_field') }}</h3>
            <button onclick="hideEditFieldModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editFieldForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4" id="editFieldContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="hideEditFieldModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">{{ __('admin.categories.show.modal.cancel') }}</button>
                <button type="submit" class="btn-primary px-4 py-2 rounded-md">{{ __('admin.categories.show.modal.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
const categoryFields = @json($category->custom_fields ?? []);
const fieldT = {
    addTitle: @json(__('admin.categories.show.modal.add_title')),
    editTitle: @json(__('admin.categories.show.modal.edit_title')),
    fieldId: @json(__('admin.categories.show.modal.field_id')),
    fieldType: @json(__('admin.categories.show.modal.field_type')),
    typeText: @json(__('admin.categories.show.modal.type_text')),
    typeTextarea: @json(__('admin.categories.show.modal.type_textarea')),
    typeNumber: @json(__('admin.categories.show.modal.type_number')),
    typeSelect: @json(__('admin.categories.show.modal.type_select')),
    typeCheckbox: @json(__('admin.categories.show.modal.type_checkbox')),
    typeLocation: @json(__('admin.categories.show.modal.type_location')),
    typeDate: @json(__('admin.categories.show.modal.type_date')),
    typeCarBodyMap: @json(__('admin.categories.show.modal.type_car_body_map')),
    labelAr: @json(__('admin.categories.show.modal.label_ar')),
    labelEn: @json(__('admin.categories.show.modal.label_en')),
    labelTr: @json(__('admin.categories.show.modal.label_tr')),
    required: @json(__('admin.categories.show.modal.required_label')),
    active: @json(__('admin.categories.show.modal.active_label')),
    min: @json(__('admin.categories.show.modal.min')),
    max: @json(__('admin.categories.show.modal.max')),
    step: @json(__('admin.categories.show.modal.step')),
    showCurrency: @json(__('admin.categories.show.modal.show_currency')),
    allowTbd: @json(__('admin.categories.show.modal.allow_tbd')),
    options: @json(__('admin.categories.show.modal.options')),
    optAr: @json(__('admin.categories.show.modal.option_placeholder_ar')),
    optEn: @json(__('admin.categories.show.modal.option_placeholder_en')),
    optTr: @json(__('admin.categories.show.modal.option_placeholder_tr')),
    addOption: @json(__('admin.categories.show.modal.add_option')),
};

function showAddFieldModal() {
    document.getElementById('addFieldModal').classList.remove('hidden');
    document.getElementById('addFieldForm').reset();
    toggleFieldOptions();
}

function hideAddFieldModal() {
    document.getElementById('addFieldModal').classList.add('hidden');
}

function showEditFieldModal(index) {
    const field = categoryFields[index];
    if (!field) return;
    
    const form = document.getElementById('editFieldForm');
    form.action = `{{ route('admin.categories.custom-fields.update', [$category->id, '']) }}/${index}`;
    
    const content = document.getElementById('editFieldContent');
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.fieldId}</label>
                <input type="text" value="${field.id}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.fieldType} <span class="text-red-500">*</span></label>
                <select name="type" id="editFieldType" required onchange="toggleEditFieldOptions()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    <option value="text" ${field.type === 'text' ? 'selected' : ''}>${fieldT.typeText}</option>
                    <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>${fieldT.typeTextarea}</option>
                    <option value="number" ${field.type === 'number' ? 'selected' : ''}>${fieldT.typeNumber}</option>
                    <option value="select" ${field.type === 'select' ? 'selected' : ''}>${fieldT.typeSelect}</option>
                    <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>${fieldT.typeCheckbox}</option>
                    <option value="location" ${field.type === 'location' ? 'selected' : ''}>${fieldT.typeLocation}</option>
                    <option value="date" ${field.type === 'date' ? 'selected' : ''}>${fieldT.typeDate}</option>
                    <option value="car_body_map" ${field.type === 'car_body_map' ? 'selected' : ''}>${fieldT.typeCarBodyMap}</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.labelAr} <span class="text-red-500">*</span></label>
                <input type="text" name="label_ar" value="${field.label?.ar || ''}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.labelEn}</label>
                <input type="text" name="label_en" value="${field.label?.en || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.labelTr}</label>
                <input type="text" name="label_tr" value="${field.label?.tr || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="required" id="editRequired" value="1" ${field.required ? 'checked' : ''} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                <label for="editRequired" class="text-sm text-gray-700">${fieldT.required}</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="editIsActive" value="1" ${field.is_active !== false ? 'checked' : ''} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                <label for="editIsActive" class="text-sm text-gray-700">${fieldT.active}</label>
            </div>
        </div>
        <div id="editNumberOptions" class="${field.type === 'number' ? '' : 'hidden'} space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.min}</label>
                    <input type="number" name="min" value="${field.min || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.max}</label>
                    <input type="number" name="max" value="${field.max || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.step}</label>
                    <input type="number" name="step" value="${field.step || 1}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="show_currency" id="editShowCurrency" value="1" ${field.show_currency ? 'checked' : ''} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                <label for="editShowCurrency" class="text-sm text-gray-700">${fieldT.showCurrency}</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="allow_tbd" id="editAllowTbd" value="1" ${field.allow_tbd ? 'checked' : ''} class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                <label for="editAllowTbd" class="text-sm text-gray-700">${fieldT.allowTbd}</label>
            </div>
        </div>
        <div id="editSelectOptions" class="${field.type === 'select' ? '' : 'hidden'}">
            <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.options}</label>
            <div id="editOptionsContainer" class="space-y-2">
                ${(field.options || []).map((opt, idx) => `
                    <div class="flex gap-2">
                        <input type="text" name="options[${idx}][ar]" value="${opt.ar || ''}" placeholder="${fieldT.optAr}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        <input type="text" name="options[${idx}][en]" value="${opt.en || ''}" placeholder="${fieldT.optEn}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        <input type="text" name="options[${idx}][tr]" value="${opt.tr || ''}" placeholder="${fieldT.optTr}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        <button type="button" onclick="removeOption(this)" class="px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
            <button type="button" onclick="addEditOption()" class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                <i class="fas fa-plus ml-1"></i> ${fieldT.addOption}
            </button>
        </div>
    `;
    
    document.getElementById('editFieldModal').classList.remove('hidden');
    toggleEditFieldOptions();
}

function hideEditFieldModal() {
    document.getElementById('editFieldModal').classList.add('hidden');
}

function toggleFieldOptions() {
    const type = document.getElementById('fieldType').value;
    document.getElementById('numberOptions').classList.toggle('hidden', type !== 'number');
    document.getElementById('selectOptions').classList.toggle('hidden', type !== 'select');
}

function toggleEditFieldOptions() {
    const type = document.getElementById('editFieldType').value;
    document.getElementById('editNumberOptions').classList.toggle('hidden', type !== 'number');
    document.getElementById('editSelectOptions').classList.toggle('hidden', type !== 'select');
}

let optionIndex = 1;
function addOption() {
    const container = document.getElementById('optionsContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="options[${optionIndex}][ar]" placeholder="${fieldT.optAr}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
        <input type="text" name="options[${optionIndex}][en]" placeholder="${fieldT.optEn}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
        <input type="text" name="options[${optionIndex}][tr]" placeholder="${fieldT.optTr}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
        <button type="button" onclick="removeOption(this)" class="px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
    optionIndex++;
}

function addEditOption() {
    const container = document.getElementById('editOptionsContainer');
    const currentCount = container.children.length;
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="options[${currentCount}][ar]" placeholder="${fieldT.optAr}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
        <input type="text" name="options[${currentCount}][en]" placeholder="${fieldT.optEn}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
        <input type="text" name="options[${currentCount}][tr]" placeholder="${fieldT.optTr}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
        <button type="button" onclick="removeOption(this)" class="px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeOption(btn) {
    btn.parentElement.remove();
}
</script>
@endsection
