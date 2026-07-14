<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Faq extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function () {
            self::forgetHelpCache();
        });

        static::deleted(function () {
            self::forgetHelpCache();
        });
    }

    protected $fillable = [
        'question_ar',
        'question_en',
        'question_tr',
        'answer_ar',
        'answer_en',
        'answer_tr',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function getQuestion($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->{"question_{$locale}"} ?? $this->question_ar;
    }

    public function getAnswer($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->{"answer_{$locale}"} ?? $this->answer_ar;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    private static function forgetHelpCache(): void
    {
        $locales = array_keys((array) config('app.available_locales', []));
        foreach ($locales as $locale) {
            Cache::forget("api:help:v1:{$locale}");
        }
    }
}
