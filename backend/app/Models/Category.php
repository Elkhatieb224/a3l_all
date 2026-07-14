<?php

namespace App\Models;

use App\Support\LocalizedDisplayName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function () {
            self::bumpApiCacheVersions();
        });

        static::deleted(function () {
            self::bumpApiCacheVersions();
        });

        static::restored(function () {
            self::bumpApiCacheVersions();
        });

        static::forceDeleted(function () {
            self::bumpApiCacheVersions();
        });
    }

    protected $fillable = [
        'name_ar',
        'name_en',
        'name_tr',
        'slug',
        'icon',
        'description_ar',
        'description_en',
        'description_tr',
        'order',
        'is_active',
        'enable_negotiation',
        'ad_images_mode',
        'ad_images_max',
        'ad_gallery_images',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'enable_negotiation' => 'boolean',
        'ad_images_max' => 'integer',
        'custom_fields' => 'array',
        'ad_gallery_images' => 'array',
    ];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function getName($locale = 'ar')
    {
        $name = $this->{"name_$locale"} ?? $this->name_ar;

        return LocalizedDisplayName::format((string) $name, $locale);
    }

    public function getDescription($locale = 'ar')
    {
        return $this->{"description_$locale"} ?? $this->description_ar;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    private static function bumpApiCacheVersions(): void
    {
        Cache::forever('api:categories:version', (int) Cache::get('api:categories:version', 1) + 1);
        Cache::forever('api:home:categories:version', (int) Cache::get('api:home:categories:version', 1) + 1);
    }
}

