<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\RegionCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    /**
     * أسماء للمطابقة مع نتائج الجيوكودينج (عربي/إنجليزي/تركي) بغض النظر عن لغة واجهة التطبيق.
     *
     * @return list<string>
     */
    private static function matchNamesForNode(array $node): array
    {
        $seen = [];
        foreach (['name_ar', 'name_en', 'name_tr'] as $k) {
            $v = trim((string) ($node[$k] ?? ''));
            if ($v !== '') {
                $seen[$v] = true;
            }
        }
        $extra = $node['match_names'] ?? [];
        if (is_array($extra)) {
            foreach ($extra as $v) {
                $s = trim((string) $v);
                if ($s !== '') {
                    $seen[$s] = true;
                }
            }
        }

        return array_keys($seen);
    }

    /**
     * شجرة المناطق لدولة (SY | TR) حسب لغة التطبيق.
     */
    public function show(Request $request, string $country)
    {
        $c = strtoupper($country);
        if (! in_array($c, ['SY', 'TR'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid country',
            ], 422);
        }

        $locale = app()->getLocale();
        $tree = RegionCatalog::mergedTreeForCountry($c);
        $payload = [];

        foreach ($tree as $state) {
            $cities = [];
            foreach ($state['cities'] ?? [] as $city) {
                $districts = [];
                foreach ($city['districts'] ?? [] as $district) {
                    $districts[] = [
                        'code' => $district['code'] ?? '',
                        'name' => RegionCatalog::labelForLocale($district, $locale),
                        'match_names' => self::matchNamesForNode($district),
                    ];
                }
                $cities[] = [
                    'code' => $city['code'] ?? '',
                    'name' => RegionCatalog::labelForLocale($city, $locale),
                    'match_names' => self::matchNamesForNode($city),
                    'districts' => $districts,
                ];
            }
            $payload[] = [
                'code' => $state['code'] ?? '',
                'name' => RegionCatalog::labelForLocale($state, $locale),
                'match_names' => self::matchNamesForNode($state),
                'cities' => $cities,
            ];
        }

        if ($payload === []) {
            Log::warning('regions.catalog_empty', [
                'country' => $c,
                'hint' => 'Check config/ad_regions.php exists and run php artisan config:clear',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'country' => $c,
                'states' => $payload,
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
