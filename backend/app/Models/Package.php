<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'name_tr',
        'description_ar',
        'description_en',
        'description_tr',
        'price',
        'currency',
        'duration_days',
        'ads_limit',
        'featured_ads',
        'featured_ads_limit',
        'urgent_ads',
        'urgent_ads_limit',
        'priority_support',
        'homepage_display',
        'features',
        'is_active',
        'order',
    ];

    protected $casts = [
        'features' => 'array',
        'featured_ads' => 'boolean',
        'urgent_ads' => 'boolean',
        'priority_support' => 'boolean',
        'homepage_display' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getName($locale = 'ar')
    {
        return $this->{"name_$locale"} ?? $this->name_ar;
    }

    public function getDescription($locale = 'ar')
    {
        return $this->{"description_$locale"} ?? $this->description_ar ?? '';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}

