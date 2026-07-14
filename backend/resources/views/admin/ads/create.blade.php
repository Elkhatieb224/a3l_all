@extends('admin.layouts.app')

@section('title', __('admin.ads.create.title'))
@section('page-title', __('admin.ads.create.title'))

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('admin.ads.index') }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.ads.create.title') }}</h2>
                <p class="text-gray-600">{{ __('admin.ads.create.subtitle') }}</p>
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

    <form action="{{ route('admin.ads.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- User Selection -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.ads.create.user_section') }}</h3>
            <div>
                <label for="user_id" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.ads.create.user_label') }} <span class="text-red-500">*</span>
                </label>
                <select name="user_id" id="user_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('user_id') ? 'border-red-500' : '' }}">
                    <option value="">{{ __('admin.ads.create.select_user') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Category and Subcategory -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.ads.create.category_section') }}</h3>
            
            <!-- Category Selection -->
            <div class="mb-4">
                <label for="category_id" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.ads.create.main_category') }} <span class="text-red-500">*</span>
                </label>
                <select name="category_id" id="category_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('category_id') ? 'border-red-500' : '' }}">
                    <option value="">{{ __('admin.ads.create.select_category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->getName('ar') }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Subcategory Selection - Multi-level -->
            <div id="subcategory-selection" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.ads.create.subcategory') }} <span class="text-red-500">*</span>
                </label>
                
                <!-- Selected Path -->
                <div id="subcategory-path" class="mb-4 p-3 bg-gray-50 rounded-lg text-sm flex items-center gap-2 flex-wrap">
                    <span id="selected-category-name" class="text-primary font-semibold"></span>
                </div>

                <!-- Subcategory Levels Container -->
                <div id="subcategory-levels" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4"></div>

                <!-- Hidden input for final subcategory_id -->
                <input type="hidden" name="subcategory_id" id="subcategory_id" value="{{ old('subcategory_id') }}" required>
                @error('subcategory_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.ads.create.basic_info') }}</h3>
            
            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.ads.create.title_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('title') ? 'border-red-500' : '' }}">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.ads.create.description_label') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" id="description" rows="8" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('description') ? 'border-red-500' : '' }}">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.ads.create.price_label') }}
                        </label>
                        <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('price') ? 'border-red-500' : '' }}">
                        @error('price')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.ads.create.currency_label') }}
                        </label>
                        <select name="currency" id="currency"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('currency') ? 'border-red-500' : '' }}">
                            <option value="SYP" {{ old('currency', 'SYP') === 'SYP' ? 'selected' : '' }}>SYP</option>
                            <option value="TRY" {{ old('currency') === 'TRY' ? 'selected' : '' }}>TRY</option>
                            <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                        </select>
                        @error('currency')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price_type" class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.ads.create.price_type_label') }}
                        </label>
                        <select name="price_type" id="price_type"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('price_type') ? 'border-red-500' : '' }}">
                            <option value="fixed" {{ old('price_type', 'fixed') === 'fixed' ? 'selected' : '' }}>{{ __('admin.ads.create.price_type_fixed') }}</option>
                            <option value="negotiable" {{ old('price_type') === 'negotiable' ? 'selected' : '' }}>{{ __('admin.ads.create.price_type_negotiable') }}</option>
                            <option value="free" {{ old('price_type') === 'free' ? 'selected' : '' }}>{{ __('admin.ads.create.price_type_free') }}</option>
                        </select>
                        @error('price_type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Fields -->
        <div id="custom-fields-card" class="bg-white rounded-xl shadow-md p-6 hidden">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.ads.create.additional_details') }}</h3>
            <div id="custom-fields-container" class="space-y-4"></div>
        </div>

        <!-- Images -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.ads.create.images_section') }}</h3>
            <div>
                <label for="images" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.ads.create.images_label') }}
                </label>
                <input type="file" name="images[]" id="images" multiple accept="image/*"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('images.*') ? 'border-red-500' : '' }}">
                <p class="text-sm text-gray-500 mt-2">{{ __('admin.ads.create.images_hint') }}</p>
                @error('images.*')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Status and Options -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.ads.create.status_section') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.ads.create.status_label') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('status') ? 'border-red-500' : '' }}">
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.ads.tabs.pending') }}</option>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('admin.ads.tabs.active') }}</option>
                        <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>{{ __('admin.ads.tabs.rejected') }}</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expires_at" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.ads.create.expires_at') }}
                    </label>
                    <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('expires_at') ? 'border-red-500' : '' }}">
                    @error('expires_at')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="text-sm font-semibold text-gray-700">{{ __('admin.ads.create.featured') }}</span>
                    </label>
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent') ? 'checked' : '' }}
                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="text-sm font-semibold text-gray-700">{{ __('admin.ads.create.urgent') }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.ads.create.save') }}
            </button>
            <a href="{{ route('admin.ads.index') }}" class="btn-secondary px-8 py-3 rounded-lg font-bold">
                {{ __('admin.ads.create.cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const subcategoryIdInput = document.getElementById('subcategory_id');
    const subcategorySelection = document.getElementById('subcategory-selection');
    const subcategoryPath = document.getElementById('subcategory-path');
    const selectedCategoryName = document.getElementById('selected-category-name');
    const subcategoryLevels = document.getElementById('subcategory-levels');
    const customFieldsCard = document.getElementById('custom-fields-card');
    const customFieldsContainer = document.getElementById('custom-fields-container');

    const categories = @json($categories->keyBy('id'));
    const oldCustomFields = @json(old('custom_fields', []));
    const oldSubcategoryId = @json(old('subcategory_id'));
    const locale = @json(app()->getLocale());
    
    let selectedSubcategories = [];
    let allSubcategories = {}; // Store all subcategories by ID for quick lookup

    const buildCustomField = (field, fieldId, fieldValue) => {
        const isRequired = field.required ?? false;
        const fieldType = field.type ?? 'text';
        const fieldLabel = (field.label && (field.label[locale] || field.label['ar'] || field.label['en'])) || fieldId;
        const requiredMark = isRequired ? '<span class="text-red-500">*</span>' : '';
        const requiredAttr = isRequired ? 'required' : '';

        if (fieldType === 'textarea') {
            return `
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                    <textarea name="custom_fields[${fieldId}]" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>${fieldValue ?? ''}</textarea>
                </div>
            `;
        }

        if (fieldType === 'select' && Array.isArray(field.options)) {
            const options = field.options.map(option => {
                const optionValue = option[locale] || option['ar'] || option['en'] || option;
                const selected = fieldValue == optionValue ? 'selected' : '';
                return `<option value="${optionValue}" ${selected}>${optionValue}</option>`;
            }).join('');
            return `
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                    <select name="custom_fields[${fieldId}]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>
                        <option value="">${@json(__('admin.ads.create.select_option'))}</option>
                        ${options}
                    </select>
                </div>
            `;
        }

        if (fieldType === 'number') {
            const min = field.min !== undefined ? `min="${field.min}"` : '';
            const max = field.max !== undefined ? `max="${field.max}"` : '';
            const step = `step="${field.step ?? 1}"`;
            return `
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                    <input type="number" name="custom_fields[${fieldId}]" value="${fieldValue ?? ''}" ${step} ${min} ${max}
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>
                </div>
            `;
        }

        if (fieldType === 'checkbox') {
            const checked = fieldValue ? 'checked' : '';
            return `
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="custom_fields[${fieldId}]" value="1" ${checked}
                           class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                    <label class="text-sm font-semibold text-gray-700">${fieldLabel}</label>
                </div>
            `;
        }

        if (fieldType === 'date') {
            return `
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                    <input type="date" name="custom_fields[${fieldId}]" value="${fieldValue ?? ''}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>
                </div>
            `;
        }

        if (fieldType === 'location') {
            const lat = fieldValue?.lat ?? '';
            const lng = fieldValue?.lng ?? '';
            const address = fieldValue?.address ?? '';
            return `
                <div class="space-y-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">{{ __('admin.ads.create.latitude') }}</label>
                            <input type="number" name="custom_fields[${fieldId}][lat]" value="${lat}" step="any"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">{{ __('admin.ads.create.longitude') }}</label>
                            <input type="number" name="custom_fields[${fieldId}][lng]" value="${lng}" step="any"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">{{ __('admin.ads.create.location_address') }}</label>
                        <input type="text" name="custom_fields[${fieldId}][address]" value="${address}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
            `;
        }

        if (fieldType === 'car_body_map') {
            const partIds = @json(\App\Support\CarBodyMapSupport::partIds());
            const existingParts = fieldValue?.parts ?? {};
            const partsHtml = partIds.map(partId => {
                const status = existingParts[partId] ?? 'original';
                return `<input type="hidden" name="custom_fields[${fieldId}][parts][${partId}]" value="${status}">`;
            }).join('');
            return `
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
                        {{ __('frontend.car_body_map.admin_panel_hint') }}
                    </p>
                    ${partsHtml}
                    <input type="hidden" name="custom_fields[${fieldId}][all_original]" value="${fieldValue?.all_original ? '1' : '0'}">
                </div>
            `;
        }

        return `
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldLabel} ${requiredMark}</label>
                <input type="text" name="custom_fields[${fieldId}]" value="${fieldValue ?? ''}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" ${requiredAttr}>
            </div>
        `;
    };

    // Load all subcategories recursively
    const loadAllSubcategories = (subcategories, parentId = null) => {
        subcategories.forEach(sub => {
            allSubcategories[sub.id] = {
                id: sub.id,
                name_ar: sub.name_ar,
                name_en: sub.name_en,
                name_tr: sub.name_tr,
                parent_subcategory_id: parentId,
                children: []
            };
            
            if (sub.children && sub.children.length > 0) {
                loadAllSubcategories(sub.children, sub.id);
            }
        });
    };

    // Get subcategories by parent ID
    const getSubcategoriesByParent = (parentId) => {
        return Object.values(allSubcategories).filter(sub => {
            if (parentId === null) {
                return sub.parent_subcategory_id === null;
            }
            return sub.parent_subcategory_id === parentId;
        });
    };

    // Render subcategory level
    const renderSubcategoryLevel = (levelIndex, parentId, categoryId) => {
        const subs = getSubcategoriesByParent(parentId);
        if (subs.length === 0) return '';

        const levelTitle = levelIndex === 0 
            ? @json(__('admin.ads.create.subcategory_level_1'))
            : @json(__('admin.ads.create.subcategory_level')) + ' ' + (levelIndex + 1);

        let html = `
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-500 mb-3">${levelTitle}</h4>
                <div class="space-y-2 max-h-64 overflow-y-auto">
        `;

        subs.forEach(sub => {
            const isSelected = selectedSubcategories[levelIndex] && selectedSubcategories[levelIndex].id === sub.id;
            const name = sub.name_ar || sub.name_en || sub.name_tr;
            html += `
                <button type="button" 
                        onclick="selectSubcategory(${sub.id}, ${levelIndex})"
                        class="w-full p-2 text-right rounded-lg transition ${isSelected ? 'bg-primary text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-800'}">
                    ${name}
                </button>
            `;
        });

        html += `
                </div>
            </div>
        `;

        return html;
    };

    // Render all subcategory levels
    const renderSubcategoryLevels = (categoryId) => {
        subcategoryLevels.innerHTML = '';
        
        // Level 0: First level subcategories
        const level0Html = renderSubcategoryLevel(0, null, categoryId);
        if (level0Html) {
            subcategoryLevels.insertAdjacentHTML('beforeend', level0Html);
        }

        // Render selected levels
        selectedSubcategories.forEach((selected, index) => {
            const nextLevelHtml = renderSubcategoryLevel(index + 1, selected.id, categoryId);
            if (nextLevelHtml) {
                subcategoryLevels.insertAdjacentHTML('beforeend', nextLevelHtml);
            }
        });
    };

    // Update subcategory path display
    const updateSubcategoryPath = () => {
        const category = categories[categorySelect.value];
        if (!category) return;

        selectedCategoryName.textContent = category.name_ar || category.name_en || category.name_tr;
        
        let pathHtml = `<span class="text-primary font-semibold">${selectedCategoryName.textContent}</span>`;
        
        selectedSubcategories.forEach((sub, index) => {
            const subName = sub.name_ar || sub.name_en || sub.name_tr;
            pathHtml += ` <span class="text-gray-400"> < </span> <span class="text-gray-700">${subName}</span>`;
        });
        
        subcategoryPath.innerHTML = pathHtml;
    };

    // Select subcategory
    window.selectSubcategory = function(subcategoryId, levelIndex) {
        const sub = allSubcategories[subcategoryId];
        if (!sub) return;

        // Remove all subcategories after this level
        selectedSubcategories = selectedSubcategories.slice(0, levelIndex);
        
        // Add the selected subcategory
        selectedSubcategories.push({
            id: sub.id,
            name_ar: sub.name_ar,
            name_en: sub.name_en,
            name_tr: sub.name_tr
        });

        // Update hidden input
        subcategoryIdInput.value = sub.id;

        // Update display
        updateSubcategoryPath();
        renderSubcategoryLevels(categorySelect.value);

        // Check if this subcategory has children
        const hasChildren = getSubcategoriesByParent(sub.id).length > 0;
        if (!hasChildren) {
            // Final selection made, can proceed
            renderCustomFields(categorySelect.value);
        }
    };

    const renderCustomFields = (categoryId) => {
        customFieldsContainer.innerHTML = '';
        const category = categories[categoryId];
        const fields = (category && category.custom_fields) ? category.custom_fields.filter(f => f.is_active ?? true) : [];

        if (!fields.length) {
            customFieldsCard.classList.add('hidden');
            return;
        }

        fields.forEach((field, index) => {
            const fieldId = field.id ?? `field_${index}`;
            const fieldValue = oldCustomFields[fieldId] ?? null;
            customFieldsContainer.insertAdjacentHTML('beforeend', buildCustomField(field, fieldId, fieldValue));
        });

        customFieldsCard.classList.remove('hidden');
    };

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        if (!categoryId) {
            subcategorySelection.classList.add('hidden');
            customFieldsCard.classList.add('hidden');
            subcategoryIdInput.value = '';
            selectedSubcategories = [];
            return;
        }

        const category = categories[categoryId];
        if (!category) return;

        // Reset subcategory selection
        selectedSubcategories = [];
        subcategoryIdInput.value = '';
        allSubcategories = {};
        
        // Load all subcategories
        if (category.subcategories) {
            loadAllSubcategories(category.subcategories, null);
        }

        // Show subcategory selection
        subcategorySelection.classList.remove('hidden');
        updateSubcategoryPath();
        renderSubcategoryLevels(categoryId);

        // If old subcategory ID exists, try to restore selection
        if (oldSubcategoryId) {
            const restoreSubcategory = (subId, parentId, level) => {
                const subs = getSubcategoriesByParent(parentId);
                const found = subs.find(s => s.id == subId);
                if (found) {
                    selectedSubcategories.push({
                        id: found.id,
                        name_ar: found.name_ar,
                        name_en: found.name_en,
                        name_tr: found.name_tr
                    });
                    subcategoryIdInput.value = found.id;
                    updateSubcategoryPath();
                    renderSubcategoryLevels(categoryId);
                    
                    // Check if this is the final one
                    const hasChildren = getSubcategoriesByParent(found.id).length > 0;
                    if (!hasChildren || found.id == oldSubcategoryId) {
                        renderCustomFields(categoryId);
                    } else {
                        // Continue to next level
                        restoreSubcategory(oldSubcategoryId, found.id, level + 1);
                    }
                }
            };
            restoreSubcategory(oldSubcategoryId, null, 0);
        }
    });

    // Trigger on page load if category is already selected
    if (categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection

