<?php

namespace App\Models;

use App\Support\LoginAttemptLimiter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginIpBlock extends Model
{
    protected $fillable = [
        'ip_address',
        'channel',
        'lockout_cycles',
        'is_permanent',
        'blocked_until',
        'last_failed_at',
        'last_lockout_at',
        'attempt_logs',
        'admin_notes',
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'blocked_until' => 'datetime',
        'last_failed_at' => 'datetime',
        'last_lockout_at' => 'datetime',
        'attempt_logs' => 'array',
    ];

    public const MAX_ATTEMPT_LOG_ENTRIES = 100;

    public static function isIpBlocked(string $ip, string $channel): bool
    {
        $row = static::where('ip_address', $ip)->where('channel', $channel)->first();
        if (! $row) {
            return false;
        }
        if ($row->is_permanent) {
            return true;
        }
        if ($row->blocked_until && $row->blocked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * أقصى ثوانٍ متبقية للحظر (قاعدة البيانات أو الكاش)، لاستخدام Retry-After.
     */
    public static function blockedSecondsRemainingForRequest(string $channel, Request $request): int
    {
        $ip = $request->ip();
        $row = static::where('ip_address', $ip)->where('channel', $channel)->first();
        $db = 0;
        if ($row) {
            if ($row->is_permanent) {
                $db = 86400;
            } elseif ($row->blocked_until && $row->blocked_until->isFuture()) {
                $db = max(1, $row->blocked_until->getTimestamp() - now()->getTimestamp());
            }
        }
        $cache = RateLimiter::availableIn(LoginAttemptLimiter::key($channel, $request));

        return max($db, $cache);
    }

    public static function appendFailedAttempt(string $ip, string $channel, Request $request, ?string $email): void
    {
        $row = static::firstOrNew([
            'ip_address' => $ip,
            'channel' => $channel,
        ]);

        $entry = [
            'at' => now()->toIso8601String(),
            'email' => $email ? mb_substr($email, 0, 255) : null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'accept_language' => mb_substr((string) $request->header('Accept-Language'), 0, 500),
            'forwarded_for' => mb_substr((string) $request->header('X-Forwarded-For'), 0, 500),
            'forwarded_proto' => mb_substr((string) $request->header('X-Forwarded-Proto'), 0, 32),
            'referer' => mb_substr((string) $request->header('Referer'), 0, 1000),
            'real_ip' => $ip,
            'url' => mb_substr((string) $request->fullUrl(), 0, 2000),
            'method' => $request->method(),
            'request_content_type' => mb_substr((string) $request->header('Content-Type'), 0, 255),
        ];

        $logs = $row->attempt_logs ?? [];
        $logs[] = $entry;
        if (count($logs) > self::MAX_ATTEMPT_LOG_ENTRIES) {
            $logs = array_slice($logs, -self::MAX_ATTEMPT_LOG_ENTRIES);
        }

        $row->attempt_logs = $logs;
        $row->last_failed_at = now();
        $row->save();
    }

    /**
     * بعد تجاوز عدد المحاولات الفاشلة: أول مرة حظر مؤقت، ثانية (وما بعدها) حظر دائم.
     */
    public static function escalateAfterRepeatedLockout(string $ip, string $channel): void
    {
        $row = static::firstOrNew([
            'ip_address' => $ip,
            'channel' => $channel,
        ]);

        $row->lockout_cycles = (int) $row->lockout_cycles + 1;

        if ($row->lockout_cycles >= 2) {
            $row->is_permanent = true;
            $row->blocked_until = null;
        } else {
            $row->is_permanent = false;
            $row->blocked_until = now()->addSeconds(LoginAttemptLimiter::decaySeconds());
        }

        $row->last_lockout_at = now();
        $row->save();
    }

    public static function releaseFromAdmin(string $ip, string $channel): void
    {
        $row = static::where('ip_address', $ip)->where('channel', $channel)->first();
        if ($row) {
            $row->is_permanent = false;
            $row->lockout_cycles = 0;
            $row->blocked_until = null;
            $row->save();
        }
        RateLimiter::clear(LoginAttemptLimiter::cacheKeyForIp($channel, $ip));
    }

    /**
     * بعد تسجيل دخول ناجح: إلغاء الحظر المؤقت فقط وتصفير الدورات (لا يمس الحظر الدائم).
     */
    public static function clearNonPermanentAfterSuccessfulLogin(string $ip, string $channel): void
    {
        $row = static::where('ip_address', $ip)->where('channel', $channel)->first();
        if (! $row || $row->is_permanent) {
            return;
        }
        $row->lockout_cycles = 0;
        $row->blocked_until = null;
        $row->save();
    }

    public static function markPermanentByAdmin(string $ip, string $channel): void
    {
        $row = static::firstOrNew([
            'ip_address' => $ip,
            'channel' => $channel,
        ]);
        $row->is_permanent = true;
        $row->blocked_until = null;
        $row->save();
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            LoginAttemptLimiter::CHANNEL_API => 'API',
            LoginAttemptLimiter::CHANNEL_WEB => 'Web',
            LoginAttemptLimiter::CHANNEL_ADMIN => 'Admin',
            default => $this->channel,
        };
    }
}
