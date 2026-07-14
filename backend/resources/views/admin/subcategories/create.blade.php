@extends('admin.layouts.app')

@section('title', __('admin.categories.subcategories.create_title'))
@section('page-title', __('admin.categories.subcategories.create_title'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('admin.categories.show', $category->id) }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.categories.subcategories.create_title') }}</h2>
                <p class="text-gray-600">{{ __('admin.categories.show.category') ?? '' }}: {{ $category->getName('ar') }}</p>
            </div>
        </div>
    </div>

    <!-- Display Validation Errors -->
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
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

    <form action="{{ route('admin.categories.subcategories.store', $category->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-secondary"></i>
                {{ __('admin.categories.subcategories.basic_info') }}
            </h3>

            <div class="space-y-4">
                <!-- Cascading Parent Subcategory Selectors (Multi-level Infinite) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.parent_label') }}
                    </label>
                    <div id="subcategory-selectors" class="space-y-3">
                        <!-- First level dropdown -->
                        <div class="subcategory-selector-wrapper" data-level="0">
                            <select class="subcategory-selector w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('parent_subcategory_id') ? 'border-red-500' : '' }}"
                                    data-level="0">
                                <option value="">{{ __('admin.categories.subcategories.parent_placeholder') }}</option>
                                @foreach($subcategories as $sub)
                                    <option value="{{ $sub->id }}" {{ old('parent_subcategory_id') == $sub->id ? 'selected' : '' }}>{{ $sub->getName('ar') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- Hidden input to store the final selected parent ID -->
                    <input type="hidden" name="parent_subcategory_id" id="parent_subcategory_id" value="{{ old('parent_subcategory_id') }}">
                    @error('parent_subcategory_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('admin.categories.subcategories.parent_placeholder') }}
                    </p>
                </div>

                <!-- Arabic Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name_ar"
                           value="{{ old('name_ar') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('name_ar') ? 'border-red-500' : '' }}">
                    @error('name_ar')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- English Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.name_en') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name_en"
                           value="{{ old('name_en') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('name_en') ? 'border-red-500' : '' }}">
                    @error('name_en')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Turkish Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.name_tr') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name_tr"
                           value="{{ old('name_tr') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('name_tr') ? 'border-red-500' : '' }}">
                    @error('name_tr')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.slug') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="slug"
                           value="{{ old('slug') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('slug') ? 'border-red-500' : '' }}"
                           placeholder="example-subcategory">
                    @error('slug')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Icon -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.icon') }}
                    </label>
                    <input type="file" 
                           name="icon" 
                           accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('icon') ? 'border-red-500' : '' }}">
                    @error('icon')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('admin.categories.subcategories.supported_formats_hint') }}
                    </p>
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.categories.subcategories.order') }}
                    </label>
                    <input type="number"
                           name="order"
                           value="{{ old('order', 0) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary {{ $errors->has('order') ? 'border-red-500' : '' }}">
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           name="is_active"
                           checked
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        {{ __('admin.categories.subcategories.activate') }}
                    </label>
                </div>
            </div>
        </div>

        @include('admin.categories.partials.ad-images-settings', [
            'record' => (object) ['ad_images_mode' => null, 'ad_images_max' => null, 'ad_gallery_images' => []],
            'showInherit' => true,
        ])

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.categories.subcategories.save') }}
            </button>

            <a href="{{ route('admin.categories.show', $category->id) }}"
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.categories.subcategories.cancel') }}
            </a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md p-6 mt-6 border border-dashed border-secondary/40">
        <h3 class="text-lg font-bold text-primary mb-2 flex items-center gap-2">
            <i class="fas fa-file-import text-secondary"></i>
            {{ __('admin.categories.subcategories.json_import_title') }}
        </h3>
        <p class="text-sm text-gray-600 mb-4">{{ __('admin.categories.subcategories.json_import_hint') }}</p>
        <form action="{{ route('admin.categories.subcategories.import-json', $category->id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="subcategory-json-import-form"
              class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3">
            @csrf
            <input type="hidden" name="parent_subcategory_id" id="import_parent_subcategory_id" value="">
            <div class="flex-1 min-w-[200px]">
                <label for="import_file" class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ __('admin.categories.subcategories.json_import_choose') }}
                </label>
                <input type="file"
                       id="import_file"
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectorsContainer = document.getElementById('subcategory-selectors');
    const hiddenInput = document.getElementById('parent_subcategory_id');
    const importParentHidden = document.getElementById('import_parent_subcategory_id');
    const importForm = document.getElementById('subcategory-json-import-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (importForm && importParentHidden && hiddenInput) {
        importForm.addEventListener('submit', function() {
            importParentHidden.value = hiddenInput.value || '';
        });
    }
    
    // Handle subcategory selection change
    function handleSubcategoryChange(selectElement) {
        const selectedId = selectElement.value;
        const level = parseInt(selectElement.dataset.level);
        
        // Update hidden input with the selected parent ID
        hiddenInput.value = selectedId || '';
        
        // Remove all dropdowns after this level
        removeDropdownsAfterLevel(level);
        
        // If a subcategory is selected, load its children
        if (selectedId) {
            loadSubcategoryChildren(selectedId, level + 1);
        }
    }
    
    // Load children of a subcategory
    async function loadSubcategoryChildren(parentId, level) {
        try {
            const response = await fetch(`/admin/subcategories/${parentId}/children`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                // Create new dropdown for this level
                createDropdown(level, result.data);
            }
        } catch (error) {
            console.error('Error loading subcategory children:', error);
        }
    }
    
    // Create a new dropdown for a specific level
    function createDropdown(level, subcategories) {
        const wrapper = document.createElement('div');
        wrapper.className = 'subcategory-selector-wrapper';
        wrapper.dataset.level = level;
        
        const select = document.createElement('select');
        select.className = 'subcategory-selector w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary';
        select.dataset.level = level;
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'لا يوجد (قسم فرعي في نفس المستوى)';
        select.appendChild(defaultOption);
        
        // Add subcategory options
        subcategories.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.id;
            option.textContent = sub.display_name || sub.name_ar || sub.name_en || sub.name_tr;
            select.appendChild(option);
        });
        
        // Add event listener
        select.addEventListener('change', function() {
            handleSubcategoryChange(this);
        });
        
        wrapper.appendChild(select);
        selectorsContainer.appendChild(wrapper);
    }
    
    // Remove all dropdowns after a specific level
    function removeDropdownsAfterLevel(level) {
        const allWrappers = selectorsContainer.querySelectorAll('.subcategory-selector-wrapper');
        allWrappers.forEach(wrapper => {
            const wrapperLevel = parseInt(wrapper.dataset.level);
            if (wrapperLevel > level) {
                wrapper.remove();
            }
        });
    }
    
    // Attach event listeners to existing dropdowns
    const existingSelectors = selectorsContainer.querySelectorAll('.subcategory-selector');
    existingSelectors.forEach(select => {
        select.addEventListener('change', function() {
            handleSubcategoryChange(this);
        });
    });
    
    // Set initial value if old input exists (for validation errors)
    const oldParentId = @json(old('parent_subcategory_id'));
    // Note: Rebuilding the full hierarchy from old input would require additional API calls
    // For now, users can reselect the hierarchy if validation fails
});
</script>
@endpush

