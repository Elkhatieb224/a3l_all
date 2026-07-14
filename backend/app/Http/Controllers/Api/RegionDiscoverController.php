<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DynamicRegionDiscoverService;
use App\Support\RegionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionDiscoverController extends Controller
{
    
    public function store(Request $request, DynamicRegionDiscoverService $service): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'required|string|in:SY,TR,sy,tr',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'primary' => 'nullable|array',
            'primary.administrative_area' => 'nullable|string|max:255',
            'primary.sub_administrative_area' => 'nullable|string|max:255',
            'primary.locality' => 'nullable|string|max:255',
            'primary.sub_locality' => 'nullable|string|max:255',
            'needles' => 'nullable|array|max:120',
            'needles.*' => 'string|max:400',
        ]);

        $country = strtoupper((string) $validated['country']);
        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $primary = $validated['primary'] ?? [];
        $needles = array_values(array_filter(
            array_map('trim', $validated['needles'] ?? []),
            fn ($s) => $s !== ''
        ));

        $stateLabel = trim((string) ($primary['administrative_area'] ?? ''));
        $cityLabel = trim((string) ($primary['locality'] ?? ''));
        $subAdmin = trim((string) ($primary['sub_administrative_area'] ?? ''));
        $districtLabel = trim((string) ($primary['sub_locality'] ?? ''));

        if ($cityLabel === '' && $subAdmin !== '') {
            $cityLabel = $subAdmin;
        }
        if ($stateLabel === '' && $needles !== []) {
            $stateLabel = $needles[0];
        }
        if ($cityLabel === '') {
            $cityLabel = $stateLabel !== '' ? $stateLabel : 'Unknown';
        }
        if ($stateLabel === '') {
            $stateLabel = $cityLabel;
        }

        $analysis = RegionCatalog::analyzeNeedlesForDiscover($country, $needles, $primary);
        if ($analysis['kind'] === 'full') {
            return response()->json([
                'success' => true,
                'data' => array_merge($analysis['payload'], [
                    'country' => $country,
                    'source' => 'catalog',
                ]),
            ])->header('Cache-Control', 'private, no-store');
        }

        try {
            if ($analysis['kind'] === 'state') {
                $payload = $service->ensureAnchoredHierarchy(
                    $country,
                    $analysis['state_code'],
                    $cityLabel,
                    $districtLabel !== '' ? $districtLabel : null,
                    $lat,
                    $lng,
                    $needles,
                    $request->user()?->id
                );
            } else {
                $payload = $service->ensureFullDynamicHierarchy(
                    $country,
                    $stateLabel,
                    $cityLabel,
                    $districtLabel !== '' ? $districtLabel : null,
                    $lat,
                    $lng,
                    $needles,
                    $request->user()?->id
                );
            }

            return response()->json([
                'success' => true,
                'data' => array_merge($payload, [
                    'country' => $country,
                    'source' => 'dynamic',
                ]),
            ])->header('Cache-Control', 'private, no-store');
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
