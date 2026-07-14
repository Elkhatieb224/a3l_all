<?php

namespace App\Models;

use App\Support\RegionCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoDivision extends Model
{
    public const LEVEL_STATE = 0;

    public const LEVEL_DISTRICT = 1;

    public const LEVEL_NEIGHBORHOOD = 2;

    protected $fillable = [
        'country',
        'parent_id',
        'level',
        'code',
        'sort_order',
        'name_ar',
        'name_en',
        'name_tr',
        'extra_match_names',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'extra_match_names' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiItem(): array
    {
        $match = [];
        foreach ([$this->name_ar, $this->name_en, $this->name_tr] as $n) {
            $t = trim((string) $n);
            if ($t !== '') {
                $match[$t] = true;
            }
        }
        foreach ($this->extra_match_names ?? [] as $n) {
            $t = trim((string) $n);
            if ($t !== '') {
                $match[$t] = true;
            }
        }

        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => RegionCatalog::labelForLocale(
                [
                    'name_ar' => (string) ($this->name_ar ?? ''),
                    'name_en' => (string) ($this->name_en ?? ''),
                    'name_tr' => (string) ($this->name_tr ?? ''),
                ],
                $locale
            ),
            'name_ar' => $this->name_ar ?? '',
            'name_en' => $this->name_en ?? '',
            'name_tr' => $this->name_tr ?? '',
            'match_names' => array_keys($match),
        ];
    }
}
