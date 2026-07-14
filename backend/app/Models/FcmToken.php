<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'device_type',
        'device_id',
        'last_used_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fcmToken) {
            if (empty($fcmToken->token_hash) && !empty($fcmToken->token)) {
                $fcmToken->token_hash = hash('sha256', $fcmToken->token);
            }
        });

        static::updating(function ($fcmToken) {
            if ($fcmToken->isDirty('token') && !empty($fcmToken->token)) {
                $fcmToken->token_hash = hash('sha256', $fcmToken->token);
            }
        });
    }

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
