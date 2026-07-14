<?php

namespace App\Support;

class CarBodyMapSupport
{
    public const STATUS_ORIGINAL = 'original';

    public const STATUS_LOCAL_PAINT = 'local_paint';

    public const STATUS_PAINTED = 'painted';

    public const STATUS_REPLACED = 'replaced';

  /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ORIGINAL,
        self::STATUS_LOCAL_PAINT,
        self::STATUS_PAINTED,
        self::STATUS_REPLACED,
    ];

  /**
   * @return array<string, array{id: string, label: array{ar: string, en: string, tr: string}}>
   */
    public static function parts(): array
    {
        return [
            'front_bumper' => [
                'id' => 'front_bumper',
                'label' => ['ar' => 'المصد الأمامي', 'en' => 'Front Bumper', 'tr' => 'Ön Tampon'],
            ],
            'hood' => [
                'id' => 'hood',
                'label' => ['ar' => 'غطاء المحرك', 'en' => 'Hood', 'tr' => 'Kaput'],
            ],
            'front_left_fender' => [
                'id' => 'front_left_fender',
                'label' => ['ar' => 'الرفرف الأمامي الأيسر', 'en' => 'Front Left Fender', 'tr' => 'Sol Ön Çamurluk'],
            ],
            'front_right_fender' => [
                'id' => 'front_right_fender',
                'label' => ['ar' => 'الرفرف الأمامي الأيمن', 'en' => 'Front Right Fender', 'tr' => 'Sağ Ön Çamurluk'],
            ],
            'left_front_door' => [
                'id' => 'left_front_door',
                'label' => ['ar' => 'الباب الأمامي الأيسر', 'en' => 'Left Front Door', 'tr' => 'Sol Ön Kapı'],
            ],
            'right_front_door' => [
                'id' => 'right_front_door',
                'label' => ['ar' => 'الباب الأمامي الأيمن', 'en' => 'Right Front Door', 'tr' => 'Sağ Ön Kapı'],
            ],
            'roof' => [
                'id' => 'roof',
                'label' => ['ar' => 'السقف', 'en' => 'Roof', 'tr' => 'Tavan'],
            ],
            'left_rear_door' => [
                'id' => 'left_rear_door',
                'label' => ['ar' => 'الباب الخلفي الأيسر', 'en' => 'Left Rear Door', 'tr' => 'Sol Arka Kapı'],
            ],
            'right_rear_door' => [
                'id' => 'right_rear_door',
                'label' => ['ar' => 'الباب الخلفي الأيمن', 'en' => 'Right Rear Door', 'tr' => 'Sağ Arka Kapı'],
            ],
            'left_rear_fender' => [
                'id' => 'left_rear_fender',
                'label' => ['ar' => 'الرفرف الخلفي الأيسر', 'en' => 'Left Rear Fender', 'tr' => 'Sol Arka Çamurluk'],
            ],
            'right_rear_fender' => [
                'id' => 'right_rear_fender',
                'label' => ['ar' => 'الرفرف الخلفي الأيمن', 'en' => 'Right Rear Fender', 'tr' => 'Sağ Arka Çamurluk'],
            ],
            'trunk' => [
                'id' => 'trunk',
                'label' => ['ar' => 'صندوق الأمتعة', 'en' => 'Trunk', 'tr' => 'Bagaj Kapağı'],
            ],
            'rear_bumper' => [
                'id' => 'rear_bumper',
                'label' => ['ar' => 'المصد الخلفي', 'en' => 'Rear Bumper', 'tr' => 'Arka Tampon'],
            ],
        ];
    }

  /** @return list<string> */
    public static function partIds(): array
    {
        return array_keys(self::parts());
    }

  /**
   * @return array<string, array{ar: string, en: string, tr: string}>
   */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ORIGINAL => [
                'ar' => 'وكالة / خالي العلام',
                'en' => 'Original',
                'tr' => 'Orijinal',
            ],
            self::STATUS_LOCAL_PAINT => [
                'ar' => 'بخ جزئي',
                'en' => 'Local Paint',
                'tr' => 'Lokal Boyalı',
            ],
            self::STATUS_PAINTED => [
                'ar' => 'مبخوخة بالكامل',
                'en' => 'Fully Painted',
                'tr' => 'Boyalı',
            ],
            self::STATUS_REPLACED => [
                'ar' => 'مبدّلة',
                'en' => 'Replaced',
                'tr' => 'Değişen',
            ],
        ];
    }

  /**
   * @return array<string, array{ar: string, en: string, tr: string}>
   */
    public static function summaryGroupTitles(): array
    {
        return [
            self::STATUS_REPLACED => [
                'ar' => 'القطع المبدّلة',
                'en' => 'Replaced Parts',
                'tr' => 'Değişen Parçalar',
            ],
            self::STATUS_PAINTED => [
                'ar' => 'القطع المبخوخة بالكامل',
                'en' => 'Fully Painted Parts',
                'tr' => 'Boyalı Parçalar',
            ],
            self::STATUS_LOCAL_PAINT => [
                'ar' => 'القطع ذات البخ الجزئي',
                'en' => 'Locally Painted Parts',
                'tr' => 'Lokal Boyalı Parçalar',
            ],
            self::STATUS_ORIGINAL => [
                'ar' => 'القطع الوكالة',
                'en' => 'Original Parts',
                'tr' => 'Orijinal Parçalar',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function statusColors(): array
    {
        return [
            self::STATUS_ORIGINAL => '#e2e8f0',
            self::STATUS_LOCAL_PAINT => '#f97316',
            self::STATUS_PAINTED => '#3b82f6',
            self::STATUS_REPLACED => '#ef4444',
        ];
    }

  /**
   * مسارات SVG لقطع السيارة (منظر علوي منفصل).
   * يمكن استبدالها لاحقاً بملف SVG احترافي من المصمم عبر نفس معرّفات data-part-id.
   *
   * @return array<string, array{d: string}>
   */
    public static function svgDiagramShapes(): array
    {
        return [
            'front_bumper' => [
                'd' => 'M112 6L308 6L315 28L105 28Z',
            ],
            'hood' => [
                'd' => 'M105 28L315 28L302 102L118 102Z',
            ],
            'front_left_fender' => [
                'd' => 'M26 32L102 28L98 105L22 109Z',
            ],
            'front_right_fender' => [
                'd' => 'M394 32L318 28L322 105L398 109Z',
            ],
            'left_front_door' => [
                'd' => 'M22 109L98 105L94 205L18 209Z',
            ],
            'roof' => [
                'd' => 'M118 102L302 102L298 332L122 332Z',
            ],
            'right_front_door' => [
                'd' => 'M398 109L322 105L326 205L402 209Z',
            ],
            'left_rear_door' => [
                'd' => 'M18 209L94 205L90 305L14 309Z',
            ],
            'right_rear_door' => [
                'd' => 'M402 209L326 205L330 305L406 309Z',
            ],
            'left_rear_fender' => [
                'd' => 'M14 309L90 305L86 405L10 409Z',
            ],
            'trunk' => [
                'd' => 'M122 332L298 332L292 418L128 418Z',
            ],
            'right_rear_fender' => [
                'd' => 'M406 309L330 305L334 405L410 409Z',
            ],
            'rear_bumper' => [
                'd' => 'M128 418L292 418L306 508L114 508Z',
            ],
        ];
    }

    public static function svgDiagramViewBox(): string
    {
        return '0 0 420.41 543.07';
    }

    public static function svgDiagramAsset(): string
    {
        return 'images/car-body-map/car.svg';
    }

    public static function svgPartStroke(): string
    {
        return '#b8c4d4';
    }

    public static function defaultPartsState(): array
    {
        $parts = [];
        foreach (self::partIds() as $partId) {
            $parts[$partId] = self::STATUS_ORIGINAL;
        }

        return $parts;
    }

  /**
   * @param  array<string, mixed>|null  $input
   * @return array{parts: array<string, string>, all_original: bool, summary: array{ar: string, en: string, tr: string}}
   */
    public static function normalizeValue(?array $input): array
    {
        $defaults = self::defaultPartsState();
        $rawParts = is_array($input['parts'] ?? null) ? $input['parts'] : [];

        $parts = $defaults;
        foreach ($defaults as $partId => $defaultStatus) {
            $status = $rawParts[$partId] ?? $defaultStatus;
            $status = is_string($status) ? trim($status) : $defaultStatus;
            $parts[$partId] = in_array($status, self::STATUSES, true) ? $status : $defaultStatus;
        }

        $allOriginal = ! in_array(false, array_map(
            fn (string $status) => $status === self::STATUS_ORIGINAL,
            $parts
        ), true);

        if (isset($input['all_original'])) {
            $allOriginal = filter_var($input['all_original'], FILTER_VALIDATE_BOOLEAN);
        }

        return [
            'parts' => $parts,
            'all_original' => $allOriginal,
            'summary' => self::buildSummary($parts),
        ];
    }

  /**
   * @param  array<string, string>  $parts
   * @return array{ar: string, en: string, tr: string}
   */
    public static function buildSummary(array $parts): array
    {
        $grouped = [];
        foreach (self::STATUSES as $status) {
            $grouped[$status] = [];
        }

        foreach ($parts as $partId => $status) {
            if (! isset(self::parts()[$partId]) || ! in_array($status, self::STATUSES, true)) {
                continue;
            }
            $grouped[$status][] = $partId;
        }

        $summary = ['ar' => '', 'en' => '', 'tr' => ''];
        $locales = ['ar', 'en', 'tr'];
        $sections = [];

        foreach ([self::STATUS_REPLACED, self::STATUS_PAINTED, self::STATUS_LOCAL_PAINT] as $status) {
            if ($grouped[$status] === []) {
                continue;
            }
            $section = [];
            foreach ($locales as $locale) {
                $names = array_map(
                    fn (string $partId) => self::parts()[$partId]['label'][$locale] ?? $partId,
                    $grouped[$status]
                );
                $title = self::summaryGroupTitles()[$status][$locale] ?? $status;
                $section[$locale] = $title.': '.implode('، ', $names);
            }
            $sections[] = $section;
        }

        if ($sections === []) {
            foreach ($locales as $locale) {
                $summary[$locale] = __('frontend.car_body_map.all_original_summary', [], $locale);
            }

            return $summary;
        }

        foreach ($locales as $locale) {
            $summary[$locale] = implode("\n", array_map(fn (array $section) => $section[$locale], $sections));
        }

        return $summary;
    }

    public static function partLabel(string $partId, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $parts = self::parts();

        return $parts[$partId]['label'][$locale] ?? ($parts[$partId]['label']['ar'] ?? $partId);
    }

    public static function statusLabel(string $status, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $labels = self::statusLabels();

        return $labels[$status][$locale] ?? ($labels[$status]['ar'] ?? $status);
    }

    public static function filterParamName(string $fieldId, string $partId): string
    {
        return "cf_{$fieldId}__{$partId}";
    }

    public static function isCarBodyMapFilterParam(string $key): bool
    {
        return (bool) preg_match('/^cf_[a-zA-Z][a-zA-Z0-9_]*__[a-z][a-z0-9_]*$/', $key);
    }

  /**
   * @return array{0: string, 1: string}|null
   */
    public static function parseFilterParam(string $key): ?array
    {
        if (! preg_match('/^cf_([a-zA-Z][a-zA-Z0-9_]*)__([a-z][a-z0-9_]*)$/', $key, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }
}
