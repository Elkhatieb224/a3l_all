<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DynamicRegion extends Model
{
    protected $fillable = [
        'country',
        'anchor_state_code',
        'parent_id',
        'type',
        'code',
        'dedup_hash',
        'name_ar',
        'name_en',
        'name_tr',
        'extra_match_names',
        'latitude',
        'longitude',
        'use_count',
        'created_by_user_id',
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
        return $this->hasMany(self::class, 'parent_id');
    }

    public static function dedupHash(string $country, string $type, ?int $parentId, string $label, ?string $anchorStateCode = null): string
    {
        $label = mb_strtolower(trim($label), 'UTF-8');
        $anchor = $anchorStateCode !== null && $anchorStateCode !== ''
            ? strtoupper(trim($anchorStateCode))
            : '-';

        return hash('sha256', strtoupper($country).'|'.$type.'|'.($parentId ?? 0).'|'.$anchor.'|'.$label);
    }

    public static function allocateCode(string $country): string
    {
        $prefix = strtoupper($country).'-DR-';

        return $prefix.str_replace('-', '', Str::uuid()->toString());
    }

    public static function labelForLocale(self $row, string $locale): string
    {
        $locale = strtolower($locale);
        if ($locale === 'tr' && $row->name_tr) {
            return (string) $row->name_tr;
        }
        if ($locale === 'en' && $row->name_en) {
            return (string) $row->name_en;
        }

        return (string) ($row->name_ar ?: $row->name_en ?: $row->name_tr ?: $row->code);
    }

    /**
     * محافظات ديناميكية فقط (ليست مرتبطة بكتالوج ثابت).
     *
     * @return list<array<string, mixed>>
     */
    public static function configShapedRootStatesForCountry(string $country): array
    {
        $c = strtoupper($country);
        $rows = self::query()->where('country', $c)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $byParent = $rows->groupBy(fn ($r) => $r->parent_id === null ? '_root' : (string) $r->parent_id);
        $roots = $byParent->get('_root', collect());

        $out = [];
        foreach ($roots as $state) {
            if ($state->type !== 'state') {
                continue;
            }
            $out[] = self::serializeState($state, $byParent);
        }

        return $out;
    }

    /**
     * مدن ديناميكية تُعرض تحت محافظة من الكتالوج الثابت (anchor_state_code).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function anchoredCityBranchesForCountry(string $country): array
    {
        $c = strtoupper($country);
        $cities = self::query()
            ->where('country', $c)
            ->where('type', 'city')
            ->whereNotNull('anchor_state_code')
            ->orderBy('id')
            ->get();

        $byAnchor = [];
        foreach ($cities as $city) {
            $anchor = (string) $city->anchor_state_code;
            $districts = [];
            foreach ($city->children()->where('type', 'district')->orderBy('id')->get() as $d) {
                $districts[] = [
                    'code' => $d->code,
                    'name_ar' => $d->name_ar ?? '',
                    'name_en' => $d->name_en ?? '',
                    'name_tr' => $d->name_tr ?? '',
                    'match_names' => self::collectMatchNames($d),
                ];
            }
            $byAnchor[$anchor][] = [
                'code' => $city->code,
                'name_ar' => $city->name_ar ?? '',
                'name_en' => $city->name_en ?? '',
                'name_tr' => $city->name_tr ?? '',
                'match_names' => self::collectMatchNames($city),
                'districts' => $districts,
            ];
        }

        return $byAnchor;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, self>>  $byParent
     * @return array<string, mixed>
     */
    private static function serializeState(self $state, $byParent): array
    {
        $matchNames = self::collectMatchNames($state);
        $cities = [];
        foreach ($byParent->get((string) $state->id, collect()) as $city) {
            if ($city->type !== 'city') {
                continue;
            }
            $cities[] = self::serializeCity($city, $byParent);
        }

        return [
            'code' => $state->code,
            'name_ar' => $state->name_ar ?? '',
            'name_en' => $state->name_en ?? '',
            'name_tr' => $state->name_tr ?? '',
            'match_names' => $matchNames,
            'cities' => $cities,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, self>>  $byParent
     * @return array<string, mixed>
     */
    private static function serializeCity(self $city, $byParent): array
    {
        $matchNames = self::collectMatchNames($city);
        $districts = [];
        foreach ($byParent->get((string) $city->id, collect()) as $d) {
            if ($d->type !== 'district') {
                continue;
            }
            $districts[] = [
                'code' => $d->code,
                'name_ar' => $d->name_ar ?? '',
                'name_en' => $d->name_en ?? '',
                'name_tr' => $d->name_tr ?? '',
                'match_names' => self::collectMatchNames($d),
            ];
        }

        return [
            'code' => $city->code,
            'name_ar' => $city->name_ar ?? '',
            'name_en' => $city->name_en ?? '',
            'name_tr' => $city->name_tr ?? '',
            'match_names' => $matchNames,
            'districts' => $districts,
        ];
    }

    /**
     * @return list<string>
     */
    private static function collectMatchNames(self $row): array
    {
        $seen = [];
        foreach ([$row->name_ar, $row->name_en, $row->name_tr] as $v) {
            $t = trim((string) $v);
            if ($t !== '') {
                $seen[$t] = true;
            }
        }
        foreach ($row->extra_match_names ?? [] as $v) {
            $t = trim((string) $v);
            if ($t !== '') {
                $seen[$t] = true;
            }
        }

        return array_keys($seen);
    }
}
