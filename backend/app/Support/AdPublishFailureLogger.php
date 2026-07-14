<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * يسجّل محاولات إنشاء إعلان التي تفشل (موقع ويب أو تطبيق عبر API) في ملف مخصّص.
 *
 * @see config/logging.php channel ad_publish_failures
 */
final class AdPublishFailureLogger
{
    public const SOURCE_WEB = 'web';

    public const SOURCE_API = 'api';

    public static function log(
        string $source,
        Request $request,
        string $failureCode,
        string $reasonSummary,
        array $context = []
    ): void {
        $user = Auth::user();

        $base = [
            'at' => now()->toIso8601String(),
            'source' => $source,
            'failure_code' => $failureCode,
            'reason' => $reasonSummary,
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'user_agent' => self::truncate((string) $request->userAgent(), 400),
        ];

        $route = $request->route();
        if ($route !== null) {
            $base['route_name'] = $route->getName();
        }

        $merged = array_merge($base, self::sanitizeContext($context));

        Log::channel('ad_publish_failures')->warning(
            '[ad_publish_failure] '.$failureCode.' — '.$reasonSummary,
            $merged
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function sanitizeContext(array $context): array
    {
        foreach (array_keys($context) as $k) {
            $lk = strtolower((string) $k);
            if (str_contains($lk, 'password')) {
                unset($context[$k]);
            }
        }

        return $context;
    }

    private static function truncate(?string $s, int $max): string
    {
        if ($s === null || $s === '') {
            return '';
        }

        return mb_strlen($s) <= $max ? $s : mb_substr($s, 0, $max).'…';
    }
}
