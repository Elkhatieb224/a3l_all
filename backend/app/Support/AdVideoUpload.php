<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class AdVideoUpload
{
    public static function maxDurationSeconds(): int
    {
        $v = (int) Setting::get('ad_video_max_duration_seconds', 60);

        return max(5, min($v, 600));
    }

    public static function maxSizeBytes(): int
    {
        $mb = (int) Setting::get('ad_video_max_size_mb', 50);
        $mb = max(1, min($mb, 500));

        return $mb * 1024 * 1024;
    }

    public static function maxSizeKbForValidator(): int
    {
        return (int) max(1024, ceil(self::maxSizeBytes() / 1024));
    }

    /**
     * @return list<string>
     */
    public static function validate(?UploadedFile $file): array
    {
        if ($file === null) {
            return [];
        }
        if (! $file->isValid()) {
            return [__('frontend.ads.video_upload_invalid')];
        }

        $errs = [];
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        $allowedExt = ['mp4', 'mov', 'webm'];

        $extOk = in_array($ext, $allowedExt, true);
        $mimeOk = str_starts_with($mime, 'video/')
            && (str_contains($mime, 'mp4') || str_contains($mime, 'quicktime') || str_contains($mime, 'webm'));

        if (! $extOk && ! $mimeOk) {
            $errs[] = __('frontend.ads.video_invalid_type');
        }

        if ($file->getSize() > self::maxSizeBytes()) {
            $errs[] = __('frontend.ads.video_too_large', ['max' => (int) (self::maxSizeBytes() / 1024 / 1024)]);
        }

        $durationErr = self::durationError($file);
        if ($durationErr !== null) {
            $errs[] = $durationErr;
        }

        return $errs;
    }

    private static function durationError(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_file($path)) {
            return null;
        }

        $seconds = self::probeDurationSeconds($path);
        if ($seconds === null) {
            return null;
        }

        if ($seconds > (float) self::maxDurationSeconds() + 0.75) {
            return __('frontend.ads.video_too_long', ['seconds' => self::maxDurationSeconds()]);
        }

        return null;
    }

    private static function probeDurationSeconds(string $realPath): ?float
    {
        $configured = env('FFPROBE_PATH');
        $bin = is_string($configured) && $configured !== '' ? $configured : 'ffprobe';
        if (! self::binaryResolvable($bin)) {
            return null;
        }

        try {
            $process = new Process([$bin, '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', $realPath]);
            $process->setTimeout(25);
            $process->run();
            if (! $process->isSuccessful()) {
                return null;
            }
            $out = trim($process->getOutput());
            if ($out === '' || ! is_numeric($out)) {
                return null;
            }
            $sec = (float) $out;

            return (is_finite($sec) && $sec > 0) ? $sec : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function binaryResolvable(string $bin): bool
    {
        if (str_contains($bin, '/') || str_contains($bin, '\\')) {
            return is_file($bin) && is_executable($bin);
        }

        return (new ExecutableFinder())->find($bin) !== null;
    }
}
