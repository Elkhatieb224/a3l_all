<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdImagesConfig
{
    public const DEFAULT_USER_UPLOAD_MAX_IMAGES = 15;

    public const MODE_USER_UPLOAD = 'user_upload';

    public const MODE_ADMIN_GALLERY = 'admin_gallery';

    /**
     * مسار ملف داخل قرص public/storage (بدون بادئة storage/).
     */
    public static function normalizePublicStoragePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }

    /**
     * إن وُجدت نسخة WebP من المسار المخزّن (.png/.jpg…) نستخدمها للعرض (بعد images:convert-to-webp أو رفع بتحويل).
     */
    public static function resolvePublicDiskPathForServing(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath));
        if ($relativePath === '') {
            return '';
        }
        if (Storage::disk('public')->exists($relativePath)) {
            return $relativePath;
        }
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $webp = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $relativePath);
            if ($webp !== $relativePath && Storage::disk('public')->exists($webp)) {
                return $webp;
            }
        }

        return $relativePath;
    }

    /**
     * رابط عام لملف في storage مع مطابقة نطاق الطلب عند توفره (يصلح APP_URL خاطئ مقابل Host الـ API).
     */
    public static function storageUrlForPath(string $path, ?Request $request = null): string
    {
        $trim = trim($path);
        if ($trim === '') {
            return '';
        }
        if (str_starts_with($trim, 'http://') || str_starts_with($trim, 'https://')) {
            return $trim;
        }
        $rel = self::normalizePublicStoragePath($trim);
        if ($rel === '') {
            return '';
        }
        $rel = self::resolvePublicDiskPathForServing($rel);
        if ($request !== null) {
            $host = $request->getSchemeAndHttpHost();
            if ($host !== '') {
                return rtrim($host, '/').'/storage/'.$rel;
            }
        }

        return asset('storage/'.$rel);
    }

    /**
     * @return array{mode: string, gallery_paths: array<int, string>, gallery_urls: array<int, string>}
     */
    public static function resolve(Category $category, Subcategory $subcategory): array
    {
        $subMode = $subcategory->ad_images_mode;
        $mode = ($subMode === null || $subMode === '')
            ? ($category->ad_images_mode ?? self::MODE_USER_UPLOAD)
            : $subMode;

        if (! in_array($mode, [self::MODE_USER_UPLOAD, self::MODE_ADMIN_GALLERY], true)) {
            $mode = self::MODE_USER_UPLOAD;
        }

        $gallery = [];
        if ($mode === self::MODE_ADMIN_GALLERY) {
            $gallery = $subcategory->ad_gallery_images ?? [];
            if (! is_array($gallery) || count($gallery) === 0) {
                $gallery = $category->ad_gallery_images ?? [];
            }
            $gallery = array_values(array_filter(array_map('strval', is_array($gallery) ? $gallery : [])));
        }

        if ($mode === self::MODE_ADMIN_GALLERY && count($gallery) === 0) {
            $mode = self::MODE_USER_UPLOAD;
        }

        return [
            'mode' => $mode,
            'max_images' => self::resolveMaxImages($category, $subcategory),
            'gallery_paths' => $gallery,
            'gallery_urls' => array_values(array_map(fn (string $p) => self::storageUrlForPath($p), $gallery)),
        ];
    }

    public static function resolveMaxImages(Category $category, Subcategory $subcategory): int
    {
        $subMax = $subcategory->ad_images_max;
        if ($subMax !== null && $subMax !== '') {
            return max(1, (int) $subMax);
        }

        $catMax = $category->ad_images_max;
        if ($catMax !== null && $catMax !== '') {
            return max(1, (int) $catMax);
        }

        return self::DEFAULT_USER_UPLOAD_MAX_IMAGES;
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public static function galleryPathsToUrls(array $paths, ?Request $request = null): array
    {
        $paths = array_values(array_filter(array_map('strval', $paths), fn (string $p) => trim($p) !== ''));

        return array_values(array_map(fn (string $p) => self::storageUrlForPath($p, $request), $paths));
    }
}
