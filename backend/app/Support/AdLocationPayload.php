<?php

namespace App\Support;

use App\Models\GeoDivision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * قواعد ودمج حقول موقع الإعلان (يدوي / خريطة) لاستخدامها من الـ API والواجهة.
 */
final class AdLocationPayload
{
    /**
     * @return array<string, string|array<int, string>>
     */
    public static function validationRules(): array
    {
        return [
            'location_country' => 'required|in:SY,TR',
            'location_input_method' => 'required|in:manual,map',
            'location_state_code' => 'nullable|string|max:64',
            'location_city_code' => 'nullable|string|max:64',
            'location_district_code' => 'nullable|string|max:64',
            'location_state' => 'nullable|string|max:255',
            'location_city' => 'nullable|string|max:255',
            'location_district' => 'nullable|string|max:255',
            'location_address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public static function attachLocationConsistency(\Illuminate\Validation\Validator $validator, Request $request): void
    {
        $validator->after(function ($v) use ($request) {
            $method = (string) $request->input('location_input_method', '');
            $country = (string) $request->input('location_country', '');

            if ($method === 'manual') {
                $sc = $request->input('location_state_code');
                $cc = $request->input('location_city_code');
                $dc = $request->input('location_district_code');
                if (! RegionCatalog::isValidManualSelection($country, is_string($sc) ? $sc : null, is_string($cc) ? $cc : null, is_string($dc) ? $dc : null)) {
                    $v->errors()->add('location_state_code', __('frontend.ads.location_hierarchy_invalid'));
                }
            } elseif ($method === 'map') {
                if (! $request->filled('latitude') || ! $request->filled('longitude')) {
                    $v->errors()->add('latitude', __('frontend.ads.location_map_coords_required'));
                }
                $sc = $request->input('location_state_code');
                $cc = $request->input('location_city_code');
                $dc = $request->input('location_district_code');
                if (! RegionCatalog::isValidMapSelection($country, is_string($sc) ? $sc : null, is_string($cc) ? $cc : null, is_string($dc) ? $dc : null)) {
                    $v->errors()->add('location_state_code', __('frontend.ads.location_hierarchy_invalid'));
                }
            }
        });
    }

    /**
     * دمج مع قواعد أخرى والتحقق من اتساق الوضع اليدوي / الخريطة.
     *
     * @param  array<string, mixed>  $mergeRules
     * @return array{0: bool, 1: \Illuminate\Contracts\Validation\Validator}
     */
    public static function validateWithMergedRules(Request $request, array $mergeRules = []): array
    {
        $validator = Validator::make(
            $request->all(),
            array_merge($mergeRules, self::validationRules())
        );
        self::attachLocationConsistency($validator, $request);

        return [$validator->passes(), $validator];
    }

    /**
     * @param  array<string, mixed>  $validated  ناتج Validator بعد النجاح
     * @return array<string, mixed> حقول جاهزة لـ Ad::create / update
     */
    public static function normalizedForDatabase(array $validated, Request $request): array
    {
        $locale = app()->getLocale();
        $method = (string) ($validated['location_input_method'] ?? 'manual');
        $country = (string) ($validated['location_country'] ?? 'SY');

        $state = (string) ($validated['location_state'] ?? '');
        $city = (string) ($validated['location_city'] ?? '');
        $district = (string) ($validated['location_district'] ?? '');
        $stateCode = $validated['location_state_code'] ?? null;
        $cityCode = $validated['location_city_code'] ?? null;
        $districtCode = $validated['location_district_code'] ?? null;

        if (is_string($stateCode) && $stateCode !== '' && is_string($cityCode) && $cityCode !== '') {
            $dcStr = is_string($districtCode) ? $districtCode : '';
            if ($dcStr !== '') {
                $resolved = RegionCatalog::resolveByCodes($country, $stateCode, $cityCode, $dcStr, $locale);
                if ($resolved !== null) {
                    $state = $resolved['state']['label'];
                    $city = $resolved['city']['label'];
                    $district = $resolved['district']['label'];
                }
            } else {
                $pair = RegionCatalog::labelsForStateCity($country, $stateCode, $cityCode, $locale);
                if ($pair !== null) {
                    $state = $pair['state'];
                    $city = $pair['city'];
                    $district = '';
                }
            }
        }

        $lat = $request->filled('latitude') && is_numeric($request->latitude) ? (float) $request->latitude : null;
        $lng = $request->filled('longitude') && is_numeric($request->longitude) ? (float) $request->longitude : null;

        // If coordinates were not provided (common in manual/list mode), try to
        // derive a stable point from the geo_divisions catalog (district → city → state).
        if ($lat === null || $lng === null) {
            $c = strtoupper($country);
            $codes = [
                is_string($districtCode) ? trim($districtCode) : '',
                is_string($cityCode) ? trim($cityCode) : '',
                is_string($stateCode) ? trim($stateCode) : '',
            ];
            foreach ($codes as $code) {
                if ($code === '') {
                    continue;
                }
                /** @var GeoDivision|null $div */
                $div = GeoDivision::query()
                    ->where('country', $c)
                    ->where('code', $code)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->first(['latitude', 'longitude']);
                if ($div && is_numeric($div->latitude) && is_numeric($div->longitude)) {
                    $lat = (float) $div->latitude;
                    $lng = (float) $div->longitude;
                    break;
                }
            }
        }

        return [
            'location_country' => $country,
            'location_state' => $state,
            'location_state_code' => is_string($stateCode) && $stateCode !== '' ? $stateCode : null,
            'location_city' => $city,
            'location_city_code' => is_string($cityCode) && $cityCode !== '' ? $cityCode : null,
            'location_district' => $district,
            'location_district_code' => is_string($districtCode) && $districtCode !== '' ? $districtCode : null,
            'location_address' => (function () use ($validated) {
                $a = $validated['location_address'] ?? null;
                if (! is_string($a)) {
                    return null;
                }
                $t = trim($a);

                return $t === '' ? null : $t;
            })(),
            'location_input_method' => $method,
            'show_location' => true,
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }
}
