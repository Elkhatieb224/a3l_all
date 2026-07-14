<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Ad;
use App\Support\CustomFieldsFilterSupport;
use App\Support\CustomFieldsResolver;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::active()
            ->with(['subcategories' => function($q) {
                $q->whereNull('parent_subcategory_id')
                  ->active()
                  ->withCount('ads')
                  ->orderBy('order');
            }])
            ->withCount(['subcategories' => function($q) {
                $q->whereNull('parent_subcategory_id')->active();
            }, 'ads'])
            ->ordered()
            ->get();

        return view('frontend.categories.index', compact('categories'));
    }

    public function show(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)
            ->active()
            ->with(['subcategories' => function($q) {
                $q->whereNull('parent_subcategory_id')
                  ->active()
                  ->orderBy('order');
            }])
            ->firstOrFail();

        $this->attachDescendantInclusiveActiveAdsCounts($category->subcategories, $category->id);

        $adsQuery = Ad::where('category_id', $category->id)
            ->where('status', 'active')
            ->with(['subcategory', 'user']);

        $schemaFields = CustomFieldsResolver::resolveActiveFields($category, null);
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($schemaFields);
        $filterFields = CustomFieldsFilterSupport::resolveFilterableFields($schemaFields);
        $priceFilterLabel = CustomFieldsFilterSupport::resolvePriceFilterLabel($schemaFields);
        $this->applyPriceFilter($adsQuery, $request, $category, null, $schemaFields);

        $this->applyCustomFieldFiltersToQuery($adsQuery, $request, $category, null, $filterFields, $priceFieldId);

        $ads = $adsQuery->latest('published_at')->paginate(20)->withQueryString();

        return view('frontend.categories.show', compact('category', 'ads', 'filterFields', 'priceFieldId', 'priceFilterLabel'));
    }

    public function showSubcategory(Request $request, $categorySlug, $subcategorySlug)
    {
        $category = Category::where('slug', $categorySlug)
            ->active()
            ->firstOrFail();

        $subcategory = Subcategory::where('slug', $subcategorySlug)
            ->where('category_id', $category->id)
            ->active()
            ->with(['category', 'children' => function($q) {
                $q->active()->orderBy('order');
            }])
            ->firstOrFail();

        $this->attachDescendantInclusiveActiveAdsCounts($subcategory->children, $category->id);

        $subcategoryIds = $subcategory->getDescendantIds();
        $adsQuery = Ad::whereIn('subcategory_id', $subcategoryIds)
            ->where('status', 'active')
            ->with(['user']);

        $schemaFields = CustomFieldsResolver::resolveActiveFields($category, $subcategory);
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($schemaFields);
        $filterFields = CustomFieldsFilterSupport::resolveFilterableFields($schemaFields);
        $priceFilterLabel = CustomFieldsFilterSupport::resolvePriceFilterLabel($schemaFields);
        $this->applyPriceFilter($adsQuery, $request, $category, $subcategory, $schemaFields);
        $this->applyCustomFieldFiltersToQuery($adsQuery, $request, $category, $subcategory, $filterFields, $priceFieldId);

        $ads = $adsQuery->latest('published_at')->paginate(20)->withQueryString();

        return view('frontend.categories.subcategory', compact('category', 'subcategory', 'ads', 'filterFields', 'priceFieldId', 'priceFilterLabel'));
    }

    /**
     * تعيين ads_count لكل قسم فرعي = إجمالي الإعلانات النشطة في ذلك القسم وجميع الأبناء (متوافق مع منطق عرض الإعلانات).
     *
     * @param  \Illuminate\Support\Collection<int, Subcategory>|\Illuminate\Database\Eloquent\Collection<int, Subcategory>  $subcategories
     */
    private function attachDescendantInclusiveActiveAdsCounts($subcategories, int $categoryId): void
    {
        $subcategories = collect($subcategories);
        if ($subcategories->isEmpty()) {
            return;
        }

        $allRows = Subcategory::query()
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->get(['id', 'parent_subcategory_id']);

        $childrenByParent = [];
        foreach ($allRows as $row) {
            if ($row->parent_subcategory_id !== null) {
                $childrenByParent[$row->parent_subcategory_id][] = $row->id;
            }
        }

        $descendantsMemo = [];
        $resolveDescendants = function (int $id) use (&$resolveDescendants, &$descendantsMemo, $childrenByParent): array {
            if (isset($descendantsMemo[$id])) {
                return $descendantsMemo[$id];
            }
            $out = [$id];
            foreach ($childrenByParent[$id] ?? [] as $cid) {
                $out = array_merge($out, $resolveDescendants((int) $cid));
            }
            return $descendantsMemo[$id] = $out;
        };

        $allDescendantIds = [];
        foreach ($subcategories as $sub) {
            $allDescendantIds = array_merge($allDescendantIds, $resolveDescendants((int) $sub->id));
        }
        $allDescendantIds = array_values(array_unique($allDescendantIds));

        $countsBySubId = Ad::query()
            ->where('status', 'active')
            ->whereIn('subcategory_id', $allDescendantIds)
            ->selectRaw('subcategory_id, COUNT(*) as c')
            ->groupBy('subcategory_id')
            ->pluck('c', 'subcategory_id');

        foreach ($subcategories as $sub) {
            $total = 0;
            foreach ($resolveDescendants((int) $sub->id) as $sid) {
                $total += (int) ($countsBySubId[$sid] ?? 0);
            }
            $sub->setAttribute('ads_count', $total);
        }
    }

    /**
     * تطبيق فلتر السعر: يشمل الإعلانات التي لها سعر في العمود price أو في custom_fields.
     */
    private function applyPriceFilter(
        Builder $query,
        Request $request,
        Category $category,
        ?Subcategory $subcategory = null,
        ?array $schemaFields = null
    ): void {
        $schemaFields ??= CustomFieldsResolver::resolveActiveFields($category, $subcategory);
        $priceFieldId = CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($schemaFields);
        [$minPrice, $maxPrice] = CustomFieldsFilterSupport::normalizedMinMaxPrice($request, $priceFieldId);

        if ($minPrice === null && $maxPrice === null) {
            return;
        }

        $priceField = $priceFieldId
            ? CustomFieldsFilterSupport::findFieldById($schemaFields, $priceFieldId)
            : null;
        $usesCurrencyObject = $priceField && CustomFieldsFilterSupport::customFieldPriceUsesCurrencyObject($priceField);

        $query->where(function (Builder $q) use ($minPrice, $maxPrice, $priceFieldId, $usesCurrencyObject) {
            $q->where(function (Builder $q2) use ($minPrice, $maxPrice) {
                $q2->whereNotNull('price');
                if ($minPrice !== null) {
                    $q2->where('price', '>=', $minPrice);
                }
                if ($maxPrice !== null) {
                    $q2->where('price', '<=', $maxPrice);
                }
            });

            if ($priceFieldId) {
                $q->orWhere(function (Builder $q2) use ($minPrice, $maxPrice, $priceFieldId, $usesCurrencyObject) {
                    if ($usesCurrencyObject) {
                        $cfPath = "custom_fields->{$priceFieldId}->value";
                        $q2->whereNotNull($cfPath);
                        if ($minPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}.value')) + 0) >= ?", [$minPrice]);
                        }
                        if ($maxPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}.value')) + 0) <= ?", [$maxPrice]);
                        }
                    } else {
                        $cfPath = "custom_fields->{$priceFieldId}";
                        $q2->whereNotNull($cfPath);
                        if ($minPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}')) + 0) >= ?", [$minPrice]);
                        }
                        if ($maxPrice !== null) {
                            $q2->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.{$priceFieldId}')) + 0) <= ?", [$maxPrice]);
                        }
                    }
                });
            }
        });
    }

    /**
     * تطبيق فلاتر الحقول المخصصة على استعلام الإعلانات.
     *
     * صيغة البارامترات:
     * - number: cf_{id}_min, cf_{id}_max
     * - select: cf_{id}
     * - checkbox: cf_{id}=1
     */
    private function applyCustomFieldFiltersToQuery(
        Builder $query,
        Request $request,
        Category $category,
        ?Subcategory $subcategory = null,
        ?array $fields = null,
        ?string $priceFieldId = null
    ): void {
        if ($fields === null) {
            $schemaFields = CustomFieldsResolver::resolveActiveFields($category, $subcategory);
            $priceFieldId ??= CustomFieldsFilterSupport::resolvePrimaryPriceFieldId($schemaFields);
            $fields = CustomFieldsFilterSupport::resolveFilterableFields($schemaFields);
        }

        if (empty($fields)) {
            return;
        }

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $id = $field['id'] ?? null;
            $type = $field['type'] ?? 'text';

            if (!$id || !is_string($id)) {
                continue;
            }

            if ($priceFieldId !== null && $id === $priceFieldId) {
                continue;
            }

            $minParam = $request->input("cf_{$id}_min");
            $maxParam = $request->input("cf_{$id}_max");
            $eqParam = $request->input("cf_{$id}");

            if ($type === 'number') {
                $jsonPath = "custom_fields->$id";
                if (!empty($field['show_currency'])) {
                    $jsonPath .= '->value';
                }

                if ($minParam !== null && $minParam !== '') {
                    $query->where($jsonPath, '>=', (float) $minParam);
                }
                if ($maxParam !== null && $maxParam !== '') {
                    $query->where($jsonPath, '<=', (float) $maxParam);
                }
            } elseif ($type === 'select') {
                if ($eqParam !== null && $eqParam !== '') {
                    $options = $field['options'] ?? [];
                    $valuesToMatch = [$eqParam];
                    foreach ($options as $opt) {
                        if (!is_array($opt)) {
                            continue;
                        }
                        $optVal = $opt['id'] ?? ($opt['ar'] ?? ($opt['en'] ?? ($opt['tr'] ?? null)));
                        if ((string) $optVal === (string) $eqParam) {
                            $valuesToMatch = array_values(array_unique(array_filter([
                                $eqParam,
                                $opt['ar'] ?? null,
                                $opt['en'] ?? null,
                                $opt['tr'] ?? null,
                            ])));
                            break;
                        }
                    }
                    $query->where(function (Builder $q) use ($id, $valuesToMatch) {
                        $q->whereIn("custom_fields->$id", $valuesToMatch);
                    });
                }
            } elseif ($type === 'checkbox') {
                if ($eqParam !== null && $eqParam !== '') {
                    $value = strtolower((string) $eqParam);
                    $boolVal = in_array($value, ['1', 'true', 'yes', 'on'], true);
                    $query->where("custom_fields->$id", $boolVal);
                }
            } elseif ($type === 'date') {
                CustomFieldsFilterSupport::applyDateAfterFilter(
                    $query,
                    $id,
                    $request->input("cf_{$id}_after")
                );
            } elseif ($type === 'car_body_map') {
                CustomFieldsFilterSupport::applyCarBodyMapPartFilters($query, $id, $request);
            }
        }
    }
}
