<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Report;
use App\Models\VerificationRequest;
use App\Models\Message;
use App\Models\Admin;
use App\Services\WebPImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConvertImagesToWebPCommand extends Command
{
    protected $signature = 'images:convert-to-webp 
                            {--dry-run : عرض ما سيتم دون تنفيذ}
                            {--force : تحويل حتى لو وجد webp موجود}';

    protected $description = 'تحويل جميع الصور الموجودة (jpg, png, gif) إلى WebP وتحديث قاعدة البيانات';

    protected WebPImageService $webpService;

    public function __construct(WebPImageService $webpService)
    {
        parent::__construct();
        $this->webpService = $webpService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $publicRoot = rtrim(Storage::disk('public')->path(''), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $converted = 0;
        $failed = 0;
        $updates = []; // old_path => new_path

        $this->info('جارٍ البحث عن الصور...');

        $files = $this->findImageFiles($publicRoot);

        foreach ($files as $fullPath) {
                $relativePath = str_replace($publicRoot, '', $fullPath);
                $relativePath = str_replace('\\', '/', $relativePath);
                $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $relativePath);

                $webpExists = file_exists($publicRoot . $webpPath);
                if ($webpExists && !$this->option('force')) {
                    // WebP موجود مسبقاً - نحدّث DB ونحذف الأصلي فقط
                    $updates[$relativePath] = $webpPath;
                    $converted++;
                    if (!$dryRun) {
                        @unlink($fullPath);
                    }
                    continue;
                }

                $newPath = $this->webpService->convertExistingToWebP($fullPath);
                if ($newPath) {
                    $updates[$relativePath] = $newPath;
                    $converted++;
                    if (!$dryRun) {
                        @unlink($fullPath);
                    }
                } else {
                    $failed++;
                    $this->warn("فشل التحويل: {$relativePath}");
                }
        }

        $this->info("تم تحويل {$converted} صورة، فشل {$failed}.");

        if (empty($updates)) {
            $this->info('لا توجد صور جديدة للتحويل.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->table(['المسار القديم', 'المسار الجديد'], collect($updates)->map(fn($v, $k) => [$k, $v])->values()->toArray());
            $this->info('وضع dry-run: لم يتم تنفيذ أي تغييرات.');
            return Command::SUCCESS;
        }

        $this->info('جارٍ تحديث قاعدة البيانات...');

        $this->updateDbPaths($updates);

        $this->info('تم الانتهاء بنجاح.');
        return Command::SUCCESS;
    }

    /**
     * @param array<string, string> $updates old_path => new_path
     */
    protected function updateDbPaths(array $updates): void
    {
        if ($updates === []) {
            return;
        }

        // Ads (images JSON array)
        Ad::query()
            ->whereNotNull('images')
            ->chunkById(100, function ($ads) use ($updates) {
                foreach ($ads as $ad) {
                    $images = $ad->images ?? [];
                    if (!is_array($images) || $images === []) {
                        continue;
                    }

                    $updatedImages = $this->replaceMappedPaths($images, $updates);
                    if ($updatedImages !== $images) {
                        $ad->update(['images' => $updatedImages]);
                    }
                }
            });

        // Users avatar
        foreach ($updates as $oldPath => $newPath) {
            User::where('avatar', $oldPath)->update(['avatar' => $newPath]);
        }

        // Admins avatar
        foreach ($updates as $oldPath => $newPath) {
            Admin::where('avatar', $oldPath)->update(['avatar' => $newPath]);
        }

        // Categories icon
        foreach ($updates as $oldPath => $newPath) {
            Category::where('icon', $oldPath)->update(['icon' => $newPath]);
        }

        // Subcategories icon
        foreach ($updates as $oldPath => $newPath) {
            Subcategory::where('icon', $oldPath)->update(['icon' => $newPath]);
        }

        // Reports (images array)
        Report::query()
            ->whereNotNull('images')
            ->chunkById(100, function ($reports) use ($updates) {
                foreach ($reports as $report) {
                    $images = $report->images ?? [];
                    if (!is_array($images) || $images === []) {
                        continue;
                    }

                    $updatedImages = $this->replaceMappedPaths($images, $updates);
                    if ($updatedImages !== $images) {
                        $report->update(['images' => $updatedImages]);
                    }
                }
            });

        // Verification requests
        foreach ($updates as $oldPath => $newPath) {
            VerificationRequest::where('primary_document_path', $oldPath)->update(['primary_document_path' => $newPath]);
            VerificationRequest::where('storefront_image_path', $oldPath)->update(['storefront_image_path' => $newPath]);
        }
        VerificationRequest::query()
            ->whereNotNull('documents')
            ->chunkById(100, function ($requests) use ($updates) {
                foreach ($requests as $verificationRequest) {
                    $documents = $verificationRequest->documents ?? [];
                    if (!is_array($documents) || $documents === []) {
                        continue;
                    }

                    $updatedDocuments = $this->replaceMappedPaths($documents, $updates);
                    if ($updatedDocuments !== $documents) {
                        $verificationRequest->update(['documents' => $updatedDocuments]);
                    }
                }
            });

        // Messages attachments (path inside array)
        Message::query()
            ->whereNotNull('attachments')
            ->chunkById(100, function ($messages) use ($updates) {
                foreach ($messages as $message) {
                    $attachments = $message->attachments ?? [];
                    if (!is_array($attachments) || $attachments === []) {
                        continue;
                    }

                    $updated = false;
                    foreach ($attachments as $i => $attachment) {
                        $path = is_array($attachment) ? ($attachment['path'] ?? null) : $attachment;
                        if (!is_string($path) || !isset($updates[$path])) {
                            continue;
                        }
                        $newPath = $updates[$path];
                        if (is_array($attachment)) {
                            $attachments[$i]['path'] = $newPath;
                            $attachments[$i]['mime'] = 'image/webp';
                            if (isset($attachments[$i]['type'])) {
                                $attachments[$i]['type'] = 'image/webp';
                            }
                        } else {
                            $attachments[$i] = $newPath;
                        }
                        $updated = true;
                    }

                    if ($updated) {
                        $message->update(['attachments' => $attachments]);
                    }
                }
            });

        // Users storefront_image_path (from verification)
        foreach ($updates as $oldPath => $newPath) {
            User::where('storefront_image_path', $oldPath)->update(['storefront_image_path' => $newPath]);
        }

        // Categories / subcategories: معرض صور الإعلان (JSON)
        Category::query()
            ->whereNotNull('ad_gallery_images')
            ->chunkById(100, function ($categories) use ($updates) {
                foreach ($categories as $category) {
                    $gallery = $category->ad_gallery_images ?? [];
                    if (!is_array($gallery) || $gallery === []) {
                        continue;
                    }

                    $updatedGallery = $this->replaceMappedPaths($gallery, $updates);
                    if ($updatedGallery !== $gallery) {
                        $category->update(['ad_gallery_images' => array_values($updatedGallery)]);
                    }
                }
            });

        Subcategory::query()
            ->whereNotNull('ad_gallery_images')
            ->chunkById(100, function ($subcategories) use ($updates) {
                foreach ($subcategories as $subcategory) {
                    $gallery = $subcategory->ad_gallery_images ?? [];
                    if (!is_array($gallery) || $gallery === []) {
                        continue;
                    }

                    $updatedGallery = $this->replaceMappedPaths($gallery, $updates);
                    if ($updatedGallery !== $gallery) {
                        $subcategory->update(['ad_gallery_images' => array_values($updatedGallery)]);
                    }
                }
            });
    }

    /**
     * @param array<int, mixed> $paths
     * @param array<string, string> $updates
     * @return array<int, mixed>
     */
    private function replaceMappedPaths(array $paths, array $updates): array
    {
        foreach ($paths as $i => $path) {
            if (is_string($path) && isset($updates[$path])) {
                $paths[$i] = $updates[$path];
            }
        }

        return array_values($paths);
    }

    protected function findImageFiles(string $dir, int $depth = 0): array
    {
        $files = [];
        if (!is_dir($dir) || $depth > 5) {
            return $files;
        }
        $skip = ['logs', 'cache', 'framework', '.git'];
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $skip)) {
                continue;
            }
            $path = $dir . $item;
            if (is_dir($path) && !is_link($path)) {
                $files = array_merge($files, $this->findImageFiles($path . DIRECTORY_SEPARATOR, $depth + 1));
            } elseif (preg_match('/\.(jpg|jpeg|png|gif)$/i', $item)) {
                $files[] = $path;
            }
        }
        return $files;
    }
}
