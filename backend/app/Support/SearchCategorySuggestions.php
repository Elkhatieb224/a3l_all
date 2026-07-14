<?php

namespace App\Support;

use App\Models\Ad;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Collection;

/**
 * اقتراحات بحث الأقسام: فئات رئيسية + فئات فرعية مع مسار (breadcrumb)،
 * حسب تطابق أسماء name_ar/name_en/name_tr فقط (يظهر حتى لو 0 إعلانات).
 */
final class SearchCategorySuggestions
{
    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public static function build(string $q, string $locale, int $minLength, int $limit = 40): array
    {
        $q = trim($q);
        if ($q === '' || mb_strlen($q) < $minLength) {
            return ['data' => [], 'total' => 0];
        }

        $like = '%'.$q.'%';

        /** @var Collection<int, Subcategory> $matchedSubs */
        $matchedSubs = Subcategory::query()
            ->active()
            ->where(function ($w) use ($like) {
                $w->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('name_tr', 'like', $like);
            })
            ->with(['category'])
            ->orderBy('category_id')
            ->orderBy('order')
            ->limit(150)
            ->get();

        /** @var Collection<int, Category> $matchedCats */
        $matchedCats = Category::query()
            ->active()
            ->where(function ($w) use ($like) {
                $w->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('name_tr', 'like', $like);
            })
            ->ordered()
            ->get();

        $categoryIds = $matchedSubs->pluck('category_id')
            ->merge($matchedCats->pluck('id'))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($categoryIds === []) {
            return ['data' => [], 'total' => 0];
        }

        /** @var Collection<int, Subcategory> $allSubsById */
        $allSubsById = Subcategory::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->get(['id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr', 'order'])
            ->keyBy('id');

        $adsCountByCategory = [];
        $aggregatedSubCounts = [];
        foreach ($categoryIds as $cid) {
            $adsCountByCategory[$cid] = (int) Ad::query()
                ->where('status', 'active')
                ->where('category_id', $cid)
                ->count();
            $aggregatedSubCounts[$cid] = Subcategory::aggregatedActiveAdsCountsByCategory((int) $cid);
        }

        $items = [];

        foreach ($matchedSubs as $sub) {
            $cat = $sub->category;
            if (! $cat || ! $cat->is_active) {
                continue;
            }
            $breadcrumb = self::breadcrumbAboveMatch($sub->id, $allSubsById, $cat, $locale);
            $cnt = (int) ($aggregatedSubCounts[$sub->category_id][$sub->id] ?? 0);
            $items[] = [
                'kind' => 'subcategory',
                'id' => (int) $cat->id,
                'category_id' => (int) $sub->category_id,
                'subcategory_id' => (int) $sub->id,
                'name' => $sub->getName($locale),
                'name_ar' => $sub->name_ar,
                'name_en' => $sub->name_en,
                'name_tr' => $sub->name_tr,
                'breadcrumb' => $breadcrumb,
                'matching_ads_count' => $cnt,
            ];
        }

        foreach ($matchedCats as $cat) {
            $cnt = (int) ($adsCountByCategory[$cat->id] ?? 0);
            $items[] = [
                'kind' => 'category',
                'id' => (int) $cat->id,
                'category_id' => (int) $cat->id,
                'subcategory_id' => null,
                'name' => $cat->getName($locale),
                'name_ar' => $cat->name_ar,
                'name_en' => $cat->name_en,
                'name_tr' => $cat->name_tr,
                'breadcrumb' => '',
                'matching_ads_count' => $cnt,
            ];
        }

        usort($items, function (array $a, array $b): int {
            $ka = ($a['kind'] ?? '') === 'subcategory' ? 0 : 1;
            $kb = ($b['kind'] ?? '') === 'subcategory' ? 0 : 1;
            if ($ka !== $kb) {
                return $ka <=> $kb;
            }
            $alen = mb_strlen((string) ($a['breadcrumb'] ?? ''));
            $blen = mb_strlen((string) ($b['breadcrumb'] ?? ''));
            if ($alen !== $blen) {
                return $blen <=> $alen;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $items = array_slice($items, 0, $limit);
        $total = (int) array_sum(array_column($items, 'matching_ads_count'));

        return ['data' => $items, 'total' => $total];
    }

    private static function breadcrumbAboveMatch(
        int $subcategoryId,
        Collection $allSubsById,
        Category $category,
        string $locale
    ): string {
        $parts = [$category->getName($locale)];
        $node = $allSubsById->get($subcategoryId);
        if (! $node) {
            return implode(' > ', $parts);
        }
        $ancestorNames = [];
        $parentId = $node->parent_subcategory_id ? (int) $node->parent_subcategory_id : null;
        $guard = 0;
        while ($parentId && $guard++ < 40) {
            $p = $allSubsById->get($parentId);
            if (! $p) {
                break;
            }
            $ancestorNames[] = $p->getName($locale);
            $parentId = $p->parent_subcategory_id ? (int) $p->parent_subcategory_id : null;
        }
        $ancestorNames = array_reverse($ancestorNames);
        foreach ($ancestorNames as $n) {
            $parts[] = $n;
        }

        return implode(' > ', $parts);
    }
}
