@php
    $importRoute = $importRoute ?? null;
    $inputId = $inputId ?? 'custom_fields_import_file';
@endphp

@if(!empty($importRoute))
    <div class="mt-6 pt-6 border-t border-dashed border-secondary/40">
        <h4 class="text-base font-bold text-primary mb-2 flex items-center gap-2">
            <i class="fas fa-file-import text-secondary"></i>
            {{ __('admin.categories.custom_fields.json_import_title') }}
        </h4>
        <p class="text-sm text-gray-600 mb-4">{{ __('admin.categories.custom_fields.json_import_hint') }}</p>
        <form action="{{ $importRoute }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex flex-col gap-4"
              onsubmit="return confirmCustomFieldsImport(this)">
            @csrf
            <div class="flex flex-col sm:flex-row sm:flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label for="{{ $inputId }}" class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('admin.categories.custom_fields.json_import_choose') }}
                    </label>
                    <input type="file"
                           id="{{ $inputId }}"
                           name="import_file"
                           accept=".json,application/json"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm {{ $errors->has('import_file') ? 'border-red-500' : '' }}">
                    @error('import_file')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="min-w-[180px]">
                    <span class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('admin.categories.custom_fields.json_import_mode') }}
                    </span>
                    <div class="flex flex-col gap-2 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="import_mode" value="replace" checked class="text-primary">
                            <span>{{ __('admin.categories.custom_fields.json_import_mode_replace') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="import_mode" value="merge" class="text-primary">
                            <span>{{ __('admin.categories.custom_fields.json_import_mode_merge') }}</span>
                        </label>
                    </div>
                </div>
            </div>
            <div>
                <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold whitespace-nowrap inline-flex items-center gap-2">
                    <i class="fas fa-upload"></i>
                    {{ __('admin.categories.custom_fields.json_import_submit') }}
                </button>
            </div>
        </form>
    </div>

    @once
        @push('scripts')
            <script>
                function confirmCustomFieldsImport(form) {
                    const mode = form.querySelector('input[name="import_mode"]:checked')?.value;
                    if (mode === 'replace') {
                        return confirm(@json(__('admin.categories.custom_fields.json_import_replace_confirm')));
                    }
                    return true;
                }
            </script>
        @endpush
    @endonce
@endif
