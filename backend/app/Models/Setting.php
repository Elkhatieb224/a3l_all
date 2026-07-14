<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
    ];

    public static function get($key, $default = null)
    {
        $setting = Cache::rememberForever(self::cacheKey($key), function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'number' => (float) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set($key, $value, $group = 'general', $type = 'text')
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
            ]
        );

        Cache::forget(self::cacheKey($key));
        self::forgetRelatedApiCaches($key);

        return $setting;
    }

    private static function cacheKey(string $key): string
    {
        return "settings:value:{$key}";
    }

    private static function forgetRelatedApiCaches(string $key): void
    {
        $locales = array_keys((array) config('app.available_locales', []));

        if (str_starts_with($key, 'privacy_policy_')) {
            foreach ($locales as $locale) {
                Cache::forget("api:legal:privacy:v1:{$locale}");
            }
        }

        if (str_starts_with($key, 'terms_conditions_')) {
            foreach ($locales as $locale) {
                Cache::forget("api:legal:terms:v1:{$locale}");
            }
        }
    }
}

