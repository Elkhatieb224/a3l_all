<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * وكيل عكسي لـ Nominatim (لتفادي حظر CORS على Flutter Web).
 */
class ReverseGeocodeController extends Controller
{
    public function reverse(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $acceptLanguage = (string) $request->query('accept_language', 'en,ar');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lat/lng',
            ], 422);
        }

        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?lat=%s&lon=%s&format=json&addressdetails=1&accept-language=%s',
            rawurlencode((string) $lat),
            rawurlencode((string) $lng),
            rawurlencode($acceptLanguage),
        );

        try {
            $res = Http::withHeaders([
                'User-Agent' => 'A3lnha/1.0 (classifieds; +https://aalenha.com)',
            ])->timeout(14)->get($url);

            if (! $res->successful()) {
                Log::warning('reverse_geocode.nominatim_http', [
                    'status' => $res->status(),
                ]);

                return response()->json(['success' => false], 502);
            }

            $json = $res->json();
            if (! is_array($json)) {
                return response()->json(['success' => false], 502);
            }

            return response()->json([
                'success' => true,
                'data' => $json,
            ])->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $e) {
            Log::warning('reverse_geocode.nominatim_failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 502);
        }
    }
}
