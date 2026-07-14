<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeoDivision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoDivisionCoordsController extends Controller
{
   
    public function show(Request $request): JsonResponse
    {
        $country = strtoupper((string) $request->query('country', ''));
        if (! in_array($country, ['SY', 'TR'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing country (use SY or TR).',
            ], 422);
        }

        $districtCode = trim((string) $request->query('district_code', ''));
        $cityCode = trim((string) $request->query('city_code', ''));
        $stateCode = trim((string) $request->query('state_code', ''));
        $singleCode = trim((string) $request->query('code', ''));

        $ordered = array_values(array_filter([
            $districtCode,
            $cityCode,
            $stateCode,
            $singleCode,
        ], fn ($v) => is_string($v) && $v !== ''));

        // Optional: support ?codes[]=A&codes[]=B
        $codesList = $request->query('codes');
        if (is_array($codesList)) {
            foreach ($codesList as $c) {
                $t = trim((string) $c);
                if ($t !== '') {
                    $ordered[] = $t;
                }
            }
        }

        $ordered = array_values(array_unique($ordered));
        if ($ordered === []) {
            return response()->json([
                'success' => true,
                'data' => [
                    'country' => $country,
                    'picked_code' => null,
                    'level' => null,
                    'latitude' => null,
                    'longitude' => null,
                ],
            ])->header('Cache-Control', 'private, no-store');
        }

        foreach ($ordered as $code) {
            /** @var GeoDivision|null $div */
            $div = GeoDivision::query()
                ->where('country', $country)
                ->where('code', $code)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->first(['code', 'level', 'latitude', 'longitude']);

            if ($div && is_numeric($div->latitude) && is_numeric($div->longitude)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'country' => $country,
                        'picked_code' => $div->code,
                        'level' => $div->level,
                        'latitude' => (float) $div->latitude,
                        'longitude' => (float) $div->longitude,
                    ],
                ])->header('Cache-Control', 'private, no-store');
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'country' => $country,
                'picked_code' => null,
                'level' => null,
                'latitude' => null,
                'longitude' => null,
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}

