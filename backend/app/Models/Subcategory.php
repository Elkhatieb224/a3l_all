<?php

namespace App\Models;

use App\Support\LocalizedDisplayName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Subcategory extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function () {
            self::bumpApiCacheVersions();
        });

        static::deleted(function () {
            self::bumpApiCacheVersions();
        });

        static::restored(function () {
            self::bumpApiCacheVersions();
        });

        static::forceDeleted(function () {
            self::bumpApiCacheVersions();
        });
    }

    protected $fillable = [
        'category_id',
        'parent_subcategory_id',
        'name_ar',
        'name_en',
        'name_tr',
        'slug',
        'icon',
        'description_ar',
        'description_en',
        'description_tr',
        'order',
        'is_active',
        'ad_images_mode',
        'ad_images_max',
        'ad_gallery_images',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ad_images_max' => 'integer',
        'custom_fields' => 'array',
        'ad_gallery_images' => 'array',
    ];

    public function getDescription($locale = 'ar')
    {
        return $this->{"description_$locale"} ?? $this->description_ar;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function children()
    {
        return $this->hasMany(Subcategory::class, 'parent_subcategory_id');
    }

    public function parent()
    {
        return $this->belongsTo(Subcategory::class, 'parent_subcategory_id');
    }

    public function getName($locale = 'ar')
    {
        $name = $this->{"name_$locale"} ?? $this->name_ar;

        return LocalizedDisplayName::format((string) $name, $locale);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * IDs of this subcategory and all descendants (for ads count)
     */
    public function getDescendantIds(): array
    {
        $rootId = (int) $this->id;
        $categoryId = $this->category_id;
        if ($categoryId === null || $categoryId === '') {
            $categoryId = static::query()->where('id', $rootId)->value('category_id');
        }
        $categoryId = (int) $categoryId;
        if ($categoryId < 1) {
            return [$rootId];
        }

        // Old implementation used recursive DB queries (N+1) and can be extremely slow on large trees.
        // This implementation loads the category tree once and traverses it in memory.
        $rows = static::query()
            ->where('category_id', $categoryId)
            ->get(['id', 'parent_subcategory_id']);

        $childrenByParent = [];
        foreach ($rows as $row) {
            if ($row->parent_subcategory_id !== null) {
                $childrenByParent[(int) $row->parent_subcategory_id][] = (int) $row->id;
            }
        }

        $ids = [];
        $stack = [$rootId];
        while (!empty($stack)) {
            $current = array_pop($stack);
            if (isset($ids[$current])) {
                continue;
            }
            $ids[$current] = true;
            foreach ($childrenByParent[$current] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Count active ads in this subcategory and all descendants
     */
    public function getAdsCountIncludingDescendants(): int
    {
        $ids = $this->getDescendantIds();
        return Ad::whereIn('subcategory_id', $ids)
            ->where('status', 'active')
            ->count();
    }

    /**
     * لكل فرعية في القسم: عدد الإعلانات النشطة المسجّلة على هذه الفرعية أو أي فئة فرعية تحتها.
     * يطابق منطق تصفية القوائم في AdController (whereIn subcategory_id + أحفاد).
     *
     * @return array<int, int>  [ subcategory_id => count ]
     */
    public static function aggregatedActiveAdsCountsByCategory(int $categoryId): array
    {
        $rawCounts = Ad::query()
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->whereNotNull('subcategory_id')
            ->selectRaw('subcategory_id, COUNT(*) as cnt')
            ->groupBy('subcategory_id')
            ->pluck('cnt', 'subcategory_id');

        $directCounts = [];
        foreach ($rawCounts as $sid => $cnt) {
            $directCounts[(int) $sid] = (int) $cnt;
        }

        $rows = static::query()
            ->where('category_id', $categoryId)
            ->get(['id', 'parent_subcategory_id']);

        $childrenByParent = [];
        foreach ($rows as $row) {
            if ($row->parent_subcategory_id !== null) {
                $childrenByParent[(int) $row->parent_subcategory_id][] = (int) $row->id;
            }
        }

        $memo = [];
        $aggregate = function (int $id) use (&$memo, &$aggregate, $childrenByParent, $directCounts): int {
            if (array_key_exists($id, $memo)) {
                return $memo[$id];
            }
            $sum = (int) ($directCounts[$id] ?? 0);
            foreach ($childrenByParent[$id] ?? [] as $childId) {
                $sum += $aggregate($childId);
            }
            $memo[$id] = $sum;

            return $sum;
        };

        $out = [];
        foreach ($rows as $row) {
            $sid = (int) $row->id;
            $out[$sid] = $aggregate($sid);
        }

        return $out;
    }

    /**
     * @param  iterable<Subcategory>  $nodes
     * @param  array<int, int>  $counts  من aggregatedActiveAdsCountsByCategory
     */
    public static function hydrateSubtreeAdsCounts(iterable $nodes, array $counts): void
    {
        foreach ($nodes as $node) {
            $node->setAttribute('ads_count', $counts[(int) $node->id] ?? 0);
            if ($node->relationLoaded('children') && $node->children->isNotEmpty()) {
                static::hydrateSubtreeAdsCounts($node->children, $counts);
            }
        }
    }

    /**
     * Calculate the depth/level of this subcategory in the hierarchy
     */
    public function getLevel()
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    private static function bumpApiCacheVersions(): void
    {
        Cache::forever('api:categories:version', (int) Cache::get('api:categories:version', 1) + 1);
        Cache::forever('api:home:categories:version', (int) Cache::get('api:home:categories:version', 1) + 1);
    }
}

