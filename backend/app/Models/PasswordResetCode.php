<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'is_used',
        'expires_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    public static function generateCode($email)
    {
        self::where('email', $email)
            ->where('is_used', false)
            ->delete();

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);
    }
}
