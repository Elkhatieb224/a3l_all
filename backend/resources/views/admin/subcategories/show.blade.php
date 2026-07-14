@extends('admin.layouts.app')

@section('title', __('admin.categories.subcategories.show_title'))
@section('page-title', __('admin.categories.subcategories.show_title'))

@section('content')
@php
    $adminUser = auth('admin')->user();
    $isSuper = $adminUser && $adminUser->isSuperAdmin();
@endphp
<div class="space-y-6">
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mt-1"></i>
                <div class="flex-1">
                    <h3 class="text-red-800 font-bold mb-2">{{ __('admin.validation_errors') }}</h3>
                    <ul class="list-disc list-inside space-y-1 text-red-700 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Breadcrumb -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.categories.index') }}" class="text-gray-600 hover:text-primary">
                {{ __('admin.categories.title') }}
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('admin.categories.show', $subcategory->category_id) }}" class="text-gray-600 hover:text-primary">
                {{ $subcategory->category->getName('ar') }}
            </a>
            @if($subcategory->parent)
                <span class="text-gray-400">/</span>
                <a href="{{ route('admin.subcategories.show', $subcategory->parent->id) }}" class="text-gray-600 hover:text-primary">
                    {{ $subcategory->parent->getName('ar') }}
                </a>
            @endif
            <span class="text-gray-400">/</span>
            <span class="text-primary font-semibold">{{ $subcategory->getName('ar') }}</span>
        </div>
    </div>

    <!-- Subcategory Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-4">
                @if($subcategory->icon)
                    <img src="{{ asset('storage/' . $subcategory->icon) }}"
                         alt="{{ $subcategory->getName('ar') }}"
                         class="w-20 h-20 object-contain rounded-xl border-2 border-secondary bg-white p-2 shadow-md">
                @else
                    <div class="w-20 h-20 bg-primary text-white rounded-xl flex items-center justify-center shadow-md">
                        <i class="fas fa-folder text-3xl"></i>
                    </div>
                @endif
                <div>
                    <h2 class="text-2xl font-bold text-primary">{{ $subcategory->getName('ar') }}</h2>
                    <p class="text-gray-600">{{ $subcategory->name_en }} / {{ $subcategory->name_tr }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $subcategory->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $subcategory->is_active ? __('admin.active') : __('admin.inactive') }}
                        </span>
                        @if($subcategory->trashed())
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                                {{ __('admin.categories.show.deleted_badge') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.subcategories.edit', $subcategory->id) }}"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2 transition">
                    <i class="fas fa-edit"></i>
                    {{ __('admin.edit') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.categories.show.subcategories_label') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $children->count() }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.categories.show.ads_label') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $subcategory->ads_count }}</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.categories.show.order_short') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $subcategory->order }}</p>
            </div>
        </div>
    </div>

    @include('admin.partials.custom-fields-section', [
        'entityType' => 'subcategory',
        'entityId' => $subcategory->id,
        'ownFields' => $subcategory->custom_fields ?? [],
        'resolved' => $resolved,
        'inheritedFromName' => $sourceSubcategory?->getName('ar'),
        'manageAtUrl' => $sourceSubcategory ? route('admin.subcategories.show', $sourceSubcategory->id) : null,
        'isSuper' => $isSuper,
        'title' => __('admin.categories.custom_fields.subcategory_title'),
    ])

    <div class="bg-white rounded-xl shadow-md p-6 border border-dashed border-secondary/40">
        <h3 class="text-lg font-bold text-primary mb-2 flex items-center gap-2">
            <i class="fas fa-file-import text-secondary"></i>
            {{ __('admin.categories.subcategories.json_import_title') }}
        </h3>
        <p class="text-sm text-gray-600 mb-4">{{ __('admin.categories.subcategories.json_import_hint_show') }}</p>
        <form action="{{ route('admin.subcategories.import-json', $subcategory->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label for="import_file_show" class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ __('admin.categories.subcategories.json_import_choose') }}
                </label>
                <input type="file"
                       id="import_file_show"
                       name="import_file"
                       accept=".json,application/json"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm {{ $errors->has('import_file') ? 'border-red-500' : '' }}">
                @error('import_file')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold whitespace-nowrap">
                <i class="fas fa-upload ml-2"></i>
                {{ __('admin.categories.subcategories.json_import_submit') }}
            </button>
        </form>
    </div>

    <!-- Children Subcategories -->
    @if($children->count() > 0)
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                <i class="fas fa-sitemap text-secondary"></i>
                {{ __('admin.categories.subcategories.children_title', ['count' => $children->count()]) }}
            </h3>
        </div>

        <!-- Search -->
        <div class="mb-6">
            <form method="GET" action="{{ route('admin.subcategories.show', $subcategory->id) }}" class="flex gap-3">
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
                    <a href="{{ route('admin.subcategories.show', $subcategory->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg">
                        <i class="fas fa-times ml-2"></i>
                        {{ __('admin.clear') }}
                    </a>
                @endif
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($children as $child)
                <div class="border rounded-lg p-4 hover:shadow-md transition {{ $child->trashed() ? 'border-gray-300 bg-gray-50 opacity-75' : 'border-gray-200' }}">
                    @if($child->trashed())
                        <div class="mb-2 flex items-center gap-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-semibold">
                            <i class="fas fa-trash"></i>
                            <span>{{ __('admin.categories.show.deleted_badge') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            @if($child->icon)
                                <img src="{{ asset('storage/' . $child->icon) }}"
                                     alt="{{ $child->getName('ar') }}"
                                     class="w-8 h-8 object-contain rounded border border-gray-200 bg-white p-1">
                            @endif
                            <h4 class="font-bold {{ $child->trashed() ? 'text-gray-500' : 'text-gray-800' }}">{{ $child->getName('ar') }}</h4>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $child->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $child->is_active ? __('admin.active') : __('admin.inactive') }}
                        </span>
                    </div>
                    <p class="text-sm {{ $child->trashed() ? 'text-gray-400' : 'text-gray-600' }} mb-3">{{ $child->name_en }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span>
                            <i class="fas fa-bullhorn"></i>
                            {{ __('admin.categories.show.ads_count_label', ['count' => $child->ads_count ?? 0]) }}
                        </span>
                        <span>{{ __('admin.categories.show.order_label', ['order' => $child->order]) }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t">
                        @if($child->trashed())
                            <form action="{{ route('admin.subcategories.restore', $child->id) }}"
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
                            <a href="{{ route('admin.subcategories.show', $child->id) }}"
                               class="flex-1 text-center bg-primary text-white hover:bg-primary-dark py-2 rounded-lg transition text-sm">
                                <i class="fas fa-eye"></i> {{ __('admin.view') }}
                            </a>
                            <a href="{{ route('admin.subcategories.edit', $child->id) }}"
                               class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-edit"></i> {{ __('admin.edit') }}
                            </a>
                        @endif

                        @if(!$child->trashed())
                            <form action="{{ route('admin.subcategories.destroy', $child->id) }}"
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
                            <form action="{{ route('admin.subcategories.force-delete', $child->id) }}"
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
    @else
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="text-center py-12">
                <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.categories.subcategories.no_children') }}</p>
            </div>
        </div>
    @endif
</div>
@endsection

