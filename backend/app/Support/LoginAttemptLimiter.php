<?php

namespace App\Support;

use App\Models\LoginIpBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginAttemptLimiter
{
    public const CHANNEL_API = 'api';

    public const CHANNEL_WEB = 'web';

    public const CHANNEL_ADMIN = 'admin';

    public static function cacheKeyForIp(string $channel, string $ip): string
    {
        return 'login-fail:'.$channel.':'.$ip;
    }

    public static function key(string $channel, Request $request): string
    {
        return self::cacheKeyForIp($channel, $request->ip());
    }


    public static function maxAttempts(): int
    {
        return config('login_throttle.max_attempts', 5);
    }

    public static function decaySeconds(): int
    {
        return max(60, (int) config('login_throttle.decay_minutes', 15) * 60);
    }

    public static function tooManyAttempts(string $channel, Request $request): bool
    {
        if (LoginIpBlock::isIpBlocked($request->ip(), $channel)) {
            return true;
        }

        return RateLimiter::tooManyAttempts(
            self::key($channel, $request),
            self::maxAttempts()
        );
    }

    public static function availableSeconds(string $channel, Request $request): int
    {
        return LoginIpBlock::blockedSecondsRemainingForRequest($channel, $request);
    }

    public static function recordFailedAttempt(string $channel, Request $request, ?string $email = null): void
    {
        $ip = $request->ip();
        $cacheKey = self::key($channel, $request);

        RateLimiter::hit($cacheKey, self::decaySeconds());
        LoginIpBlock::appendFailedAttempt($ip, $channel, $request, $email);

        if (RateLimiter::attempts($cacheKey) >= self::maxAttempts()) {
            LoginIpBlock::escalateAfterRepeatedLockout($ip, $channel);
        }
    }

    public static function clear(string $channel, Request $request): void
    {
        RateLimiter::clear(self::key($channel, $request));
        LoginIpBlock::clearNonPermanentAfterSuccessfulLogin($request->ip(), $channel);
    }


    public static function lockoutMinutes(string $channel, Request $request): int
    {
        $sec = self::availableSeconds($channel, $request);

        return max(1, (int) ceil($sec / 60));
    }
}
