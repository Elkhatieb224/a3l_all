<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Subcategory;
use App\Support\AdGalleryImages;
use App\Support\CustomFieldValidation;
use App\Support\CustomFieldsJsonImporter;
use App\Support\CustomFieldsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    public function show(Request $request, $id)
    {
        $subcategory = Subcategory::with(['category', 'parent'])
            ->withTrashed()
            ->withCount('ads')
            ->findOrFail($id);

        // Get children subcategories with search
        $children = Subcategory::where('parent_subcategory_id', $id)
            ->withTrashed()
            ->withCount('ads')
            ->when($request->search, function($query) use ($request) {
                $query->where(function($q) use ($request) {
                    $q->where('name_ar', 'like', '%' . $request->search . '%')
                      ->orWhere('name_en', 'like', '%' . $request->search . '%')
                      ->orWhere('name_tr', 'like', '%' . $request->search . '%');
                });
            })
            ->orderBy('order')
            ->get();

        $resolved = CustomFieldsResolver::resolve($subcategory->category, $subcategory);
        $sourceSubcategory = null;
        if ($resolved['source_subcategory_id']) {
            $sourceSubcategory = Subcategory::query()->find($resolved['source_subcategory_id']);
        }

        return view('admin.subcategories.show', compact('subcategory', 'children', 'resolved', 'sourceSubcategory'));
    }

    public function create($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $subcategories = Subcategory::where('category_id', $categoryId)
            ->whereNull('parent_subcategory_id')
            ->orderBy('order')
            ->get();

        return view('admin.subcategories.create', compact('category', 'subcategories'));
    }

    /**
     * Get children of a subcategory (AJAX endpoint for cascading dropdowns)
     * Returns all children (active and inactive) for admin management
     */
    public function getChildren($subcategoryId)
    {
        $subcategory = Subcategory::findOrFail($subcategoryId);

        // Get all children (admin can see both active and inactive)
        $children = Subcategory::where('parent_subcategory_id', $subcategoryId)
            ->orderBy('order')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'name_tr', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => $children->map(fn (Subcategory $child) => [
                'id' => $child->id,
                'name_ar' => $child->name_ar,
                'name_en' => $child->name_en,
                'name_tr' => $child->name_tr,
                'display_name' => $child->getName('ar'),
                'is_active' => $child->is_active,
            ])->values(),
        ]);
    }

    public function store(Request $request, $categoryId)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'required|string|max:255',
            'slug' => 'required|string|unique:subcategories,slug',
            'parent_subcategory_id' => 'nullable|exists:subcategories,id',
            'icon' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,gif,bmp,avif,ico|max:4096',
            'ad_images_max' => 'nullable|integer|min:1|max:50',
        ]);

        // Get the deepest selected parent (the last selected in the hierarchy)
        // The parent_subcategory_id will be the last selected value from cascading dropdowns
        $parentId = $request->parent_subcategory_id;

        // Validate that the parent belongs to the same category
        if ($parentId) {
            $parent = Subcategory::findOrFail($parentId);
            if ($parent->category_id != $categoryId) {
                return back()->withErrors(['parent_subcategory_id' => 'القسم الفرعي المحدد لا ينتمي لهذا القسم الرئيسي'])->withInput();
            }
        }

        $data = [
            'category_id' => $categoryId,
            'parent_subcategory_id' => $parentId,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'name_tr' => $request->name_tr,
            'slug' => $request->slug,
            'description_ar' => $request->description_ar,
            'description_en' => $request->description_en,
            'description_tr' => $request->description_tr,
            'order' => $request->order ?? 0,
            'is_active' => $request->has('is_active'),
        ];

        // Handle icon upload (تحويل الصور لـ WebP)
        if ($request->hasFile('icon')) {
            $data['icon'] = store_image_as_webp($request->file('icon'), 'subcategories/icons');
        }

        $modeIn = $request->input('ad_images_mode', '');
        if ($modeIn === '' || $modeIn === 'inherit') {
            $data['ad_images_mode'] = null;
        } elseif (in_array($modeIn, ['user_upload', 'admin_gallery'], true)) {
            $data['ad_images_mode'] = $modeIn;
        } else {
            $data['ad_images_mode'] = null;
        }
        $data['ad_images_max'] = $request->filled('ad_images_max')
            ? (int) $request->input('ad_images_max')
            : null;
        $data['ad_gallery_images'] = [];

        $subcategory = Subcategory::create($data);

        $subcategory->ad_gallery_images = AdGalleryImages::mergeFromRequest(
            $request,
            [],
            'subcategories/ad_gallery/'.$subcategory->id
        );
        $subcategory->save();

        ActivityLog::log('subcategory_created', $subcategory);

        return redirect()->route('admin.categories.show', $categoryId)
            ->with('success', 'تم إضافة القسم الفرعي بنجاح');
    }

    /**
     * Bulk-create subcategories from a JSON file (names per locale; slug from English name).
     * Expected shape: { "locale_keys": ["tr","en","ar"], "items": [ { "tr": "...", "en": "...", "ar": "..." }, ... ] }
     */
    public function importFromJson(Request $request, $categoryId)
    {
        Category::findOrFail($categoryId);

        $request->validate([
            'import_file' => 'required|file|max:2048',
            'parent_subcategory_id' => 'nullable|exists:subcategories,id',
        ]);

        $parentId = $request->parent_subcategory_id ?: null;
        if ($parentId) {
            $parent = Subcategory::findOrFail($parentId);
            if ((int) $parent->category_id !== (int) $categoryId) {
                return back()->withErrors([
                    'parent_subcategory_id' => __('admin.categories.subcategories.json_parent_wrong_category'),
                ])->withInput();
            }
        }

        $raw = file_get_contents($request->file('import_file')->getRealPath());
        $prepared = [];
        $error = $this->jsonImportErrorFromRaw($raw, (int) $categoryId, $parentId ? (int) $parentId : null, $prepared);
        if ($error !== null) {
            return back()->withErrors(['import_file' => $error]);
        }

        $created = $this->persistJsonImportedSubcategories((int) $categoryId, $parentId, $prepared);

        return redirect()->route('admin.categories.show', $categoryId)
            ->with('success', __('admin.categories.subcategories.json_import_success', ['count' => $created]));
    }

    /**
     * JSON import as direct children of the subcategory shown at /admin/subcategories/{id}.
     */
    public function importChildrenFromJson(Request $request, $id)
    {
        $parent = Subcategory::findOrFail($id);

        $request->validate([
            'import_file' => 'required|file|max:2048',
        ]);

        $categoryId = (int) $parent->category_id;
        $parentId = (int) $parent->id;

        $raw = file_get_contents($request->file('import_file')->getRealPath());
        $prepared = [];
        $error = $this->jsonImportErrorFromRaw($raw, $categoryId, $parentId, $prepared);
        if ($error !== null) {
            return back()->withErrors(['import_file' => $error]);
        }

        $created = $this->persistJsonImportedSubcategories($categoryId, $parentId, $prepared);

        return redirect()->route('admin.subcategories.show', $id)
            ->with('success', __('admin.categories.subcategories.json_import_success', ['count' => $created]));
    }

    /**
     * Parse JSON body and build rows with unique slugs. Sets $prepared by reference; returns error message or null.
     *
     * @param  array<int, array{name_ar: string, name_en: string, name_tr: string, slug: string}>  $prepared
     */
    private function jsonImportErrorFromRaw(string $raw, int $categoryId, ?int $parentId, array &$prepared): ?string
    {
        $prepared = [];
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return __('admin.categories.subcategories.json_invalid');
        }

        $items = $data['items'] ?? null;
        if (! is_array($items) || count($items) === 0) {
            return __('admin.categories.subcategories.json_no_items');
        }

        $rowErrors = [];
        $rows = [];
        foreach ($items as $i => $item) {
            $rowNum = $i + 1;
            if (! is_array($item)) {
                $rowErrors[] = __('admin.categories.subcategories.json_row_invalid', ['row' => $rowNum]);

                continue;
            }
            $en = isset($item['en']) ? trim((string) $item['en']) : '';
            if ($en === '') {
                $rowErrors[] = __('admin.categories.subcategories.json_row_no_en', ['row' => $rowNum]);

                continue;
            }
            $ar = isset($item['ar']) ? trim((string) $item['ar']) : '';
            $tr = isset($item['tr']) ? trim((string) $item['tr']) : '';
            if ($ar === '') {
                $ar = $en;
            }
            if ($tr === '') {
                $tr = $en;
            }

            $baseSlug = Str::slug($en, '-', 'en');
            if ($baseSlug === '') {
                $rowErrors[] = __('admin.categories.subcategories.json_row_slug_empty', ['row' => $rowNum]);

                continue;
            }

            $rows[] = [
                'name_ar' => $ar,
                'name_en' => $en,
                'name_tr' => $tr,
                'base_slug' => $baseSlug,
            ];
        }

        if ($rowErrors !== []) {
            return implode(' ', $rowErrors);
        }

        if ($rows === []) {
            return __('admin.categories.subcategories.json_no_items');
        }

        $usedSlugs = [];
        $categorySlug = (string) (Category::query()->where('id', $categoryId)->value('slug') ?? '');
        $parentSlug = '';
        if ($parentId) {
            $parentSlug = (string) (Subcategory::withTrashed()->where('id', $parentId)->value('slug') ?? '');
        }

        foreach ($rows as $idx => $row) {
            $s = $row['base_slug'];
            $unique = $s;
            if (Subcategory::withTrashed()->where('slug', $unique)->exists() || isset($usedSlugs[$unique])) {
                $scopeSlug = $parentSlug !== '' ? $parentSlug : $categorySlug;
                if ($scopeSlug !== '') {
                    $candidate = $this->composeScopedSlug($s, $scopeSlug);
                    if (! Subcategory::withTrashed()->where('slug', $candidate)->exists() && ! isset($usedSlugs[$candidate])) {
                        $unique = $candidate;
                    } else {
                        $n = 2;
                        do {
                            $candidate = $this->composeScopedSlug($s, $scopeSlug, $n);
                            $n++;
                        } while (Subcategory::withTrashed()->where('slug', $candidate)->exists() || isset($usedSlugs[$candidate]));
                        $unique = $candidate;
                    }
                } else {
                    $n = 2;
                    do {
                        $candidate = $s.'-'.$n;
                        $n++;
                    } while (Subcategory::withTrashed()->where('slug', $candidate)->exists() || isset($usedSlugs[$candidate]));
                    $unique = $candidate;
                }
            }
            $usedSlugs[$unique] = true;
            $rows[$idx]['slug'] = $unique;
            unset($rows[$idx]['base_slug']);
        }

        $prepared = $rows;

        return null;
    }

    /**
     * @param  array<int, array{name_ar: string, name_en: string, name_tr: string, slug: string}>  $prepared
     */
    private function persistJsonImportedSubcategories(int $categoryId, ?int $parentId, array $prepared): int
    {
        $nextOrder = (int) (Subcategory::where('category_id', $categoryId)
            ->where('parent_subcategory_id', $parentId)
            ->max('order'));
        $nextOrder = $nextOrder >= 0 ? $nextOrder + 1 : 0;

        $created = 0;
        DB::transaction(function () use ($prepared, $categoryId, $parentId, &$nextOrder, &$created) {
            foreach ($prepared as $row) {
                $subcategory = Subcategory::create([
                    'category_id' => $categoryId,
                    'parent_subcategory_id' => $parentId,
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'name_tr' => $row['name_tr'],
                    'slug' => $row['slug'],
                    'description_ar' => null,
                    'description_en' => null,
                    'description_tr' => null,
                    'order' => $nextOrder,
                    'is_active' => true,
                    'ad_images_mode' => null,
                    'ad_gallery_images' => [],
                ]);
                $nextOrder++;
                $created++;
                ActivityLog::log('subcategory_created', $subcategory);
            }
        });

        return $created;
    }

    private function composeScopedSlug(string $baseSlug, string $scopeSlug, ?int $counter = null): string
    {
        $suffix = $counter ? '-'.$scopeSlug.'-'.$counter : '-'.$scopeSlug;
        $maxBaseLength = max(1, 255 - strlen($suffix));
        $base = substr($baseSlug, 0, $maxBaseLength);

        return rtrim($base, '-') . $suffix;
    }

    public function edit($id)
    {
        $subcategory = Subcategory::with('parent')->findOrFail($id);
        $category = $subcategory->category;

        // Get all descendant IDs to prevent circular references
        $descendantIds = $this->getDescendantIds($id);
        $descendantIds[] = $id; // Also exclude the current subcategory itself

        // Get all subcategories that can be parents (top-level only to keep structure simple)
        $parentSubcategories = Subcategory::where('category_id', $category->id)
            ->whereNull('parent_subcategory_id')
            ->whereNotIn('id', $descendantIds)
            ->get();

        // If the current subcategory has a parent, ensure it's in the list
        // (even if it's not top-level, we need to show it)
        if ($subcategory->parent_subcategory_id) {
            $currentParent = Subcategory::find($subcategory->parent_subcategory_id);
            if ($currentParent && !$parentSubcategories->contains('id', $currentParent->id)) {
                // Add the current parent to the list so it can be displayed
                $parentSubcategories->prepend($currentParent);
            }
        }

        return view('admin.subcategories.edit', compact('subcategory', 'category', 'parentSubcategories'));
    }

    /**
     * Get all descendant IDs of a subcategory (children, grandchildren, etc.)
     */
    private function getDescendantIds($subcategoryId)
    {
        $descendantIds = [];
        $children = Subcategory::where('parent_subcategory_id', $subcategoryId)->get();

        foreach ($children as $child) {
            $descendantIds[] = $child->id;
            $descendantIds = array_merge($descendantIds, $this->getDescendantIds($child->id));
        }

        return $descendantIds;
    }

    public function update(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'required|string|max:255',
            'slug' => 'required|string|unique:subcategories,slug,' . $id,
            'parent_subcategory_id' => 'nullable|exists:subcategories,id',
            'icon' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,gif,bmp,avif,ico|max:4096',
            'ad_images_max' => 'nullable|integer|min:1|max:50',
        ]);

        // Prevent circular references: don't allow setting parent to self or any descendant
        if ($request->parent_subcategory_id) {
            $descendantIds = $this->getDescendantIds($id);
            $descendantIds[] = $id;

            if (in_array($request->parent_subcategory_id, $descendantIds)) {
                return back()->withErrors(['parent_subcategory_id' => 'لا يمكن تعيين قسم فرعي كأب لنفسه أو لأحد أبنائه'])->withInput();
            }
        }

        $modeIn = $request->input('ad_images_mode', '');
        if ($modeIn === '' || $modeIn === 'inherit') {
            $adMode = null;
        } elseif (in_array($modeIn, ['user_upload', 'admin_gallery'], true)) {
            $adMode = $modeIn;
        } else {
            $adMode = null;
        }

        $data = [
            'parent_subcategory_id' => $request->parent_subcategory_id ?: null,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'name_tr' => $request->name_tr,
            'slug' => $request->slug,
            'description_ar' => $request->description_ar,
            'description_en' => $request->description_en,
            'description_tr' => $request->description_tr,
            'order' => $request->order ?? 0,
            'is_active' => $request->has('is_active'),
            'ad_images_mode' => $adMode,
            'ad_images_max' => $request->filled('ad_images_max')
                ? (int) $request->input('ad_images_max')
                : null,
            'ad_gallery_images' => AdGalleryImages::mergeFromRequest(
                $request,
                is_array($subcategory->ad_gallery_images) ? $subcategory->ad_gallery_images : [],
                'subcategories/ad_gallery/'.$subcategory->id
            ),
        ];

        // Handle icon upload (تحويل الصور لـ WebP)
        if ($request->hasFile('icon')) {
            if ($subcategory->icon && \Storage::disk('public')->exists($subcategory->icon)) {
                \Storage::disk('public')->delete($subcategory->icon);
            }
            $data['icon'] = store_image_as_webp($request->file('icon'), 'subcategories/icons');
        }

        $subcategory->update($data);

        ActivityLog::log('subcategory_updated', $subcategory);

        return redirect()->route('admin.categories.show', $subcategory->category_id)
            ->with('success', 'تم تحديث القسم الفرعي بنجاح');
    }

    public function destroy($id)
    {
        $subcategory = Subcategory::withTrashed()->findOrFail($id);
        $redirectTo = url()->previous() ?: route('admin.categories.show', $subcategory->category_id);

        ActivityLog::log('subcategory_deleted', $subcategory);

        if (! $subcategory->trashed()) {
            // Free original slug immediately so it can be reused after soft delete.
            $subcategory->slug = $this->buildDeletedSlug($subcategory->slug, (int) $subcategory->id);
            $subcategory->saveQuietly();
            $subcategory->delete();
        }

        return redirect()->to($redirectTo)
            ->with('success', 'تم حذف القسم الفرعي بنجاح');
    }

    public function restore($id)
    {
        $subcategory = Subcategory::onlyTrashed()->findOrFail($id);
        $categoryId = $subcategory->category_id;

        $subcategory->restore();
        $subcategory->update(['is_active' => true]);

        ActivityLog::log('subcategory_restored', $subcategory);

        return redirect()->route('admin.categories.show', $categoryId)
            ->with('success', 'تم استعادة القسم الفرعي بنجاح');
    }

    public function forceDelete($id)
    {
        $subcategory = Subcategory::onlyTrashed()->findOrFail($id);
        $categoryId = $subcategory->category_id;

        ActivityLog::log('subcategory_force_deleted', $subcategory);

        $subcategory->forceDelete();

        return redirect()->route('admin.categories.show', $categoryId)
            ->with('success', 'تم حذف القسم الفرعي نهائياً');
    }

    private function buildDeletedSlug(string $originalSlug, int $subcategoryId): string
    {
        $suffix = '--deleted-' . $subcategoryId;
        $maxBaseLength = max(1, 255 - strlen($suffix));
        $base = substr($originalSlug, 0, $maxBaseLength);
        $candidate = rtrim($base, '-') . $suffix;

        if (! Subcategory::withTrashed()->where('slug', $candidate)->exists()) {
            return $candidate;
        }

        $counter = 2;
        do {
            $extra = '-' . $counter;
            $maxBaseLength = max(1, 255 - strlen($suffix) - strlen($extra));
            $base = substr($originalSlug, 0, $maxBaseLength);
            $candidate = rtrim($base, '-') . $suffix . $extra;
            $counter++;
        } while (Subcategory::withTrashed()->where('slug', $candidate)->exists());

        return $candidate;
    }

    public function storeCustomField(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        if ($message = CustomFieldsResolver::pathConflictMessage($subcategory)) {
            return back()->withErrors(['error' => $message]);
        }

        $request->validate(CustomFieldValidation::storeFieldRules($request));

        $customFields = $subcategory->custom_fields ?? [];

        foreach ($customFields as $field) {
            if (($field['id'] ?? '') === $request->id) {
                return back()->withErrors(['id' => __('admin.categories.custom_fields.duplicate_id')]);
            }
        }

        $customFields[] = CustomFieldValidation::buildFieldFromRequest($request);
        $subcategory->custom_fields = $customFields;
        $subcategory->save();

        ActivityLog::log('subcategory_custom_field_added', $subcategory, ['field' => end($customFields)]);

        return back()->with('success', __('admin.categories.custom_fields.added_success'));
    }

    public function updateCustomField(Request $request, $id, $fieldIndex)
    {
        $subcategory = Subcategory::findOrFail($id);
        $customFields = $subcategory->custom_fields ?? [];

        if (! isset($customFields[$fieldIndex])) {
            return back()->withErrors(['error' => __('admin.categories.custom_fields.not_found')]);
        }

        $rules = [
            'type' => 'required|in:text,textarea,number,select,checkbox,location,date,car_body_map',
            'label_ar' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'label_tr' => 'nullable|string|max:255',
            'required' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'nullable|array',
        ];

        if ($request->input('type') === 'select') {
            $rules['options'] = 'required|array|min:1';
            $rules['options.*.ar'] = 'required|string';
            $rules['options.*.en'] = 'nullable|string';
            $rules['options.*.tr'] = 'nullable|string';
        }

        $request->validate($rules);

        $customFields[$fieldIndex] = CustomFieldValidation::buildFieldFromRequest(
            $request,
            $customFields[$fieldIndex]
        );

        $subcategory->custom_fields = $customFields;
        $subcategory->save();

        ActivityLog::log('subcategory_custom_field_updated', $subcategory, ['field' => $customFields[$fieldIndex]]);

        return back()->with('success', __('admin.categories.custom_fields.updated_success'));
    }

    public function deleteCustomField($id, $fieldIndex)
    {
        $subcategory = Subcategory::findOrFail($id);
        $customFields = $subcategory->custom_fields ?? [];

        if (! isset($customFields[$fieldIndex])) {
            return back()->withErrors(['error' => __('admin.categories.custom_fields.not_found')]);
        }

        $deletedField = $customFields[$fieldIndex];
        unset($customFields[$fieldIndex]);
        $subcategory->custom_fields = array_values($customFields);
        $subcategory->save();

        ActivityLog::log('subcategory_custom_field_deleted', $subcategory, ['field' => $deletedField]);

        return back()->with('success', __('admin.categories.custom_fields.deleted_success'));
    }

    public function toggleCustomFieldStatus($id, $fieldIndex)
    {
        $subcategory = Subcategory::findOrFail($id);
        $customFields = $subcategory->custom_fields ?? [];

        if (! isset($customFields[$fieldIndex])) {
            return back()->withErrors(['error' => __('admin.categories.custom_fields.not_found')]);
        }

        $customFields[$fieldIndex]['is_active'] = ! ($customFields[$fieldIndex]['is_active'] ?? true);
        $subcategory->custom_fields = $customFields;
        $subcategory->save();

        ActivityLog::log('subcategory_custom_field_status_toggle', $subcategory, ['field' => $customFields[$fieldIndex]]);

        return back()->with('success', __('admin.categories.custom_fields.status_toggled'));
    }

    public function importCustomFieldsFromJson(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        $existing = is_array($subcategory->custom_fields) ? $subcategory->custom_fields : [];
        if ($existing === [] && ($message = CustomFieldsResolver::pathConflictMessage($subcategory))) {
            return back()->withErrors(['import_file' => $message]);
        }

        $request->validate([
            'import_file' => 'required|file|max:5120',
            'import_mode' => 'nullable|in:replace,merge',
        ]);

        $raw = file_get_contents($request->file('import_file')->getRealPath());
        $mode = $request->input('import_mode', 'replace');

        $result = CustomFieldsJsonImporter::parseAndApply($raw, $existing, $mode);

        if ($result['error'] !== null) {
            return back()->withErrors(['import_file' => $result['error']]);
        }

        if ($existing === [] && ($message = CustomFieldsResolver::pathConflictMessage($subcategory))) {
            return back()->withErrors(['import_file' => $message]);
        }

        $subcategory->custom_fields = $result['fields'];
        $subcategory->save();

        ActivityLog::log('subcategory_custom_fields_imported', $subcategory, [
            'mode' => $mode,
            'count' => count($result['fields']),
        ]);

        return back()->with('success', __('admin.categories.custom_fields.json_import_success', [
            'count' => count($result['fields']),
        ]));
    }
}

