<?php

namespace App\Jobs;

use App\Models\Ad;
use App\Services\WebPImageService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * تحويل صور الإعلان المرفوعة (jpeg/png/gif) إلى WebP بعد إرسال الاستجابة للمستخدم.
 * يُستدعى عبر dispatch(...)->afterResponse() ولا يحتاج عامل طابور.
 */
class ProcessAdImagesJob
{
    use Dispatchable;

    /**
     * @param  list<string>  $storedPaths  المسارات النسبية كما حُفظت في التخزين العام
     */
    public function __construct(
        public int $adId,
        public array $storedPaths,
    ) {}

    public function handle(WebPImageService $webp): void
    {
        if ($this->storedPaths === []) {
            return;
        }

        try {
            $this->runConversion($webp);
        } catch (\Throwable $e) {
            Log::error('ProcessAdImagesJob failed', [
                'ad_id' => $this->adId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function runConversion(WebPImageService $webp): void
    {
        $ad = Ad::query()->find($this->adId);
        if ($ad === null) {
            return;
        }

        if (! $webp->supportsWebPEncoding()) {
            Log::warning('ProcessAdImagesJob: WebP encoding not available, skipping', [
                'ad_id' => $this->adId,
            ]);

            return;
        }

        /** @var array<string, string> $map مسار قديم => مسار webp */
        $map = [];

        foreach ($this->storedPaths as $rel) {
            $rel = (string) $rel;
            if ($rel === '') {
                continue;
            }

            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            if ($ext === 'webp' || ! in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                continue;
            }

            $full = Storage::disk('public')->path($rel);
            if (! is_file($full)) {
                continue;
            }

            $newRel = $webp->convertExistingToWebP($full);
            if ($newRel !== null && $newRel !== $rel) {
                @unlink($full);
                $map[$rel] = $newRel;
            }
        }

        if ($map === []) {
            return;
        }

        $apply = static function (?array $list) use ($map): array {
            if ($list === null || $list === []) {
                return $list ?? [];
            }

            return array_values(array_map(static fn ($p) => $map[(string) $p] ?? $p, $list));
        };

        $ad->images = $apply($ad->images ?? []);

        $pc = $ad->pending_changes;
        if (is_array($pc) && isset($pc['images']) && is_array($pc['images'])) {
            $pc['images'] = $apply($pc['images']);
            $ad->pending_changes = $pc;
        }

        $ad->save();
    }
}

