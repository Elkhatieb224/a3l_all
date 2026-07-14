<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdListResource;
use App\Models\Ad;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * خادم مخصص للصفحة الرئيسية في التطبيق.
     * يعيد بيانات خفيفة فقط: أقسام مختصرة + إعلانات مميزة/عاجلة/أحدث الإعلانات.
     *
     * التخزين المؤقت مفتاحه يتضمّن api:home:ads:version (يُحدَّث عند حفظ/حذف إعلان) حتى لا تُقدَّم لقطة قديمة بعد نشر إعلانات جديدة.
     */
    public function index(Request $request)
    {
        $version = (int) Cache::get('api:home:ads:version', 1);
        $locale = app()->getLocale();
        $cacheKey = "api:home:v2:{$version}:{$locale}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(2), fn () => $this->buildHomePayload());

        $etag = '"'.sha1((string) json_encode($payload, JSON_UNESCAPED_UNICODE)).'"';
        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response()->noContent(304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=180');
        }

        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=180');
    }

    /**
     * @return array{success: bool, data: array<string, mixed>}
     */
    private function buildHomePayload(): array
    {
        return [
            'success' => true,
            'data' => [
                'categories' => $this->buildHomeCategories(),
                'featured_ads' => $this->buildHomeAdsResolved(fn (Builder $q) => $q->where('is_featured', true), 10),
                'urgent_ads' => $this->buildHomeAdsResolved(fn (Builder $q) => $q->where('is_urgent', true), 10),
                'latest_ads' => $this->buildHomeAdsResolved(fn (Builder $q) => $q, 6),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildHomeCategories(): array
    {
        $adsCounts = Ad::query()
            ->active()
            ->selectRaw('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id');

        return Category::query()
            ->active()
            ->ordered()
            ->select([
                'id',
                'name_ar',
                'name_en',
                'name_tr',
                'icon',
                'order',
            ])
            ->get()
            ->map(function (Category $cat) use ($adsCounts) {
                $locale = app()->getLocale();
                $adsCount = (int) ($adsCounts[$cat->id] ?? 0);

                return [
                    'id' => $cat->id,
                    'name' => $cat->getName($locale),
                    'name_ar' => $cat->name_ar,
                    'name_en' => $cat->name_en,
                    'name_tr' => $cat->name_tr,
                    'icon' => $cat->icon ? asset('storage/'.$cat->icon) : null,
                    'order' => $cat->order,
                    'ads_count' => $adsCount,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  \Closure(Builder): Builder  $scope
     * @return list<array<string, mixed>>
     */
    private function buildHomeAdsResolved(\Closure $scope, int $limit): array
    {
        $baseAdsQuery = Ad::query()
            ->active()
            ->select([
                'id',
                'uid',
                'user_id',
                'category_id',
                'subcategory_id',
                'title',
                'price',
                'currency',
                'images',
                'custom_fields',
                'location_country',
                'location_state',
                'location_state_code',
                'location_city',
                'location_city_code',
                'location_district',
                'location_district_code',
                'location_address',
                'show_location',
                'latitude',
                'longitude',
                'views_count',
                'status',
                'is_featured',
                'is_urgent',
                'published_at',
            ])
            ->with([
                'category:id,name_ar,name_en,name_tr',
                'subcategory:id,name_ar,name_en,name_tr',
                'user:id,name,business_name,slug,avatar,is_verified',
            ]);

        $ads = $scope($baseAdsQuery)
            ->latest('published_at')
            ->take($limit)
            ->get();

        return AdListResource::collection($ads)->resolve();
    }
}
