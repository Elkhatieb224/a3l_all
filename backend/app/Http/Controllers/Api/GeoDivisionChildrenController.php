<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoDivision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * تحميل تدريجي للمناطق: محافظة → مقاطعة (ilçe) → حي (mahalle) مع الأسماء الثلاثة دفعة واحدة.
 */
class GeoDivisionChildrenController extends Controller
{
    /**
     * شجرة كاملة نادرة الاستخدام — بدون تخزين طويل حتى لا تبقى بيانات قديمة بعد إعادة المزامنة.
     */
    private const CACHE_TREE = 'private, max-age=300';

    /**
     * قوائم المحافظة → مركز → حي تُستخدم في نماذج الإعلانات والتطبيق؛ يجب ألا تُخزَّن لأيام بعد تحديث GeoDivisionsSeeder.
     */
    private const CACHE_CASCADE = 'private, no-store';

    public function fullTree(string $country): JsonResponse
    {
        $c = strtoupper($country);
        if (! in_array($c, ['SY', 'TR'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid country (use SY or TR).',
            ], 422);
        }

        $rows = GeoDivision::query()
            ->where('country', $c)
            ->orderBy('level')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'country' => $c,
                    'states' => [],
                    'meta' => ['empty' => true, 'hint' => 'Run php artisan db:seed --class=GeoDivisionsSeeder'],
                ],
            ])->header('Cache-Control', self::CACHE_CASCADE);
        }

        $states = $rows
            ->where('level', GeoDivision::LEVEL_STATE)
            ->whereNull('parent_id')
            ->values()
            ->map(fn (GeoDivision $root) => $this->serializeGeoTreeBranch($root, $rows))
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'country' => $c,
                'states' => $states,
            ],
        ])->header('Cache-Control', self::CACHE_TREE);
    }

    /**
     * @param  Collection<int, GeoDivision>  $allRows
     * @return array<string, mixed>
     */
    private function serializeGeoTreeBranch(GeoDivision $row, Collection $allRows): array
    {
        $item = $row->toApiItem();
        $children = $allRows
            ->where('parent_id', $row->id)
            ->values()
            ->sort(function (GeoDivision $a, GeoDivision $b) {
                if ($a->sort_order !== $b->sort_order) {
                    return $a->sort_order <=> $b->sort_order;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        if ($row->level === GeoDivision::LEVEL_STATE) {
            $item['cities'] = $children
                ->map(fn (GeoDivision $child) => $this->serializeGeoTreeBranch($child, $allRows))
                ->all();
        } elseif ($row->level === GeoDivision::LEVEL_DISTRICT) {
            $item['districts'] = $children
                ->map(fn (GeoDivision $child) => $this->serializeGeoTreeBranch($child, $allRows))
                ->all();
        }

        return $item;
    }

    /**
     * GET /api/v1/states?country=TR|SY
     * قائمة المحافظات/الولايات (المستوى 0) مع المعرّفات — مثال تركيا: إسطنبول id = 34 (رقم اللوحة).
     */
    public function states(Request $request): JsonResponse
    {
        $country = strtoupper((string) $request->query('country', ''));
        if (! in_array($country, ['SY', 'TR'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing country (use SY or TR).',
            ], 422);
        }

        $items = GeoDivision::query()
            ->where('country', $country)
            ->where('level', GeoDivision::LEVEL_STATE)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_ar', 'name_en', 'name_tr']);

        return $this->jsonItems($country, $items);
    }

    /**
     * GET /api/v1/districts/{parentId}
     * المقاطعات/الأقضية تحت محافظة (parent = مستوى 0).
     */
    public function districts(int $parentId): JsonResponse
    {
        $parent = GeoDivision::query()->whereKey($parentId)->first();
        if ($parent === null || $parent->level !== GeoDivision::LEVEL_STATE) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found or not a province/governorate.',
            ], 404);
        }

        $items = GeoDivision::query()
            ->where('parent_id', $parentId)
            ->where('level', GeoDivision::LEVEL_DISTRICT)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_ar', 'name_en', 'name_tr']);

        return $this->jsonItems($parent->country, $items, $parent);
    }

    /**
     * GET /api/v1/neighborhoods/{parentId}
     * الأحياء تحت مقاطعة (parent = مستوى 1).
     */
    public function neighborhoods(int $parentId): JsonResponse
    {
        $parent = GeoDivision::query()->whereKey($parentId)->first();
        if ($parent === null || $parent->level !== GeoDivision::LEVEL_DISTRICT) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found or not a district.',
            ], 404);
        }

        $items = GeoDivision::query()
            ->where('parent_id', $parentId)
            ->where('level', GeoDivision::LEVEL_NEIGHBORHOOD)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_ar', 'name_en', 'name_tr']);

        return $this->jsonItems($parent->country, $items, $parent);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, GeoDivision>  $items
     */
    private function jsonItems(string $country, $items, ?GeoDivision $parent = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => [
                'country' => $country,
                'items' => $items->map(fn (GeoDivision $r) => $r->toApiItem())->values()->all(),
            ],
        ];

        if ($parent !== null) {
            $payload['data']['parent'] = $parent->toApiItem();
        }

        return response()->json($payload)->header('Cache-Control', self::CACHE_CASCADE);
    }
}
