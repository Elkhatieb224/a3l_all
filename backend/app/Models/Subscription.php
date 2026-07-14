<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'starts_at',
        'expires_at',
        'status',
        'ads_used',
        'featured_ads_used',
        'urgent_ads_used',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /** Whether this subscription was granted by admin (not paid by user). */
    public function isAdminGranted(): bool
    {
        $p = $this->payment;
        return $p && $p->payment_method === 'admin_grant';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', now());
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function hasAdsRemaining()
    {
        return $this->ads_used < $this->package->ads_limit;
    }

    /** Whether the user can create more featured ads (package allows it and limit not reached). */
    public function hasFeaturedAdsRemaining(): bool
    {
        if (!$this->package->featured_ads) {
            return false;
        }
        $limit = (int) ($this->package->featured_ads_limit ?? 0);
        if ($limit <= 0) {
            return true; // 0 or null = unlimited
        }
        return (int) ($this->featured_ads_used ?? 0) < $limit;
    }

    /** Whether the user can create more urgent ads (package allows it and limit not reached). */
    public function hasUrgentAdsRemaining(): bool
    {
        if (!$this->package->urgent_ads) {
            return false;
        }
        $limit = (int) ($this->package->urgent_ads_limit ?? 0);
        if ($limit <= 0) {
            return true; // 0 or null = unlimited
        }
        return (int) ($this->urgent_ads_used ?? 0) < $limit;
    }
}

