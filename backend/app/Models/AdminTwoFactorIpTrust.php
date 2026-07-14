<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTwoFactorIpTrust extends Model
{
    protected $fillable = [
        'admin_id',
        'ip_address',
        'trusted_until',
    ];

    protected $casts = [
        'trusted_until' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public static function isTrusted(int $adminId, string $ip): bool
    {
        return static::where('admin_id', $adminId)
            ->where('ip_address', $ip)
            ->where('trusted_until', '>', now())
            ->exists();
    }

    public static function trust(int $adminId, string $ip, ?int $minutes = null): void
    {
        $minutes = $minutes ?? (int) config('admin_two_factor.trust_ip_ttl_minutes', 60);

        static::updateOrCreate(
            [
                'admin_id' => $adminId,
                'ip_address' => $ip,
            ],
            [
                'trusted_until' => now()->addMinutes(max(1, $minutes)),
            ]
        );
    }

    public static function forgetForAdmin(int $adminId): void
    {
        static::where('admin_id', $adminId)->delete();
    }
}
