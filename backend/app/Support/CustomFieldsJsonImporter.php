<?php

namespace App\Support;

class CustomFieldsJsonImporter
{
    /** @var list<string> */
    private const ALLOWED_TYPES = ['text', 'textarea', 'number', 'select', 'checkbox', 'location', 'date', 'car_body_map'];

    /**
     * @return array{error: ?string, fields: array<int, array<string, mixed>>}
     */
    public static function parseAndApply(string $raw, array $existing, string $mode = 'replace'): array
    {
        $parsed = self::parse($raw);
        if ($parsed['error'] !== null) {
            return $parsed;
        }

        return self::apply($parsed['fields'], $existing, $mode);
    }

    /**
     * @return array{error: ?string, fields: array<int, array<string, mixed>>}
     */
    public static function parse(string $raw): array
    {
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return ['error' => __('admin.categories.custom_fields.json_invalid'), 'fields' => []];
        }

        $fields = $data['fields'] ?? null;
        if (! is_array($fields) || $fields === []) {
            return ['error' => __('admin.categories.custom_fields.json_no_fields'), 'fields' => []];
        }

        $normalized = [];
        $errors = [];
        $seenIds = [];

        foreach ($fields as $index => $field) {
            $row = $index + 1;
            if (! is_array($field)) {
                $errors[] = __('admin.categories.custom_fields.json_row_invalid', ['row' => $row]);

                continue;
            }

            $error = self::normalizeField($field, $row, $normalized, $seenIds);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if ($errors !== []) {
            return ['error' => implode(' ', $errors), 'fields' => []];
        }

        if ($normalized === []) {
            return ['error' => __('admin.categories.custom_fields.json_no_fields'), 'fields' => []];
        }

        return ['error' => null, 'fields' => $normalized];
    }

    /**
     * @param  array<int, array<string, mixed>>  $imported
     * @param  array<int, array<string, mixed>>  $existing
     * @return array{error: ?string, fields: array<int, array<string, mixed>>}
     */
    public static function apply(array $imported, array $existing, string $mode = 'replace'): array
    {
        if ($mode === 'merge') {
            $byId = [];
            foreach ($existing as $field) {
                if (is_array($field) && ($field['id'] ?? '') !== '') {
                    $byId[$field['id']] = $field;
                }
            }

            foreach ($imported as $field) {
                $byId[$field['id']] = $field;
            }

            return ['error' => null, 'fields' => array_values($byId)];
        }

        return ['error' => null, 'fields' => $imported];
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalized
     * @param  array<string, true>  $seenIds
     */
    private static function normalizeField(array $field, int $row, array &$normalized, array &$seenIds): ?string
    {
        $id = isset($field['id']) ? trim((string) $field['id']) : '';
        if ($id === '') {
            return __('admin.categories.custom_fields.json_row_missing_id', ['row' => $row]);
        }
        if (strlen($id) > 100 || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $id)) {
            return __('admin.categories.custom_fields.json_row_invalid_id', ['row' => $row, 'id' => $id]);
        }
        if (isset($seenIds[$id])) {
            return __('admin.categories.custom_fields.json_duplicate_id', ['row' => $row, 'id' => $id]);
        }
        $seenIds[$id] = true;

        $type = isset($field['type']) ? trim((string) $field['type']) : '';
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return __('admin.categories.custom_fields.json_row_invalid_type', ['row' => $row, 'type' => $type]);
        }

        $label = $field['label'] ?? null;
        if (! is_array($label)) {
            return __('admin.categories.custom_fields.json_row_missing_label', ['row' => $row]);
        }

        $labelAr = isset($label['ar']) ? trim((string) $label['ar']) : '';
        if ($labelAr === '') {
            return __('admin.categories.custom_fields.json_row_missing_label_ar', ['row' => $row]);
        }

        $labelEn = isset($label['en']) ? trim((string) $label['en']) : '';
        $labelTr = isset($label['tr']) ? trim((string) $label['tr']) : '';
        if ($labelEn === '') {
            $labelEn = $labelAr;
        }
        if ($labelTr === '') {
            $labelTr = $labelAr;
        }

        $normalizedField = [
            'id' => $id,
            'type' => $type,
            'label' => [
                'ar' => $labelAr,
                'en' => $labelEn,
                'tr' => $labelTr,
            ],
            'required' => (bool) ($field['required'] ?? false),
            'is_active' => array_key_exists('is_active', $field) ? (bool) $field['is_active'] : true,
        ];

        if ($type === 'select') {
            $options = $field['options'] ?? null;
            if (! is_array($options) || $options === []) {
                return __('admin.categories.custom_fields.json_row_select_no_options', ['row' => $row]);
            }

            $normalizedOptions = [];
            foreach ($options as $optIndex => $option) {
                if (! is_array($option)) {
                    return __('admin.categories.custom_fields.json_row_option_invalid', [
                        'row' => $row,
                        'option' => $optIndex + 1,
                    ]);
                }

                $optAr = isset($option['ar']) ? trim((string) $option['ar']) : '';
                if ($optAr === '') {
                    return __('admin.categories.custom_fields.json_row_option_missing_ar', [
                        'row' => $row,
                        'option' => $optIndex + 1,
                    ]);
                }

                $optEn = isset($option['en']) ? trim((string) $option['en']) : '';
                $optTr = isset($option['tr']) ? trim((string) $option['tr']) : '';
                if ($optEn === '') {
                    $optEn = $optAr;
                }
                if ($optTr === '') {
                    $optTr = $optAr;
                }

                $normalizedOptions[] = [
                    'ar' => $optAr,
                    'en' => $optEn,
                    'tr' => $optTr,
                ];
            }

            $normalizedField['options'] = $normalizedOptions;

            if (! empty($field['multiple'])) {
                $normalizedField['multiple'] = true;
            }
        }

        if ($type === 'number') {
            if (isset($field['min']) && $field['min'] !== '' && $field['min'] !== null) {
                $normalizedField['min'] = $field['min'];
            }
            if (isset($field['max']) && $field['max'] !== '' && $field['max'] !== null) {
                $normalizedField['max'] = $field['max'];
            }
            if (isset($field['step']) && $field['step'] !== '' && $field['step'] !== null) {
                $normalizedField['step'] = $field['step'];
            }
            if (! empty($field['show_currency'])) {
                $normalizedField['show_currency'] = true;
            }
            if (! empty($field['allow_tbd'])) {
                $normalizedField['allow_tbd'] = true;
            }
        }

        $normalized[] = $normalizedField;

        return null;
    }
}
