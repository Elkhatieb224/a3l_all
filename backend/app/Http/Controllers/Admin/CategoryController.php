<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Support\AdGalleryImages;
use App\Support\CustomFieldsJsonImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['subcategories', 'ads'])
            ->orderBy('order')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function show(Request $request, $id)
    {
        $category = Category::with(['subcategories' => function($q) use ($request) {
            $q->whereNull('parent_subcategory_id')
              ->withTrashed()
              ->withCount('ads')
              ->when($request->search, function($query) use ($request) {
                  $query->where(function($q) use ($request) {
                      $q->where('name_ar', 'like', '%' . $request->search . '%')
                        ->orWhere('name_en', 'like', '%' . $request->search . '%')
                        ->orWhere('name_tr', 'like', '%' . $request->search . '%');
                  });
              })
              ->orderBy('order');
        }])->withCount(['subcategories' => function($q) {
            $q->whereNull('parent_subcategory_id');
        }, 'ads'])->findOrFail($id);

        return view('admin.categories.show', compact('category'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            // SVG غير مدعوم في تطبيق Flutter (ملفات Illustrator تستنزف الذاكرة)
            'icon' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:4096',
            'order' => 'nullable|integer',
            'ad_images_max' => 'nullable|integer|min:1|max:50',
            'enable_negotiation' => 'nullable|boolean',
        ]);

        $data = $request->except('icon', 'is_active', 'ad_gallery_new', 'ad_gallery_remove', 'ad_images_mode');
        
        // Handle is_active checkbox
        $data['is_active'] = $request->has('is_active');
        $data['enable_negotiation'] = $request->has('enable_negotiation');
        
        // Handle icon upload (تحويل الصور لـ WebP)
        if ($request->hasFile('icon')) {
            $data['icon'] = store_image_as_webp($request->file('icon'), 'categories/icons');
        }

        $category = Category::create($data);

        $category->ad_images_mode = in_array($request->input('ad_images_mode'), ['user_upload', 'admin_gallery'], true)
            ? $request->input('ad_images_mode')
            : 'user_upload';
        $category->ad_images_max = $request->filled('ad_images_max')
            ? (int) $request->input('ad_images_max')
            : null;
        $category->ad_gallery_images = AdGalleryImages::mergeFromRequest(
            $request,
            [],
            'categories/ad_gallery/'.$category->id
        );
        $category->save();

        ActivityLog::log('category_created', $category);

        return redirect()->route('admin.categories.index')
            ->with('success', 'تم إضافة القسم بنجاح');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug,' . $id,
            'icon' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:4096',
            'order' => 'nullable|integer',
            'ad_images_max' => 'nullable|integer|min:1|max:50',
            'enable_negotiation' => 'nullable|boolean',
        ]);

        $oldData = $category->toArray();
        $data = $request->except('icon', 'is_active', 'ad_gallery_new', 'ad_gallery_remove');
        
        // Handle is_active checkbox
        $data['is_active'] = $request->has('is_active');
        $data['enable_negotiation'] = $request->has('enable_negotiation');

        $data['ad_images_mode'] = in_array($request->input('ad_images_mode'), ['user_upload', 'admin_gallery'], true)
            ? $request->input('ad_images_mode')
            : 'user_upload';
        $data['ad_images_max'] = $request->filled('ad_images_max')
            ? (int) $request->input('ad_images_max')
            : null;
        $data['ad_gallery_images'] = AdGalleryImages::mergeFromRequest(
            $request,
            is_array($category->ad_gallery_images) ? $category->ad_gallery_images : [],
            'categories/ad_gallery/'.$category->id
        );
        
        // Handle icon upload (تحويل الصور لـ WebP)
        if ($request->hasFile('icon')) {
            if ($category->icon && \Storage::disk('public')->exists($category->icon)) {
                \Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = store_image_as_webp($request->file('icon'), 'categories/icons');
        }

        $category->update($data);

        ActivityLog::log('category_updated', $category, [
            'old' => $oldData,
            'new' => $category->toArray()
        ]);

        return redirect()->route('admin.categories.show', $category->id)
            ->with('success', __('admin.categories.updated_success'))
            ->withFragment('category-ad-images-summary');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        ActivityLog::log('category_deleted', $category);
        
        $category->delete();

        return back()->with('success', 'تم حذف القسم بنجاح');
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        ActivityLog::log('category_status_toggle', $category);

        return back()->with('success', 'تم تغيير حالة القسم بنجاح');
    }

    // Custom Fields Management
    public function storeCustomField(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $rules = [
            'id' => 'required|string|max:100',
            'type' => 'required|in:text,textarea,number,select,checkbox,location,date,car_body_map',
            'label_ar' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'label_tr' => 'nullable|string|max:255',
            'required' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'nullable|array',
        ];

        // إذا كان نوع الحقل قائمة، يجب إدخال خيارات
        if ($request->input('type') === 'select') {
            $rules['options'] = 'required|array|min:1';
            $rules['options.*.ar'] = 'required|string';
            $rules['options.*.en'] = 'nullable|string';
            $rules['options.*.tr'] = 'nullable|string';
        }

        $request->validate($rules);

        $customFields = $category->custom_fields ?? [];
        
        // Check if field ID already exists
        foreach ($customFields as $field) {
            if (($field['id'] ?? '') === $request->id) {
                return back()->withErrors(['id' => __('admin.categories.custom_fields.duplicate_id')]);
            }
        }

        $newField = [
            'id' => $request->id,
            'type' => $request->type,
            'label' => [
                'ar' => $request->label_ar,
                'en' => $request->label_en ?? $request->label_ar,
                'tr' => $request->label_tr ?? $request->label_ar,
            ],
            'required' => $request->boolean('required'),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->type === 'select' && $request->has('options')) {
            $newField['options'] = $request->options;
        }

        if ($request->type === 'number') {
            $newField['min'] = $request->input('min');
            $newField['max'] = $request->input('max');
            $newField['step'] = $request->input('step', 1);
            $newField['show_currency'] = $request->boolean('show_currency');
            $newField['allow_tbd'] = $request->boolean('allow_tbd');
        }

        $customFields[] = $newField;
        $category->custom_fields = $customFields;
        $category->save();

        ActivityLog::log('category_custom_field_added', $category, ['field' => $newField]);

        return back()->with('success', 'تم إضافة الحقل بنجاح');
    }

    public function updateCustomField(Request $request, $id, $fieldIndex)
    {
        $category = Category::findOrFail($id);
        $customFields = $category->custom_fields ?? [];
        
        if (!isset($customFields[$fieldIndex])) {
            return back()->withErrors(['error' => 'الحقل غير موجود']);
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

        $field = $customFields[$fieldIndex];
        $field['type'] = $request->type;
        $field['label'] = [
            'ar' => $request->label_ar,
            'en' => $request->label_en ?? $request->label_ar,
            'tr' => $request->label_tr ?? $request->label_ar,
        ];
        $field['required'] = $request->boolean('required');
        $field['is_active'] = $request->boolean('is_active', true);

        if ($request->type === 'select' && $request->has('options')) {
            $field['options'] = $request->options;
        } else {
            unset($field['options']);
        }

        if ($request->type === 'number') {
            $field['min'] = $request->input('min');
            $field['max'] = $request->input('max');
            $field['step'] = $request->input('step', 1);
            $field['show_currency'] = $request->boolean('show_currency');
            $field['allow_tbd'] = $request->boolean('allow_tbd');
        } else {
            unset($field['min'], $field['max'], $field['step'], $field['show_currency'], $field['allow_tbd']);
        }

        $customFields[$fieldIndex] = $field;
        $category->custom_fields = $customFields;
        $category->save();

        ActivityLog::log('category_custom_field_updated', $category, ['field' => $field]);

        return back()->with('success', 'تم تحديث الحقل بنجاح');
    }

    public function deleteCustomField($id, $fieldIndex)
    {
        $category = Category::findOrFail($id);
        $customFields = $category->custom_fields ?? [];
        
        if (!isset($customFields[$fieldIndex])) {
            return back()->withErrors(['error' => 'الحقل غير موجود']);
        }

        $deletedField = $customFields[$fieldIndex];
        unset($customFields[$fieldIndex]);
        $customFields = array_values($customFields); // Re-index array
        
        $category->custom_fields = $customFields;
        $category->save();

        ActivityLog::log('category_custom_field_deleted', $category, ['field' => $deletedField]);

        return back()->with('success', 'تم حذف الحقل بنجاح');
    }

    public function toggleCustomFieldStatus($id, $fieldIndex)
    {
        $category = Category::findOrFail($id);
        $customFields = $category->custom_fields ?? [];
        
        if (!isset($customFields[$fieldIndex])) {
            return back()->withErrors(['error' => 'الحقل غير موجود']);
        }

        $customFields[$fieldIndex]['is_active'] = !($customFields[$fieldIndex]['is_active'] ?? true);
        $category->custom_fields = $customFields;
        $category->save();

        ActivityLog::log('category_custom_field_status_toggle', $category, ['field' => $customFields[$fieldIndex]]);

        return back()->with('success', 'تم تغيير حالة الحقل بنجاح');
    }

    public function importCustomFieldsFromJson(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'import_file' => 'required|file|max:5120',
            'import_mode' => 'nullable|in:replace,merge',
        ]);

        $raw = file_get_contents($request->file('import_file')->getRealPath());
        $mode = $request->input('import_mode', 'replace');

        $result = CustomFieldsJsonImporter::parseAndApply(
            $raw,
            is_array($category->custom_fields) ? $category->custom_fields : [],
            $mode
        );

        if ($result['error'] !== null) {
            return back()->withErrors(['import_file' => $result['error']]);
        }

        $category->custom_fields = $result['fields'];
        $category->save();

        ActivityLog::log('category_custom_fields_imported', $category, [
            'mode' => $mode,
            'count' => count($result['fields']),
        ]);

        return back()->with('success', __('admin.categories.custom_fields.json_import_success', [
            'count' => count($result['fields']),
        ]));
    }
}

