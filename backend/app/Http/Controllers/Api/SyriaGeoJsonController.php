<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * بيانات وصفية وروابط لملفات GeoJSON الخاصة بسوريا (حدود إدارية).
 */
class SyriaGeoJsonController extends Controller
{
    private const CACHE = 'public, max-age=86400, stale-while-revalidate=604800';

    public function manifest(): JsonResponse
    {
        $prefix = rtrim((string) config('syria_geojson.public_path_prefix', '/geo/syria-geojson'), '/');
        $layers = config('syria_geojson.layers', []);
        if (! is_array($layers)) {
            $layers = [];
        }

        $publicDir = public_path(ltrim($prefix, '/'));
        $out = [];

        foreach ($layers as $layer) {
            if (! is_array($layer)) {
                continue;
            }
            $file = (string) ($layer['file'] ?? '');
            if ($file === '' || str_contains($file, '..')) {
                continue;
            }
            $path = $publicDir.DIRECTORY_SEPARATOR.$file;
            $out[] = [
                'id' => (string) ($layer['id'] ?? pathinfo($file, PATHINFO_FILENAME)),
                'file' => $file,
                'admin_level' => (int) ($layer['admin_level'] ?? 0),
                'geometry_type' => (string) ($layer['geometry_type'] ?? 'Polygon'),
                'label_ar' => (string) ($layer['label_ar'] ?? ''),
                'label_en' => (string) ($layer['label_en'] ?? ''),
                'label_tr' => (string) ($layer['label_tr'] ?? ''),
                'url' => url($prefix.'/'.$file),
                'bytes' => is_file($path) ? filesize($path) : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'source_repository' => 'https://github.com/alahwa/Syria-GeoJson-Maps',
                'base_url' => url($prefix),
                'layers' => $out,
            ],
        ])->header('Cache-Control', self::CACHE);
    }
}
