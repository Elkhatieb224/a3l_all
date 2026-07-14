<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ProcessSavedSearchMatchesJob;

class Ad extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid',
        'user_id',
        'category_id',
        'subcategory_id',
        'title',
        'description',
        'price',
        'currency',
        'price_type',
        'location_country',
        'location_state',
        'location_state_code',
        'location_city',
        'location_city_code',
        'location_district',
        'location_district_code',
        'location_address',
        'latitude',
        'longitude',
        'location_input_method',
        'show_location',
        'images',
        'video',
        'custom_fields',
        'status',
        'published_at',
        'expires_at',
        'views_count',
        'is_featured',
        'is_urgent',
        'rejection_reason',
        'pending_changes',
        'account_status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ad) {
            if (empty($ad->uid)) {
                $ad->uid = static::generateUniqueUid();
            }
        });

        static::saved(function (Ad $ad) {
            self::bumpApiListingCaches();
            $shouldProcessSavedSearches = ($ad->wasRecentlyCreated && $ad->status === 'active')
                || ($ad->wasChanged('status') && $ad->status === 'active');
            if ($shouldProcessSavedSearches) {
                ProcessSavedSearchMatchesJob::dispatch($ad->id);
            }
        });

        static::deleted(function () {
            self::bumpApiListingCaches();
        });
    }

    /**
     * Generate a unique 9-digit UID
     */
    public static function generateUniqueUid(): string
    {
        do {
            $uid = str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        } while (static::where('uid', $uid)->exists());

        return $uid;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    protected $casts = [
        'images' => 'array',
        'custom_fields' => 'array',
        'pending_changes' => 'array',
        'is_featured' => 'boolean',
        'is_urgent' => 'boolean',
        'show_location' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function isFavoriteBy($userId)
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }


    public function getEffectivePriceAttribute()
    {
        $pending = $this->pending_changes ?? [];
        if (isset($pending['price'])) {
            $p = $pending['price'];
            if (is_array($p) && array_key_exists('value', $p)) {
                $v = $p['value'] ?? null;
                return $v !== '' && $v !== null && is_numeric($v) ? (float) $v : $this->price;
            }
            return is_numeric($p) ? (float) $p : $this->price;
        }
        return $this->price;
    }


    public function getEffectiveCurrencyAttribute()
    {
        $pending = $this->pending_changes ?? [];
        if (isset($pending['price']) && is_array($pending['price']) && !empty($pending['price']['currency'])) {
            return $pending['price']['currency'];
        }
        if (!empty($pending['currency'])) {
            return $pending['currency'];
        }
        return $this->currency;
    }

    /**
     * Get display price: from ad->price or custom_fields (price/salary or any number+currency field).
     * Returns formatted price string or null if no price.
     */
    public function getDisplayPriceAttribute(): ?string
    {
        $currency = $this->currency ?? \App\Models\Setting::get('default_currency', 'SYP');

        // 1. Main price column (only if real numeric value)
        if ($this->price !== null && $this->price !== '' && is_numeric($this->price)) {
            $p = is_numeric($this->price) ? (float) $this->price : null;
            if ($p !== null) {
                return format_price($p, 2, $currency);
            }
        }

        $cf = $this->custom_fields ?? [];
        if (!is_array($cf)) {
            return null;
        }

        $check = function ($v) use ($currency) {
            if (!is_array($v)) {
                if (is_numeric($v) && (string)$v !== '') {
                    return format_price((float) $v, 2, $currency);
                }
                return null;
            }
            if (!empty($v['tbd'])) {
                return __("frontend.ads.price_tbd");
            }
            $val = $v['value'] ?? null;
            if (is_array($val)) {
                return null;
            }
            if ($val === null || $val === '' || (string) $val === '' || (string) $val === 'null') {
                return __("frontend.ads.price_tbd");
            }
            if (is_numeric($val)) {
                return format_price((float) $val, 2, $v['currency'] ?? $currency);
            }
            return null;
        };

        // 2. Preferred keys first
        foreach (['price', 'salary'] as $key) {
            if (!isset($cf[$key])) continue;
            $result = $check($cf[$key]);
            if ($result !== null) {
                return $result;
            }
        }
        // 3. Any other custom field with currency/value (number+currency shape)
        foreach ($cf as $key => $v) {
            if (in_array($key, ['price', 'salary'], true)) continue;
            if (!is_array($v) || (!array_key_exists('currency', $v) && !array_key_exists('value', $v) && empty($v['tbd']))) continue;
            $result = $check($v);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Invalidate API ad listings cache (index + home payload keys use these versions).
     */
    public static function bumpApiListingCaches(): void
    {
        Cache::increment('api:ads:index:version');
        Cache::increment('api:home:ads:version');
    }
}

