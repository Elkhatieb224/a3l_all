<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class AdminTwoFactorChallenge extends Model
{
    public const TYPE_LOGIN = 'login';

    public const TYPE_SETUP = 'setup';

    protected $fillable = [
        'admin_id',
        'type',
        'email',
        'code_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * إنشاء تحدٍ جديد وحذف السابق لنفس المسؤول والنوع. يعيد الرقم الصريح لإرساله بالبريد فقط.
     */
    public static function createChallenge(int $adminId, string $type, string $email): string
    {
        static::where('admin_id', $adminId)->where('type', $type)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        static::create([
            'admin_id' => $adminId,
            'type' => $type,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('admin_two_factor.challenge_ttl_minutes', 10)),
        ]);

        return $code;
    }

    public static function verify(int $adminId, string $type, string $plainCode): ?self
    {
        $row = static::where('admin_id', $adminId)
            ->where('type', $type)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $row || ! Hash::check($plainCode, $row->code_hash)) {
            return null;
        }

        return $row;
    }
}
