<?php

namespace App\Services;

use App\Models\DynamicRegion;
use App\Support\RegionCatalog;
use Illuminate\Support\Facades\DB;

/**
 * إنشاء أو إعادة استخدام عقد ديناميكية عند اختيار موقع من الخريطة خارج الكتالوج الثابت.
 */
final class DynamicRegionDiscoverService
{
    /**
     * محافظة + مدينة + حي كصفوف ديناميكية (بدون ربط بمحافظة ثابتة).
     *
     * @param  list<string>  $needles
     * @return array{
     *     location_state_code: string,
     *     location_city_code: string,
     *     location_district_code: string,
     *     location_state: string,
     *     location_city: string,
     *     location_district: string
     * }
     */
    public function ensureFullDynamicHierarchy(
        string $country,
        string $stateLabel,
        string $cityLabel,
        ?string $districtLabel,
        ?float $latitude,
        ?float $longitude,
        array $needles,
        ?int $userId
    ): array {
        $country = strtoupper($country);
        $locale = app()->getLocale();

        return DB::transaction(function () use ($country, $stateLabel, $cityLabel, $districtLabel, $latitude, $longitude, $needles, $userId, $locale) {
            $extra = array_slice(
                array_values(array_filter(array_map('trim', $needles), fn ($s) => $s !== '')),
                0,
                40
            );

            $state = $this->findOrCreateState($country, $stateLabel, $extra, $latitude, $longitude, $userId);
            $city = $this->findOrCreateCityUnderParent($country, $state->id, $cityLabel, $extra, $latitude, $longitude, $userId);

            if ($districtLabel === null || trim($districtLabel) === '') {
                $state->increment('use_count');
                $city->increment('use_count');

                return [
                    'location_state_code' => $state->code,
                    'location_city_code' => $city->code,
                    'location_district_code' => null,
                    'location_state' => DynamicRegion::labelForLocale($state, $locale),
                    'location_city' => DynamicRegion::labelForLocale($city, $locale),
                    'location_district' => '',
                ];
            }

            $distName = trim($districtLabel);
            $district = $this->findOrCreateDistrict(
                $country,
                $city->id,
                $distName,
                $extra,
                $latitude,
                $longitude,
                $userId,
                false
            );

            $state->increment('use_count');
            $city->increment('use_count');
            $district->increment('use_count');

            return [
                'location_state_code' => $state->code,
                'location_city_code' => $city->code,
                'location_district_code' => $district->code,
                'location_state' => DynamicRegion::labelForLocale($state, $locale),
                'location_city' => DynamicRegion::labelForLocale($city, $locale),
                'location_district' => DynamicRegion::labelForLocale($district, $locale),
            ];
        });
    }

    /**
     * مدينة (وحي) ديناميكية تحت محافظة موجودة في الكتالوج الثابت.
     *
     * @param  list<string>  $needles
     * @return array{
     *     location_state_code: string,
     *     location_city_code: string,
     *     location_district_code: string,
     *     location_state: string,
     *     location_city: string,
     *     location_district: string
     * }
     */
    public function ensureAnchoredHierarchy(
        string $country,
        string $staticStateCode,
        string $cityLabel,
        ?string $districtLabel,
        ?float $latitude,
        ?float $longitude,
        array $needles,
        ?int $userId
    ): array {
        if (! RegionCatalog::stateExistsInStaticCatalog($country, $staticStateCode)) {
            throw new \InvalidArgumentException('Invalid static state code.');
        }

        $country = strtoupper($country);
        $locale = app()->getLocale();

        return DB::transaction(function () use ($country, $staticStateCode, $cityLabel, $districtLabel, $latitude, $longitude, $needles, $userId, $locale) {
            $extra = array_slice(
                array_values(array_filter(array_map('trim', $needles), fn ($s) => $s !== '')),
                0,
                40
            );

            $city = $this->findOrCreateAnchoredCity(
                $country,
                $staticStateCode,
                $cityLabel,
                $extra,
                $latitude,
                $longitude,
                $userId
            );

            if ($districtLabel === null || trim($districtLabel) === '') {
                $city->increment('use_count');

                $stateNode = null;
                foreach (RegionCatalog::treeForCountry($country) as $s) {
                    if (($s['code'] ?? '') === $staticStateCode) {
                        $stateNode = $s;
                        break;
                    }
                }
                $stateLabel = $stateNode !== null
                    ? RegionCatalog::labelForLocale($stateNode, $locale)
                    : $staticStateCode;

                return [
                    'location_state_code' => $staticStateCode,
                    'location_city_code' => $city->code,
                    'location_district_code' => null,
                    'location_state' => $stateLabel,
                    'location_city' => DynamicRegion::labelForLocale($city, $locale),
                    'location_district' => '',
                ];
            }

            $distName = trim($districtLabel);
            $district = $this->findOrCreateDistrict(
                $country,
                $city->id,
                $distName,
                $extra,
                $latitude,
                $longitude,
                $userId,
                false
            );

            $city->increment('use_count');
            $district->increment('use_count');

            $stateNode = null;
            foreach (RegionCatalog::treeForCountry($country) as $s) {
                if (($s['code'] ?? '') === $staticStateCode) {
                    $stateNode = $s;
                    break;
                }
            }
            $stateLabel = $stateNode !== null
                ? RegionCatalog::labelForLocale($stateNode, $locale)
                : $staticStateCode;

            return [
                'location_state_code' => $staticStateCode,
                'location_city_code' => $city->code,
                'location_district_code' => $district->code,
                'location_state' => $stateLabel,
                'location_city' => DynamicRegion::labelForLocale($city, $locale),
                'location_district' => DynamicRegion::labelForLocale($district, $locale),
            ];
        });
    }

    /**
     * @param  list<string>  $extraNeedles
     */
    private function findOrCreateState(
        string $country,
        string $label,
        array $extraNeedles,
        ?float $latitude,
        ?float $longitude,
        ?int $userId
    ): DynamicRegion {
        $hash = DynamicRegion::dedupHash($country, 'state', null, $label, null);
        $row = DynamicRegion::query()
            ->where('country', $country)
            ->where('type', 'state')
            ->whereNull('parent_id')
            ->whereNull('anchor_state_code')
            ->where('dedup_hash', $hash)
            ->first();
        if ($row !== null) {
            return $row;
        }
        $display = $this->spreadLabel($label);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => null,
            'parent_id' => null,
            'type' => 'state',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $display['ar'],
            'name_en' => $display['en'],
            'name_tr' => $display['tr'],
            'extra_match_names' => $extraNeedles,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'created_by_user_id' => $userId,
            'use_count' => 0,
        ]);
    }

    /**
     * @param  list<string>  $extraNeedles
     */
    private function findOrCreateCityUnderParent(
        string $country,
        int $stateId,
        string $label,
        array $extraNeedles,
        ?float $latitude,
        ?float $longitude,
        ?int $userId
    ): DynamicRegion {
        $hash = DynamicRegion::dedupHash($country, 'city', $stateId, $label, null);
        $row = DynamicRegion::query()
            ->where('country', $country)
            ->where('type', 'city')
            ->where('parent_id', $stateId)
            ->whereNull('anchor_state_code')
            ->where('dedup_hash', $hash)
            ->first();
        if ($row !== null) {
            return $row;
        }
        $display = $this->spreadLabel($label);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => null,
            'parent_id' => $stateId,
            'type' => 'city',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $display['ar'],
            'name_en' => $display['en'],
            'name_tr' => $display['tr'],
            'extra_match_names' => $extraNeedles,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'created_by_user_id' => $userId,
            'use_count' => 0,
        ]);
    }

    /**
     * @param  list<string>  $extraNeedles
     */
    private function findOrCreateAnchoredCity(
        string $country,
        string $staticStateCode,
        string $label,
        array $extraNeedles,
        ?float $latitude,
        ?float $longitude,
        ?int $userId
    ): DynamicRegion {
        $hash = DynamicRegion::dedupHash($country, 'city', null, $label, $staticStateCode);
        $row = DynamicRegion::query()
            ->where('country', $country)
            ->where('type', 'city')
            ->whereNull('parent_id')
            ->where('anchor_state_code', $staticStateCode)
            ->where('dedup_hash', $hash)
            ->first();
        if ($row !== null) {
            return $row;
        }
        $display = $this->spreadLabel($label);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => $staticStateCode,
            'parent_id' => null,
            'type' => 'city',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $display['ar'],
            'name_en' => $display['en'],
            'name_tr' => $display['tr'],
            'extra_match_names' => $extraNeedles,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'created_by_user_id' => $userId,
            'use_count' => 0,
        ]);
    }

    /**
     * @param  list<string>  $extraNeedles
     */
    private function findOrCreateDistrict(
        string $country,
        int $cityId,
        string $label,
        array $extraNeedles,
        ?float $latitude,
        ?float $longitude,
        ?int $userId,
        bool $isSyntheticDistrict
    ): DynamicRegion {
        $hash = DynamicRegion::dedupHash($country, 'district', $cityId, $label, null);
        $row = DynamicRegion::query()
            ->where('country', $country)
            ->where('type', 'district')
            ->where('parent_id', $cityId)
            ->where('dedup_hash', $hash)
            ->first();
        if ($row !== null) {
            return $row;
        }
        $display = $isSyntheticDistrict
            ? ['ar' => 'عام', 'en' => 'General', 'tr' => 'Genel']
            : $this->spreadLabel($label);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => null,
            'parent_id' => $cityId,
            'type' => 'district',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $display['ar'],
            'name_en' => $display['en'],
            'name_tr' => $display['tr'],
            'extra_match_names' => $isSyntheticDistrict ? [] : $extraNeedles,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'created_by_user_id' => $userId,
            'use_count' => 0,
        ]);
    }

    /**
     * @return array{ar: string, en: string, tr: string}
     */
    private function spreadLabel(string $label): array
    {
        $t = trim($label);

        return [
            'ar' => $t,
            'en' => $t,
            'tr' => $t,
        ];
    }
}
