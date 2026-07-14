<!-- Add Field Modal -->
<div id="addFieldModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">{{ __('admin.categories.show.modal.add_title') }}</h3>
            <button type="button" onclick="hideAddFieldModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addFieldForm" action="{{ $storeRoute }}" method="POST">
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
            <button type="button" onclick="hideEditFieldModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editFieldForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4" id="editFieldContent"></div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="hideEditFieldModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">{{ __('admin.categories.show.modal.cancel') }}</button>
                <button type="submit" class="btn-primary px-4 py-2 rounded-md">{{ __('admin.categories.show.modal.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
const entityFields = @json($entityFields ?? []);
const updateRoutePrefix = @json($updateRoutePrefix);
const fieldT = {
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
    const field = entityFields[index];
    if (!field) return;

    const form = document.getElementById('editFieldForm');
    form.action = updateRoutePrefix + '/' + index;

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
                <input type="text" name="label_ar" value="${(field.label && field.label.ar) || ''}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.labelEn}</label>
                <input type="text" name="label_en" value="${(field.label && field.label.en) || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">${fieldT.labelTr}</label>
                <input type="text" name="label_tr" value="${(field.label && field.label.tr) || ''}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
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
