<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class LegalController extends Controller
{
    public function privacy()
    {
        $locale = app()->getLocale();
        $cacheKey = "api:legal:privacy:v1:{$locale}";
        $payload = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($locale) {
            $content = Setting::get("privacy_policy_{$locale}", '');

            return [
                'success' => true,
                'data' => [
                    'content' => $content,
                ],
            ];
        });

        $etag = '"' . sha1(json_encode($payload, JSON_UNESCAPED_UNICODE)) . '"';
        if (trim((string) request()->header('If-None-Match')) === $etag) {
            return response()->noContent(304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
        }

        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
    }

    public function terms()
    {
        $locale = app()->getLocale();
        $cacheKey = "api:legal:terms:v1:{$locale}";
        $payload = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($locale) {
            $content = Setting::get("terms_conditions_{$locale}", '');

            return [
                'success' => true,
                'data' => [
                    'content' => $content,
                ],
            ];
        });

        $etag = '"' . sha1(json_encode($payload, JSON_UNESCAPED_UNICODE)) . '"';
        if (trim((string) request()->header('If-None-Match')) === $etag) {
            return response()->noContent(304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
        }

        return response()->json($payload)
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=3600');
    }
}
