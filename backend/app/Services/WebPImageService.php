<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebPImageService
{
    /** @var int WebP quality 0-100 (85 = جودة عالية مع ضغط جيد) */
    protected int $quality = 85;


    protected int $maxLongEdge = 1920;

    protected array $convertibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    /** @var array<string, true> */
    protected array $convertibleMimeTypes = [
        'image/jpeg' => true,
        'image/jpg' => true,
        'image/pjpeg' => true,
        'image/png' => true,
        'image/x-png' => true,
        'image/gif' => true,
    ];

    public function supportsWebPEncoding(): bool
    {
        if (! function_exists('imagewebp')) {
            return false;
        }
        $info = @gd_info();

        return ! empty($info['WebP Support']);
    }

    /**
     * تحويل ملف مرفوع لـ WebP وحفظه
     * @return string|null المسار النسبي أو null عند الفشل
     */
    public function convertAndStore(UploadedFile $file, string $directory): ?string
    {
        if (! $this->supportsWebPEncoding()) {
            Log::error('WebPImageService: GD does not support WebP output (imagewebp / WebP Support).');

            return null;
        }

        if (! $this->isConvertible($file)) {
            return null;
        }

        $tempPath = $file->getRealPath() ?: $file->getPathname();
        if (! $tempPath || ! is_readable($tempPath)) {
            Log::warning('WebPImageService: Uploaded temp path not readable', [
                'realpath' => $file->getRealPath(),
                'pathname' => $file->getPathname(),
            ]);

            return null;
        }

        $kind = $this->resolveKindForUpload($file, $tempPath);
        if ($kind === null) {
            Log::warning('WebPImageService: Could not detect image type for upload', [
                'client_ext' => $file->getClientOriginalExtension(),
                'mime' => $file->getMimeType(),
                'temp' => $tempPath,
            ]);

            return null;
        }
        if (! $this->canDecodeSafely($tempPath)) {
            Log::warning('WebPImageService: Skipped upload conversion due to decode memory guard.', [
                'path' => $tempPath,
            ]);

            return null;
        }

        $directory = rtrim($directory, '/');
        $filename = Str::random(40) . '.webp';
        $relativePath = $directory ? $directory . '/' . $filename : $filename;

        $image = $this->decodeImage($tempPath, $kind);
        if (! $image) {
            Log::warning('WebPImageService: Could not decode uploaded image', ['path' => $tempPath, 'kind' => $kind]);

            return null;
        }

        $image = $this->limitLongEdge($image);
        if (! $this->canEncodeSafely($image)) {
            if (function_exists('imagedestroy')) {
                imagedestroy($image);
            }
            Log::warning('WebPImageService: Skipped upload conversion due to memory guard.');

            return null;
        }

        $destPath = Storage::disk('public')->path($relativePath);
        $dir = dirname($destPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $success = @imagewebp($image, $destPath, $this->quality);
        if (function_exists('imagedestroy')) {
            imagedestroy($image);
        }

        if (! $success || ! is_file($destPath) || filesize($destPath) < 1) {
            if (is_file($destPath)) {
                @unlink($destPath);
            }

            return null;
        }

        @chmod($destPath, 0644);

        return $relativePath;
    }

    /**
     * تحويل ملف موجود على الديسك لـ WebP
     * @param string $fullPath المسار الكامل للملف
     * @return string|null المسار النسبي الجديد أو null
     */
    public function convertExistingToWebP(string $fullPath): ?string
    {
        if (! $this->supportsWebPEncoding()) {
            return null;
        }

        if (! file_exists($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $kind = $this->normalizeExtensionToKind($extension) ?? $this->detectKindFromFile($fullPath);
        if ($kind === null || ! in_array($kind, ['jpeg', 'png', 'gif'], true)) {
            return null;
        }

        $publicRoot = rtrim(Storage::disk('public')->path(''), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $fileDir = dirname($fullPath) . DIRECTORY_SEPARATOR;
        $relativeDir = str_replace($publicRoot, '', $fileDir);
        $relativeDir = trim(str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir), '/');
        $relativeDir = $relativeDir !== '' ? $relativeDir . '/' : '';

        return $this->convertFileToWebP($fullPath, $relativeDir, $kind);
    }

    /**
     * تحويل ملف إلى WebP
     */
    protected function convertFileToWebP(string $sourcePath, string $directory, ?string $kind = null): ?string
    {
        if (! $this->supportsWebPEncoding()) {
            return null;
        }

        $kind ??= $this->normalizeExtensionToKind(strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)))
            ?? $this->detectKindFromFile($sourcePath);
        if ($kind === null) {
            return null;
        }
        if (! $this->canDecodeSafely($sourcePath)) {
            Log::warning('WebPImageService: Skipped file conversion due to decode memory guard', [
                'path' => $sourcePath,
            ]);

            return null;
        }

        $image = $this->decodeImage($sourcePath, $kind);
        if (! $image) {
            Log::warning('WebPImageService: Could not load image', ['path' => $sourcePath, 'kind' => $kind]);

            return null;
        }

        $image = $this->limitLongEdge($image);
        if (! $this->canEncodeSafely($image)) {
            if (function_exists('imagedestroy')) {
                imagedestroy($image);
            }
            Log::warning('WebPImageService: Skipped file conversion due to memory guard', [
                'path' => $sourcePath,
            ]);

            return null;
        }

        $filename = pathinfo($sourcePath, PATHINFO_FILENAME) . '.webp';
        $directory = rtrim($directory, '/');
        $relativePath = $directory ? $directory . '/' . $filename : $filename;
        $destPath = Storage::disk('public')->path($relativePath);

        $dir = dirname($destPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $success = @imagewebp($image, $destPath, $this->quality);
        if (function_exists('imagedestroy')) {
            imagedestroy($image);
        }

        if (! $success || ! is_file($destPath) || filesize($destPath) < 1) {
            if (is_file($destPath)) {
                @unlink($destPath);
            }

            return null;
        }

        @chmod($destPath, 0644);

        return $relativePath;
    }

    protected function resolveKindForUpload(UploadedFile $file, string $tempPath): ?string
    {
        $fromExt = $this->normalizeExtensionToKind($file->getClientOriginalExtension());
        if ($fromExt !== null) {
            return $fromExt;
        }

        $mime = strtolower((string) $file->getMimeType());
        $fromMime = match ($mime) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpeg',
            'image/png', 'image/x-png' => 'png',
            'image/gif' => 'gif',
            default => null,
        };
        if ($fromMime !== null) {
            return $fromMime;
        }

        return $this->detectKindFromFile($tempPath);
    }

    protected function normalizeExtensionToKind(?string $extension): ?string
    {
        $extension = strtolower((string) $extension);

        return match ($extension) {
            'jpg', 'jpeg' => 'jpeg',
            'png' => 'png',
            'gif' => 'gif',
            default => null,
        };
    }

    protected function detectKindFromFile(string $path): ?string
    {
        if (! is_readable($path) || ! function_exists('exif_imagetype')) {
            return null;
        }

        $type = @exif_imagetype($path);

        return match ($type) {
            IMAGETYPE_JPEG => 'jpeg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            default => null,
        };
    }

    /**
     * @return \GdImage|resource|null
     */
    protected function decodeImage(string $path, string $kind)
    {
        $image = null;

        switch ($kind) {
            case 'jpeg':
                $image = @imagecreatefromjpeg($path);
                if ($image) {
                    $image = $this->applyExifOrientation($image, $path);
                }
                break;
            case 'png':
                $image = @imagecreatefrompng($path);
                if ($image) {
                    if (function_exists('imagepalettetotruecolor')) {
                        imagepalettetotruecolor($image);
                    }
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'gif':
                $image = @imagecreatefromgif($path);
                if ($image && function_exists('imagepalettetotruecolor')) {
                    imagepalettetotruecolor($image);
                }
                break;
        }

        return $image ?: null;
    }

    /**
     * Apply EXIF orientation for JPEG images taken by phones.
     * Without this, portrait photos can appear rotated after conversion.
     *
     * @param \GdImage|resource $image
     * @return \GdImage|resource
     */
    protected function applyExifOrientation($image, string $path)
    {
        if (!function_exists('exif_read_data') || !is_readable($path)) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if ($orientation <= 1) {
            return $image;
        }

        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                return $image;
            case 3:
                $rotated = @imagerotate($image, 180, 0);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                return $image;
            case 5:
                imageflip($image, IMG_FLIP_VERTICAL);
                $rotated = @imagerotate($image, -90, 0);
                break;
            case 6:
                $rotated = @imagerotate($image, -90, 0);
                break;
            case 7:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                $rotated = @imagerotate($image, -90, 0);
                break;
            case 8:
                $rotated = @imagerotate($image, 90, 0);
                break;
            default:
                return $image;
        }

        if ($rotated === false) {
            return $image;
        }

        if (function_exists('imagedestroy')) {
            imagedestroy($image);
        }

        return $rotated;
    }

    /**
     * تصغير الصورة إذا تجاوز أطول ضلع maxLongEdge (أسرع بكثير من ترميز ملايين البكسل).
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    protected function limitLongEdge($image)
    {
        $max = $this->maxLongEdge;
        if ($max <= 0 || ! $image) {
            return $image;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        if ($w < 1 || $h < 1) {
            return $image;
        }

        $long = max($w, $h);
        if ($long <= $max) {
            return $image;
        }

        $ratio = $max / $long;
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        // Prevent OOM during scaling: we already hold original image in memory,
        // so only scale when we can safely allocate the resized buffer too.
        if (! $this->canScaleSafely($w, $h, $nw, $nh)) {
            return $image;
        }

        $scaled = defined('IMG_BILINEAR_FIXED')
            ? @imagescale($image, $nw, $nh, IMG_BILINEAR_FIXED)
            : @imagescale($image, $nw, $nh);
        if ($scaled === false) {
            return $image;
        }

        if (function_exists('imagedestroy')) {
            imagedestroy($image);
        }

        return $scaled;
    }

    protected function canScaleSafely(int $w, int $h, int $nw, int $nh): bool
    {
        $limit = $this->getMemoryLimitBytes();
        if ($limit <= 0) {
            return true;
        }

        $currentUsage = memory_get_usage(true);
        $available = $limit - $currentUsage;
        if ($available <= 0) {
            return false;
        }

        // Rough estimate for GD truecolor buffers + internal overhead.
        $estimated = (int) (($w * $h * 5) + ($nw * $nh * 5) + (8 * 1024 * 1024));

        return $estimated < $available;
    }

    /**
     * @param \GdImage|resource $image
     */
    protected function canEncodeSafely($image): bool
    {
        if (! $image) {
            return false;
        }

        $limit = $this->getMemoryLimitBytes();
        if ($limit <= 0) {
            return true;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        if ($w < 1 || $h < 1) {
            return false;
        }

        $currentUsage = memory_get_usage(true);
        $available = $limit - $currentUsage;
        if ($available <= 0) {
            return false;
        }

        // Conservative estimate for encoder temporary buffers.
        $estimated = (int) (($w * $h * 3) + (8 * 1024 * 1024));

        return $estimated < $available;
    }

    protected function getMemoryLimitBytes(): int
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') {
            return -1;
        }

        $last = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($last) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    protected function canDecodeSafely(string $path): bool
    {
        $limit = $this->getMemoryLimitBytes();
        if ($limit <= 0) {
            return true;
        }

        $size = @getimagesize($path);
        if (!is_array($size) || count($size) < 2) {
            return false;
        }

        $w = (int) ($size[0] ?? 0);
        $h = (int) ($size[1] ?? 0);
        if ($w < 1 || $h < 1) {
            return false;
        }

        $currentUsage = memory_get_usage(true);
        $available = $limit - $currentUsage;
        if ($available <= 0) {
            return false;
        }

        // Estimate buffer required for decoded truecolor image + overhead.
        $estimated = (int) (($w * $h * 5) + (8 * 1024 * 1024));

        return $estimated < $available;
    }

    /**
     * هل الملف صورة قابلة للتحويل؟
     */
    public function isConvertible(UploadedFile $file): bool
    {
        if ($this->normalizeExtensionToKind($file->getClientOriginalExtension()) !== null) {
            return true;
        }

        $mime = strtolower((string) $file->getMimeType());

        return isset($this->convertibleMimeTypes[$mime]);
    }

    /**
     * هل المسار يشير إلى صورة قابلة للتحويل؟
     */
    public function pathIsConvertible(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, $this->convertibleExtensions, true);
    }
}
