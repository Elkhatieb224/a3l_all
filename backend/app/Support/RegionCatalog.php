<?php

namespace App\Support;

use App\Models\DynamicRegion;

final class RegionCatalog
{
    /**
     * لا نستخدم كاشاً ثابتاً هنا: في PHP-FPM قد يبقى مصفوفة فارغة من طلب سابق
     * بعد نشر config/ad_regions.php حتى يُعاد تشغيل الووركر.
     */
    public static function all(): array
    {
        $raw = config('ad_regions');

        return is_array($raw) ? $raw : [];
    }

    public static function countries(): array
    {
        return array_keys(self::all());
    }

    public static function treeForCountry(string $country): array
    {
        $c = strtoupper($country);

        return self::all()[$c] ?? [];
    }

    /**
     * كتالوج ثابت + مدن/محافظات مضافة من الخريطة (جدول dynamic_regions).
     *
     * @return list<array<string, mixed>>
     */
    public static function mergedTreeForCountry(string $country): array
    {
        $c = strtoupper($country);
        $static = self::treeForCountry($c);
        $anchored = DynamicRegion::anchoredCityBranchesForCountry($c);
        foreach ($static as $i => $state) {
            $code = (string) ($state['code'] ?? '');
            if ($code !== '' && isset($anchored[$code])) {
                $static[$i]['cities'] = array_values(array_merge($state['cities'] ?? [], $anchored[$code]));
            }
        }

        return array_merge($static, DynamicRegion::configShapedRootStatesForCountry($c));
    }

    public static function stateExistsInStaticCatalog(string $country, string $stateCode): bool
    {
        $c = strtoupper($country);
        foreach (self::treeForCountry($c) as $state) {
            if (($state['code'] ?? '') === $stateCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * مطابقة سريعة للجيوكودينج قبل إنشاء صفوف ديناميكية.
     *
     * @param  list<string>  $needles
     * @param  array<string, mixed>  $primary
     * @return array{kind: 'full', payload: array<string, mixed>}|array{kind: 'state', state_code: string}|array{kind: 'none'}
     */
    public static function analyzeNeedlesForDiscover(string $country, array $needles, array $primary = []): array
    {
        $pool = self::buildNeedlePool($needles, $primary);
        if ($pool === []) {
            return ['kind' => 'none'];
        }

        $tree = self::mergedTreeForCountry(strtoupper($country));
        $bestState = null;
        $bestStateScore = 0;
        foreach ($tree as $state) {
            $score = self::scoreNodeAgainstPool($state, $pool);
            if ($score > $bestStateScore) {
                $bestStateScore = $score;
                $bestState = $state;
            }
        }
        if ($bestState === null || $bestStateScore === 0) {
            return ['kind' => 'none'];
        }

        $stCode = (string) ($bestState['code'] ?? '');
        if ($stCode === '') {
            return ['kind' => 'none'];
        }

        $bestCity = null;
        $bestCityScore = 0;
        foreach ($bestState['cities'] ?? [] as $city) {
            $score = self::scoreNodeAgainstPool($city, $pool);
            if ($score > $bestCityScore) {
                $bestCityScore = $score;
                $bestCity = $city;
            }
        }

        $bestDist = null;
        $bestDistScore = 0;
        $cityOwningBestDist = null;
        foreach ($bestState['cities'] ?? [] as $city) {
            foreach ($city['districts'] ?? [] as $dist) {
                $score = self::scoreNodeAgainstPool($dist, $pool);
                if ($score > $bestDistScore) {
                    $bestDistScore = $score;
                    $bestDist = $dist;
                    $cityOwningBestDist = $city;
                }
            }
        }

        if ($bestDistScore > 0 && $cityOwningBestDist !== null) {
            $bestCity = $cityOwningBestDist;
            $bestCityScore = self::scoreNodeAgainstPool($bestCity, $pool);
        }

        if ($bestCity === null || ($bestCityScore === 0 && $bestDistScore === 0)) {
            return ['kind' => 'state', 'state_code' => $stCode];
        }

        $locale = app()->getLocale();
        $ctCode = (string) ($bestCity['code'] ?? '');
        if ($ctCode === '') {
            return ['kind' => 'state', 'state_code' => $stCode];
        }

        $payload = [
            'location_state_code' => $stCode,
            'location_city_code' => $ctCode,
            'location_district_code' => $bestDistScore > 0 && $bestDist !== null
                ? (string) ($bestDist['code'] ?? '')
                : null,
            'location_state' => self::labelForLocale($bestState, $locale),
            'location_city' => self::labelForLocale($bestCity, $locale),
            'location_district' => $bestDistScore > 0 && $bestDist !== null
                ? self::labelForLocale($bestDist, $locale)
                : '',
        ];

        return ['kind' => 'full', 'payload' => $payload];
    }

    /**
     * @param  list<string>  $needles
     * @param  array<string, mixed>  $primary
     * @return list<string>
     */
    private static function buildNeedlePool(array $needles, array $primary): array
    {
        $raw = array_merge(
            $needles,
            [
                (string) ($primary['administrative_area'] ?? ''),
                (string) ($primary['sub_administrative_area'] ?? ''),
                (string) ($primary['locality'] ?? ''),
                (string) ($primary['sub_locality'] ?? ''),
            ]
        );
        $out = [];
        foreach ($raw as $s) {
            $t = trim((string) $s);
            if (mb_strlen($t) >= 2) {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<string>  $pool
     */
    private static function scoreNodeAgainstPool(array $node, array $pool): int
    {
        $labels = self::labelsForNodeMatching($node);
        if ($labels === []) {
            return 0;
        }
        $sc = 0;
        foreach ($labels as $lab) {
            foreach ($pool as $n) {
                if (self::labelMatchesNeedle($lab, $n)) {
                    $sc += 3;
                }
            }
        }

        return $sc;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    private static function labelsForNodeMatching(array $node): array
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
                $t = trim((string) $v);
                if ($t !== '') {
                    $seen[$t] = true;
                }
            }
        }

        return array_keys($seen);
    }

    private static function labelMatchesNeedle(string $label, string $needle): bool
    {
        $a = mb_strtolower(trim($label), 'UTF-8');
        $b = mb_strtolower(trim($needle), 'UTF-8');
        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_contains($a, $b) || str_contains($b, $a);
    }

    public static function labelForLocale(array $node, string $locale): string
    {
        $locale = strtolower($locale);
        if ($locale === 'tr' && ! empty($node['name_tr'])) {
            return (string) $node['name_tr'];
        }
        if ($locale === 'en' && ! empty($node['name_en'])) {
            return (string) $node['name_en'];
        }

        return (string) ($node['name_ar'] ?? $node['name_en'] ?? $node['name_tr'] ?? $node['code'] ?? '');
    }

    /**
     * @return array{state: array, city: array, district: array}|null
     */
    public static function resolveByCodes(
        string $country,
        string $stateCode,
        string $cityCode,
        string $districtCode,
        string $locale = 'ar'
    ): ?array {
        $c = strtoupper($country);
        foreach (self::mergedTreeForCountry($c) as $state) {
            if (($state['code'] ?? '') !== $stateCode) {
                continue;
            }
            foreach ($state['cities'] ?? [] as $city) {
                if (($city['code'] ?? '') !== $cityCode) {
                    continue;
                }
                foreach ($city['districts'] ?? [] as $district) {
                    if (($district['code'] ?? '') !== $districtCode) {
                        continue;
                    }

                    return [
                        'state' => [
                            'code' => $state['code'],
                            'label' => self::labelForLocale($state, $locale),
                        ],
                        'city' => [
                            'code' => $city['code'],
                            'label' => self::labelForLocale($city, $locale),
                        ],
                        'district' => [
                            'code' => $district['code'],
                            'label' => self::labelForLocale($district, $locale),
                        ],
                    ];
                }
            }
        }

        return null;
    }

    public static function isValidManualSelection(
        string $country,
        ?string $stateCode,
        ?string $cityCode,
        ?string $districtCode
    ): bool {
        if ($stateCode === null || $stateCode === '' || $cityCode === null || $cityCode === '') {
            return false;
        }
        if (! self::isValidStateCityPair($country, $stateCode, $cityCode)) {
            return false;
        }

        if ($districtCode === null || $districtCode === '') {
            return false;
        }

        return self::resolveByCodes($country, $stateCode, $cityCode, $districtCode) !== null;
    }

    public static function isValidMapSelection(
        string $country,
        ?string $stateCode,
        ?string $cityCode,
        ?string $districtCode
    ): bool {
        if ($stateCode === null || $stateCode === '' || $cityCode === null || $cityCode === '') {
            return false;
        }
        if (! self::isValidStateCityPair($country, $stateCode, $cityCode)) {
            return false;
        }
        if ($districtCode === null || $districtCode === '') {
            return true;
        }

        return self::resolveByCodes($country, $stateCode, $cityCode, $districtCode) !== null;
    }

    public static function isValidStateCityPair(
        string $country,
        string $stateCode,
        string $cityCode
    ): bool {
        $c = strtoupper($country);
        foreach (self::mergedTreeForCountry($c) as $state) {
            if (($state['code'] ?? '') !== $stateCode) {
                continue;
            }
            foreach ($state['cities'] ?? [] as $city) {
                if (($city['code'] ?? '') === $cityCode) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{state: string, city: string}|null
     */
    public static function labelsForStateCity(
        string $country,
        string $stateCode,
        string $cityCode,
        string $locale = 'ar'
    ): ?array {
        $c = strtoupper($country);
        foreach (self::mergedTreeForCountry($c) as $state) {
            if (($state['code'] ?? '') !== $stateCode) {
                continue;
            }
            foreach ($state['cities'] ?? [] as $city) {
                if (($city['code'] ?? '') !== $cityCode) {
                    continue;
                }

                return [
                    'state' => self::labelForLocale($state, $locale),
                    'city' => self::labelForLocale($city, $locale),
                ];
            }
        }

        return null;
    }
}
