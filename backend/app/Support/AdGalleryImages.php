<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdGalleryImages
{
    /**
     *
     * @param  array<int, string>  $existing
     * @return array<int, string>
     */
    public static function mergeFromRequest(Request $request, array $existing, string $storageSubdir): array
    {
        $gallery = array_values(array_filter(array_map('strval', $existing)));

        $newFiles = $request->file('ad_gallery_new', []);
        if (! is_array($newFiles)) {
            $newFiles = [];
        }
        foreach ($newFiles as $file) {
            if ($file && $file->isValid()) {
                $path = store_image_as_webp($file, $storageSubdir);
                if ($path !== '' && $path !== null) {
                    $gallery[] = $path;
                }
            }
        }

        $remove = $request->input('ad_gallery_remove', []);
        if (! is_array($remove)) {
            $remove = [];
        }
        foreach ($remove as $path) {
            if (! is_string($path)) {
                continue;
            }
            $path = trim($path);
            if ($path === '' || ! in_array($path, $gallery, true)) {
                continue;
            }
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $gallery = array_values(array_diff($gallery, [$path]));
        }

        return array_values(array_filter($gallery));
    }
}
