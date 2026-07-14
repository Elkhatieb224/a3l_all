<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * صفوف «أقسام رئيسية فيها نتائج بحث» مع مسار توضيحي (مثل المواقع التركية)
 * بناءً على إعلان مطابق ممثل داخل كل قسم.
 */
final class SearchCategoryAdHitRows
{
    /**
     * @param  Collection<int, Category>  $categories
     * @param  array<int, int>  $countsByCategoryId
     * @return list<array<string, mixed>>
     */
    public static function rowsWithBreadcrumbs(
        Builder $baseAdQuery,
        Collection $categories,
        array $countsByCategoryId,
        string $locale
    ): array {
        $categoryIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        if ($categoryIds === []) {
            return [];
        }

        $sample = (clone $baseAdQuery)
            ->whereIn('category_id', $categoryIds)
            ->select(['category_id', 'subcategory_id'])
            ->orderByDesc('published_at')
            ->get();

        $repSubByCat = [];
        foreach ($sample as $row) {
            $cid = (int) $row->category_id;
            if (! isset($repSubByCat[$cid]) && $row->subcategory_id) {
                $repSubByCat[$cid] = (int) $row->subcategory_id;
            }
        }
        foreach ($sample as $row) {
            $cid = (int) $row->category_id;
            if (! isset($repSubByCat[$cid])) {
                $repSubByCat[$cid] = $row->subcategory_id ? (int) $row->subcategory_id : null;
            }
        }

        $allSubs = Subcategory::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->get(['id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr']);

        $subsById = $allSubs->keyBy('id');

        $out = [];
        foreach ($categories as $cat) {
            $cnt = (int) ($countsByCategoryId[$cat->id] ?? 0);
            if ($cnt <= 0) {
                continue;
            }
            $sid = $repSubByCat[(int) $cat->id] ?? null;
            $bc = $sid ? self::subcategoryPathLine((int) $sid, $subsById, $locale) : '';

            $out[] = [
                'kind' => 'category',
                'id' => (int) $cat->id,
                'category_id' => (int) $cat->id,
                'subcategory_id' => null,
                'name' => $cat->getName($locale),
                'name_ar' => $cat->name_ar,
                'name_en' => $cat->name_en,
                'name_tr' => $cat->name_tr,
                'icon' => $cat->icon,
                'matching_ads_count' => $cnt,
                'breadcrumb' => $bc,
            ];
        }

        return $out;
    }

    /** مسار الفروع داخل القسم الرئيسي (بدون تكرار اسم القسم في السطر الأول بالواجهة). */
    private static function subcategoryPathLine(int $leafSubId, Collection $subsById, string $locale): string
    {
        $chain = [];
        $node = $subsById->get($leafSubId);
        $guard = 0;
        while ($node && $guard++ < 40) {
            $chain[] = $node->getName($locale);
            $pid = (int) ($node->parent_subcategory_id ?? 0);
            if ($pid <= 0) {
                break;
            }
            $node = $subsById->get($pid);
        }
        $chain = array_reverse($chain);

        return implode(' > ', $chain);
    }
}
