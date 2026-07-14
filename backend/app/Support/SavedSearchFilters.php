<?php

namespace App\Support;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Builder;

class SavedSearchFilters
{
    public static function normalize(array $raw): array
    {
        $filters = [
            'search' => trim((string) ($raw['search'] ?? '')),
            'category_id' => self::toIntOrNull($raw['category_id'] ?? null),
            'subcategory_id' => self::toIntOrNull($raw['subcategory_id'] ?? null),
            'min_price' => self::toNumericOrNull($raw['min_price'] ?? null),
            'max_price' => self::toNumericOrNull($raw['max_price'] ?? null),
            'custom_filters' => is_array($raw['custom_filters'] ?? null) ? $raw['custom_filters'] : [],
        ];

        return $filters;
    }

    public static function applyToAdsQuery(Builder $query, array $filters): Builder
    {
        $filters = self::normalize($filters);
        $search = $filters['search'];

        if ($search !== '') {
            $variants = self::searchLikeVariants($search);
            $query->where(function (Builder $q) use ($variants) {
                foreach ($variants as $like) {
                    $q->orWhere('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('category', function (Builder $c) use ($like) {
                            $c->where('name_ar', 'like', $like)
                                ->orWhere('name_en', 'like', $like)
                                ->orWhere('name_tr', 'like', $like);
                        })
                        ->orWhereHas('subcategory', function (Builder $s) use ($like) {
                            $s->where('name_ar', 'like', $like)
                                ->orWhere('name_en', 'like', $like)
                                ->orWhere('name_tr', 'like', $like);
                        });
                }
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', (int) $filters['subcategory_id']);
        }

        if ($filters['min_price'] !== null) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if ($filters['max_price'] !== null) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $customFilters = $filters['custom_filters'];
        if (is_array($customFilters)) {
            foreach ($customFilters as $key => $value) {
                $key = (string) $key;
                if (!str_starts_with($key, 'cf_') || $value === null || $value === '') {
                    continue;
                }
                // Supported keys: cf_12, cf_12_min, cf_12_max, cf_field_after
                if (preg_match('/^cf_(.+)_after$/', $key, $m)) {
                    CustomFieldsFilterSupport::applyDateAfterFilter($query, $m[1], $value);
                    continue;
                }

                if (preg_match('/^cf_(\d+)_(min|max)$/', $key, $m)) {
                    $fieldId = $m[1];
                    $op = $m[2] === 'min' ? '>=' : '<=';
                    $numVal = self::toNumericOrNull($value);
                    if ($numVal === null) {
                        continue;
                    }
                    $query->where(function (Builder $q) use ($fieldId, $op, $numVal) {
                        $q->whereRaw(
                            "CAST(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.\"{$fieldId}\".value')) AS DECIMAL(20,4)) {$op} ?",
                            [$numVal]
                        )->orWhereRaw(
                            "CAST(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.\"{$fieldId}\"')) AS DECIMAL(20,4)) {$op} ?",
                            [$numVal]
                        );
                    });
                    continue;
                }

                if (preg_match('/^cf_(\d+)$/', $key, $m)) {
                    $fieldId = $m[1];
                    $strVal = (string) $value;
                    $query->where(function (Builder $q) use ($fieldId, $strVal) {
                        $q->whereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.\"{$fieldId}\".value')) = ?",
                            [$strVal]
                        )->orWhereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.\"{$fieldId}\"')) = ?",
                            [$strVal]
                        );
                    });
                }
            }
        }

        return $query;
    }

    public static function buildAdsBaseQuery(): Builder
    {
        return Ad::where('status', 'active')
            ->with([
                'category:id,name_ar,name_en,name_tr',
                'subcategory:id,name_ar,name_en,name_tr',
                'user:id,name,slug,avatar,is_verified',
            ]);
    }

    private static function searchLikeVariants(string $searchValue): array
    {
        $raw = trim($searchValue);
        if ($raw === '') return ['%%'];

        $collapseSpaces = preg_replace('/\s+/u', ' ', $raw) ?? $raw;
        $noSpaces = str_replace(' ', '', $collapseSpaces);
        $normalized = strtr($collapseSpaces, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ؤ' => 'و', 'ئ' => 'ي', 'ى' => 'ي', 'ة' => 'ه',
        ]);
        $normalizedNoSpaces = str_replace(' ', '', $normalized);

        $variants = array_values(array_unique(array_filter([
            $raw, $collapseSpaces, $noSpaces, $normalized, $normalizedNoSpaces,
        ], fn ($v) => is_string($v) && trim($v) !== '')));

        return array_map(fn ($v) => '%' . $v . '%', $variants);
    }

    private static function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value)) return null;
        return (int) $value;
    }

    private static function toNumericOrNull(mixed $value): int|float|null
    {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value)) return null;
        return $value + 0;
    }
}

