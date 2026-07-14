@php
    $ownFields = $ownFields ?? [];
    $resolved = $resolved ?? ['fields' => [], 'source' => null, 'source_subcategory_id' => null];
    $displayFields = !empty($ownFields) ? $ownFields : ($resolved['fields'] ?? []);
    $isInherited = ($entityType ?? 'category') === 'subcategory'
        && empty($ownFields)
        && !empty($resolved['fields'])
        && ($resolved['source'] ?? null) === 'subcategory'
        && (int)($resolved['source_subcategory_id'] ?? 0) !== (int)($entityId ?? 0);
    $canManageFields = ($isSuper ?? false) && !$isInherited;
    $entityId = (int) ($entityId ?? 0);
    $customFieldsRouteGroup = ($entityType ?? 'category') === 'subcategory' ? 'admin.subcategories' : 'admin.categories';
    $customFieldsUrlSegment = ($entityType ?? 'category') === 'subcategory' ? 'subcategories' : 'categories';
    $storeRoute = $storeRoute ?? route($customFieldsRouteGroup.'.custom-fields.store', $entityId);
    $updateRoutePrefix = $updateRoutePrefix ?? url('/admin/'.$customFieldsUrlSegment.'/'.$entityId.'/custom-fields');
@endphp

<div class="bg-white rounded-xl shadow-md p-6" id="custom-fields-section">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <h3 class="text-xl font-bold text-primary flex items-center gap-2 min-w-0">
            <i class="fas fa-list text-secondary shrink-0"></i>
            {{ $title ?? __('admin.categories.show.custom_fields_title') }}
        </h3>
        @if($canManageFields)
            <button type="button" onclick="showAddFieldModal()" class="btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2 shrink-0">
                <i class="fas fa-plus"></i>
                {{ __('admin.categories.show.add_field') }}
            </button>
        @endif
    </div>

    @if($isInherited)
        <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-blue-900 text-sm flex items-start gap-3">
            <i class="fas fa-level-down-alt mt-0.5 text-blue-600"></i>
            <div>
                <p class="font-semibold">{{ __('admin.categories.custom_fields.inherited_title') }}</p>
                <p class="mt-1 text-blue-800/90">
                    {{ __('admin.categories.custom_fields.inherited_body', ['name' => $inheritedFromName ?? '—']) }}
                </p>
                @if(!empty($manageAtUrl))
                    <a href="{{ $manageAtUrl }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        {{ __('admin.categories.custom_fields.inherited_manage_link') }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if(($entityType ?? '') === 'subcategory' && ($isSuper ?? false) && !$isInherited)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 text-sm">
            <p>{{ __('admin.categories.custom_fields.subcategory_path_hint') }}</p>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="text-sm text-red-600 space-y-1">
                @foreach($errors->all() as $error)
                    <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($displayFields))
        <div class="space-y-4">
            @foreach($displayFields as $index => $field)
                <div class="border {{ ($field['is_active'] ?? true) ? 'border-gray-200' : 'border-gray-100 bg-gray-50 opacity-60' }} rounded-lg p-4 hover:border-secondary transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                <span class="px-3 py-1 bg-primary text-white rounded-lg text-xs font-semibold">{{ $field['type'] }}</span>
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
                            @if($canManageFields)
                                <button type="button" onclick="showEditFieldModal({{ $index }})"
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50"
                                        title="{{ __('admin.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route($customFieldsRouteGroup.'.custom-fields.toggle-status', ['id' => $entityId, 'fieldIndex' => $index]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50" title="{{ ($field['is_active'] ?? true) ? __('admin.categories.show.toggle_disable') : __('admin.categories.show.toggle_enable') }}">
                                        <i class="fas fa-{{ ($field['is_active'] ?? true) ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route($customFieldsRouteGroup.'.custom-fields.delete', ['id' => $entityId, 'fieldIndex' => $index]) }}" method="POST"
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50" title="{{ __('admin.delete') }}">
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

    @if($canManageFields)
        @include('admin.partials.custom-fields-import-form', [
            'importRoute' => route($customFieldsRouteGroup.'.custom-fields.import-json', $entityId),
            'inputId' => 'custom_fields_import_file_'.$entityId,
        ])
    @endif
</div>

@if($canManageFields)
    @include('admin.partials.custom-fields-modals', [
        'entityFields' => $ownFields,
        'storeRoute' => $storeRoute,
        'updateRoutePrefix' => $updateRoutePrefix,
    ])
@endif
