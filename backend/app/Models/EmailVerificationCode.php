<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailVerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'email',
        'is_used',
        'expires_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid()
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                    ->where('expires_at', '>', now());
    }

    public static function generateCode($userId, $email)
    {
        // Delete old unused codes for this user
        self::where('user_id', $userId)
            ->where('is_used', false)
            ->delete();

        // Generate 6-digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'user_id' => $userId,
            'code' => $code,
            'email' => $email,
            'expires_at' => now()->addMinutes(15), // Code expires in 15 minutes
        ]);
    }
}
