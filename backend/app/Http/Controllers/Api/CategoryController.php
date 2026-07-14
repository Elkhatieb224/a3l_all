<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SubcategoryResource;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    private function getCategoriesCacheVersion(): int
    {
        $version = (int) Cache::get('api:categories:version', 1);
        if ($version < 1) {
            $version = 1;
            Cache::forever('api:categories:version', $version);
        }
        return $version;
    }
    public function index()
    {
        $locale = app()->getLocale();
        $version = $this->getCategoriesCacheVersion();
        $cacheKey = "api:categories:index:v1:ver:{$version}:{$locale}";

        $payload = Cache::remember($cacheKey, now()->addHour(), function () {
            $categories = Category::query()
                ->select([
                    'id',
                    'name_ar',
                    'name_en',
                    'name_tr',
                    'icon',
                    'is_active',
                    'custom_fields',
                    'ad_images_mode',
                    'ad_images_max',
                    'ad_gallery_images',
                    'order',
                    'created_at',
                    'updated_at',
                ])
                ->withCount([
                    'subcategories as subcategories_count',
                ])
                ->with(['subcategories' => function ($query) {
                    $query->select([
                        'id',
                        'category_id',
                        'parent_subcategory_id',
                        'name_ar',
                        'name_en',
                        'name_tr',
                        'icon',
                        'is_active',
                        'custom_fields',
                        'ad_images_mode',
                        'ad_images_max',
                        'ad_gallery_images',
                        'order',
                        'created_at',
                        'updated_at',
                    ])
                        ->where('is_active', true)
                        ->whereNull('parent_subcategory_id');
                }])
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
            return [
                'success' => true,
                'data' => CategoryResource::collection($categories)->resolve(),
            ];
        });
        $etag = '"' . sha1(json_encode($payload, JSON_UNESCAPED_UNICODE)) . '"';
        if (trim((string) request()->header('If-None-Match')) === $etag) {
            return response()->noContent(304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=1200');
        }
        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=1200');
    }
    public function show($id)
    {
        $category = Category::query()
            ->withCount([
                'subcategories as subcategories_count',
                'ads as ads_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->with(['subcategories' => function ($query) {
                $query->where('is_active', true)
                    ->with(['children' => function ($childrenQuery) {
                        $childrenQuery->where('is_active', true);
                    }]);
            }])
            ->where('is_active', true)
            ->findOrFail($id);

        $rollup = Subcategory::aggregatedActiveAdsCountsByCategory((int) $category->id);
        Subcategory::hydrateSubtreeAdsCounts($category->subcategories, $rollup);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }
    public function subcategories($categoryId)
    {
        $categoryId = (int) $categoryId;

        $subcategories = Subcategory::query()
            ->select([
                'id',
                'category_id',
                'parent_subcategory_id',
                'name_ar',
                'name_en',
                'name_tr',
                'icon',
                'is_active',
                'custom_fields',
                'ad_images_mode',
                'ad_images_max',
                'ad_gallery_images',
                'order',
                'created_at',
                'updated_at',
            ])
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->whereNull('parent_subcategory_id')
            ->with(['children' => function ($query) {
                $query->select([
                    'id',
                    'category_id',
                    'parent_subcategory_id',
                    'name_ar',
                    'name_en',
                    'name_tr',
                    'icon',
                    'is_active',
                    'custom_fields',
                    'ad_images_mode',
                    'ad_images_max',
                    'ad_gallery_images',
                    'order',
                    'created_at',
                    'updated_at',
                ])
                    ->where('is_active', true);
            }])
            ->orderBy('order')
            ->get();

        $rollup = Subcategory::aggregatedActiveAdsCountsByCategory($categoryId);
        Subcategory::hydrateSubtreeAdsCounts($subcategories, $rollup);

        $payload = [
            'success' => true,
            'data' => SubcategoryResource::collection($subcategories)->resolve(),
        ];

        return response()->json($payload)
            ->withHeaders([
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

    public function subcategoryChildren($subcategoryId)
    {
        $subcategory = Subcategory::where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true);
            }])
            ->findOrFail($subcategoryId);

        $rollup = Subcategory::aggregatedActiveAdsCountsByCategory((int) $subcategory->category_id);
        Subcategory::hydrateSubtreeAdsCounts($subcategory->children, $rollup);

        return response()->json([
            'success' => true,
            'data' => SubcategoryResource::collection($subcategory->children),
        ])->withHeaders([
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }


    public function subcategoryShow(int $id)
    {
        $subcategory = Subcategory::query()
            ->where('is_active', true)
            ->with('category:id,custom_fields')
            ->findOrFail($id);

        $rollup = Subcategory::aggregatedActiveAdsCountsByCategory((int) $subcategory->category_id);
        $subcategory->setAttribute('ads_count', $rollup[(int) $subcategory->id] ?? 0);

        $resolved = \App\Support\CustomFieldsResolver::resolve($subcategory->category, $subcategory);
        $payload = (new SubcategoryResource($subcategory))->resolve();
        $payload['resolved_custom_fields'] = $resolved['fields'];
        $payload['custom_fields_source'] = $resolved['source'];
        $payload['custom_fields_source_subcategory_id'] = $resolved['source_subcategory_id'];
        $payload['custom_fields_source_category_id'] = $resolved['source_category_id'];

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}
