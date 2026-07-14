<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\SellerRating;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'fcm_token',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'password',
        'country_code',
        'avatar',
        'bio',
        'location_country',
        'location_city',
        'location_district',
        'business_name',
        'business_type',
        'business_owner',
        'business_address',
        'business_phone',
        'instagram_url',
        'facebook_url',
        'website_url',
        'storefront_image_path',
        'is_verified',
        'is_active',
        'last_login_at',
        'scheduled_deletion_at',
        'account_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'scheduled_deletion_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function hawalaTransferRequests()
    {
        return $this->hasMany(HawalaTransferRequest::class);
    }

    public function packageRequests()
    {
        return $this->hasMany(PackageRequest::class);
    }

    public function conversations()
    {
        return Conversation::where('sender_id', $this->id)
            ->orWhere('receiver_id', $this->id);
    }

    public function sentConversations()
    {
        return $this->hasMany(Conversation::class, 'sender_id');
    }

    public function receivedConversations()
    {
        return $this->hasMany(Conversation::class, 'receiver_id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
                    ->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->latest();
    }

    /**
     * All active (non-expired) subscriptions for cumulative limits.
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    protected function activeSubscriptionsCollection()
    {
        return $this->activeSubscriptions()
            ->with('package')
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Get the number of free ads the user has used
     */
    public function getFreeAdsUsed()
    {
        $activeSubscription = $this->activeSubscription;

        if ($activeSubscription) {
            return 0;
        }

        return $this->ads()->where('status', '!=', 'suspended')->count();
    }

    /**
     * Check if user can create a free ad.
     * عدد الإعلانات النشطة يُحتسب دائماً من الإعلانات الفعلية (غير المعلّقة).
     */
    public function canCreateFreeAd()
    {
        $activeCount = $this->getActiveAdsCount();
        return $activeCount < $this->getAdsLimit();
    }

    /**
     * عدد الإعلانات التي تحتسب ضمن الحد (غير المعلّقة) — للمشترك والمجاني.
     */
    public function getActiveAdsCount(): int
    {
        return $this->ads()->where('status', '!=', 'suspended')->count();
    }

    /**
     * الحد الحالي للمستخدم (من الباقة أو المجانية).
     */
    public function getAdsLimit(): int
    {
        $activeSubs = $this->activeSubscriptionsCollection();
        if ($activeSubs->isNotEmpty()) {
            return (int) $activeSubs->sum(function ($sub) {
                return (int) ($sub->package->ads_limit ?? 0);
            });
        }
        return (int) \App\Models\Setting::get('free_ads_limit', 3);
    }

    /**
     * تعليق الإعلانات الزائدة عن الحد — الأقدم أولاً.
     * يُستدعى عند: تغيير الاشتراك، انتهاء الاشتراك، العودة للمجانية.
     * @return int عدد الإعلانات التي تم تعليقها
     */
    public function enforceAdsLimit(): int
    {
        $limit = $this->getAdsLimit();
        $activeCount = $this->getActiveAdsCount();

        if ($activeCount <= $limit) {
            return 0;
        }

        $toSuspend = $activeCount - $limit;
        $oldestAds = $this->ads()
            ->where('status', '!=', 'suspended')
            ->orderByRaw('COALESCE(published_at, created_at) ASC')
            ->limit($toSuspend)
            ->get();

        foreach ($oldestAds as $ad) {
            $ad->update(['status' => 'suspended']);
        }

        return $oldestAds->count();
    }

    /**
     * Can the user unsuspend an ad? Only if they are under their limit (subscribed or free).
     * المشترك له حد من الباقة؛ إن كان قد استخدم الحد كاملاً لا يمكنه فك التعليق.
     */
    public function canUnsuspendAd(): bool
    {
        return $this->getActiveAdsCount() < $this->getAdsLimit();
    }

    /**
     * Get remaining free ads count
     */
    public function getRemainingFreeAds()
    {
        $freeAdsLimit = \App\Models\Setting::get('free_ads_limit', 3);
        $used = $this->getFreeAdsUsed();
        return max(0, $freeAdsLimit - $used);
    }

    /** Can the user create a featured ad? (Only with active subscription that has featured_ads + remaining quota) */
    public function canCreateFeaturedAd(): bool
    {
        foreach ($this->activeSubscriptionsCollection() as $sub) {
            if ($sub->hasFeaturedAdsRemaining()) {
                return true;
            }
        }
        return false;
    }

    /** Can the user create an urgent ad? (Only with active subscription that has urgent_ads + remaining quota) */
    public function canCreateUrgentAd(): bool
    {
        foreach ($this->activeSubscriptionsCollection() as $sub) {
            if ($sub->hasUrgentAdsRemaining()) {
                return true;
            }
        }
        return false;
    }

    /** Remaining featured ads count (0 if no subscription or no featured in package) */
    public function getRemainingFeaturedAds(): int
    {
        $activeSubs = $this->activeSubscriptionsCollection();
        if ($activeSubs->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($activeSubs as $sub) {
            if (!$sub->package || !$sub->package->featured_ads) {
                continue;
            }
            $limit = (int) ($sub->package->featured_ads_limit ?? 0);
            if ($limit <= 0) {
                return 999; // unlimited if any active package is unlimited
            }
            $used = (int) ($sub->featured_ads_used ?? 0);
            $total += max(0, $limit - $used);
        }
        return $total;
    }

    /** Remaining urgent ads count (0 if no subscription or no urgent in package) */
    public function getRemainingUrgentAds(): int
    {
        $activeSubs = $this->activeSubscriptionsCollection();
        if ($activeSubs->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($activeSubs as $sub) {
            if (!$sub->package || !$sub->package->urgent_ads) {
                continue;
            }
            $limit = (int) ($sub->package->urgent_ads_limit ?? 0);
            if ($limit <= 0) {
                return 999; // unlimited if any active package is unlimited
            }
            $used = (int) ($sub->urgent_ads_used ?? 0);
            $total += max(0, $limit - $used);
        }
        return $total;
    }

    /**
     * Consume one regular-ad quota from earliest-expiring active subscription.
     */
    public function consumeAdQuota(): void
    {
        $sub = $this->activeSubscriptionsCollection()->first();
        if ($sub) {
            $sub->increment('ads_used');
        }
    }

    /**
     * Consume one featured quota from the earliest-expiring active subscription with remaining slots.
     */
    public function consumeFeaturedQuota(): bool
    {
        foreach ($this->activeSubscriptionsCollection() as $sub) {
            if ($sub->hasFeaturedAdsRemaining()) {
                $sub->increment('featured_ads_used');
                return true;
            }
        }
        return false;
    }

    /**
     * Consume one urgent quota from the earliest-expiring active subscription with remaining slots.
     */
    public function consumeUrgentQuota(): bool
    {
        foreach ($this->activeSubscriptionsCollection() as $sub) {
            if ($sub->hasUrgentAdsRemaining()) {
                $sub->increment('urgent_ads_used');
                return true;
            }
        }
        return false;
    }

    /**
     * Release one featured usage from any active subscription that has used quota.
     */
    public function releaseFeaturedQuota(): void
    {
        $sub = $this->activeSubscriptionsCollection()
            ->first(fn ($s) => (int) ($s->featured_ads_used ?? 0) > 0);
        if ($sub) {
            $sub->decrement('featured_ads_used');
        }
    }

    /**
     * Release one urgent usage from any active subscription that has used quota.
     */
    public function releaseUrgentQuota(): void
    {
        $sub = $this->activeSubscriptionsCollection()
            ->first(fn ($s) => (int) ($s->urgent_ads_used ?? 0) > 0);
        if ($sub) {
            $sub->decrement('urgent_ads_used');
        }
    }

    /**
     * Latest expiry among active subscriptions.
     */
    public function getLatestActiveSubscriptionExpiry()
    {
        return $this->activeSubscriptionsCollection()->max('expires_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function hasFavorite($adId)
    {
        return $this->favorites()->where('ad_id', $adId)->exists();
    }

    public function favoriteSellers()
    {
        return $this->hasMany(FavoriteSeller::class);
    }

    public function favoritedBy()
    {
        return $this->hasMany(FavoriteSeller::class, 'seller_id');
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlockedUser::class);
    }

    public function blockedBy()
    {
        return $this->hasMany(BlockedUser::class, 'blocked_user_id');
    }

    public function isBlockedBy($userId)
    {
        return $this->blockedBy()->where('user_id', $userId)->exists();
    }

    public function hasBlocked($userId)
    {
        return $this->blockedUsers()->where('blocked_user_id', $userId)->exists();
    }

    /** Invalidate cached blocked-user id lists used by API ad listings. */
    public function forgetBlockedUserIdsCache(): void
    {
        Cache::forget('api:user:'.$this->id.':blocked_user_ids');
    }

    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function searchHistories()
    {
        return $this->hasMany(SearchHistory::class);
    }

    public function ratingsAsSeller()
    {
        return $this->hasMany(SellerRating::class, 'seller_id');
    }

    public function ratingsGiven()
    {
        return $this->hasMany(SellerRating::class, 'user_id');
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratingsAsSeller()->avg('rating') ?? 0;
    }

    public function getRatingsCountAttribute()
    {
        return $this->ratingsAsSeller()->count();
    }

    public function hasRated($sellerId)
    {
        return $this->ratingsGiven()->where('seller_id', $sellerId)->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->slug)) {
                $user->slug = \Illuminate\Support\Str::slug($user->name);
                // Ensure uniqueness
                $baseSlug = $user->slug;
                $counter = 1;
                while (static::where('slug', $user->slug)->exists()) {
                    $user->slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('name') && empty($user->slug)) {
                $user->slug = \Illuminate\Support\Str::slug($user->name);
                // Ensure uniqueness
                $baseSlug = $user->slug;
                $counter = 1;
                while (static::where('slug', $user->slug)->where('id', '!=', $user->id)->exists()) {
                    $user->slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
