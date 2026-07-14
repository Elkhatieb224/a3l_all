<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CustomFieldsFilterSupport
{
  /**
   * Primary price field: explicit price/salary id, else first number field with show_currency.
   *
   * @param  array<int, array<string, mixed>>  $fields
   */
  public static function resolvePrimaryPriceFieldId(array $fields): ?string
  {
    foreach ($fields as $field) {
      if (! is_array($field) || ($field['type'] ?? '') !== 'number') {
        continue;
      }
      if (! ($field['is_active'] ?? true)) {
        continue;
      }
      $id = (string) ($field['id'] ?? '');
      if ($id === 'price' || $id === 'salary') {
        return $id;
      }
    }

    foreach ($fields as $field) {
      if (! is_array($field) || ($field['type'] ?? '') !== 'number') {
        continue;
      }
      if (! ($field['is_active'] ?? true)) {
        continue;
      }
      if (! empty($field['show_currency'])) {
        $id = $field['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
      }
    }

    return null;
  }

  /**
   * Filterable custom fields for UI / cf_* params — excludes the primary price field.
   *
   * @param  array<int, array<string, mixed>>  $fields
   * @return array<int, array<string, mixed>>
   */
  public static function resolveFilterableFields(array $fields): array
  {
    $priceId = self::resolvePrimaryPriceFieldId($fields);

    return array_values(array_filter($fields, function ($field) use ($priceId) {
      if (! is_array($field)) {
        return false;
      }
      if (! ($field['is_active'] ?? true)) {
        return false;
      }
      $type = $field['type'] ?? 'text';
        if (! in_array($type, ['number', 'select', 'checkbox', 'date', 'car_body_map'], true)) {
        return false;
      }
      if ($priceId !== null && ($field['id'] ?? '') === $priceId) {
        return false;
      }

      return true;
    }));
  }

  /**
   * @param  array<int, array<string, mixed>>  $fields
   */
  public static function findFieldById(array $fields, string $fieldId): ?array
  {
    foreach ($fields as $field) {
      if (is_array($field) && ($field['id'] ?? '') === $fieldId) {
        return $field;
      }
    }

    return null;
  }

  /**
   * Unified min/max price from min_price/max_price and legacy cf_{priceId}_min/max.
   *
   * @return array{0: ?float, 1: ?float}
   */
  public static function normalizedMinMaxPrice(Request $request, ?string $priceFieldId): array
  {
    $min = $request->input('min_price');
    $max = $request->input('max_price');

    if ($priceFieldId) {
      if (($min === null || $min === '') && $request->filled("cf_{$priceFieldId}_min")) {
        $min = $request->input("cf_{$priceFieldId}_min");
      }
      if (($max === null || $max === '') && $request->filled("cf_{$priceFieldId}_max")) {
        $max = $request->input("cf_{$priceFieldId}_max");
      }
    }

    return [
      $min !== null && $min !== '' ? (float) $min : null,
      $max !== null && $max !== '' ? (float) $max : null,
    ];
  }

  /**
   * @param  array<string, mixed>  $customFields
   * @return array{price: mixed, currency: ?string}
   */
    public static function extractPriceAndCurrencyFromCustomFields(
        array $customFields,
        ?string $priceFieldId = null,
        ?string $defaultCurrency = null
    ): array {
        $currency = $defaultCurrency;
        if (isset($customFields['currency']) && is_string($customFields['currency']) && $customFields['currency'] !== '') {
            $currency = $customFields['currency'];
        }

        $keys = $priceFieldId ? [$priceFieldId] : ['price', 'salary'];

        foreach ($keys as $key) {
            if (! isset($customFields[$key])) {
                continue;
            }
            $v = $customFields[$key];
            if (is_array($v) && ! empty($v['tbd'])) {
                continue;
            }
            if (is_array($v) && isset($v['value']) && (string) $v['value'] !== '' && is_numeric($v['value'])) {
                return [
                    'price' => $v['value'],
                    'currency' => isset($v['currency']) ? (string) $v['currency'] : $currency,
                ];
            }
            if (is_numeric($v) && (string) $v !== '') {
                return [
                    'price' => $v,
                    'currency' => $currency,
                ];
            }
        }

        return ['price' => null, 'currency' => $currency];
    }

  /**
   * @param  array<string, mixed>  $field
   */
  public static function customFieldPriceUsesCurrencyObject(array $field): bool
  {
    return ! empty($field['show_currency']);
  }

  /**
   * Label for the unified price filter UI (min_price / max_price).
   * Uses primary price field, else salary/pay fields for jobs-style categories.
   *
   * @param  array<int, array<string, mixed>>  $fields
   * @return array{ar: string, en: string, tr: string}
   */
  public static function resolvePriceFilterLabel(array $fields): array
  {
    $priceId = self::resolvePrimaryPriceFieldId($fields);
    if ($priceId) {
      $field = self::findFieldById($fields, $priceId);
      if ($field && is_array($field['label'] ?? null)) {
        return [
          'ar' => (string) ($field['label']['ar'] ?? 'السعر'),
          'en' => (string) ($field['label']['en'] ?? 'Price'),
          'tr' => (string) ($field['label']['tr'] ?? 'Fiyat'),
        ];
      }
    }

    foreach (['expected_salary', 'expected_pay', 'salary'] as $altId) {
      $field = self::findFieldById($fields, $altId);
      if ($field && is_array($field['label'] ?? null)) {
        return [
          'ar' => (string) ($field['label']['ar'] ?? ''),
          'en' => (string) ($field['label']['en'] ?? ''),
          'tr' => (string) ($field['label']['tr'] ?? ''),
        ];
      }
    }

    return [
      'ar' => 'السعر',
      'en' => 'Price',
      'tr' => 'Fiyat',
    ];
  }

  /**
   * Apply "expires after" filter: custom field date >= selected date (Y-m-d).
   */
  public static function applyDateAfterFilter(Builder $query, string $fieldId, mixed $afterDate): void
  {
    if ($afterDate === null || $afterDate === '') {
      return;
    }

    $afterDate = trim((string) $afterDate);
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $afterDate)) {
      return;
    }

    $query->whereRaw(
      'JSON_UNQUOTE(JSON_EXTRACT(custom_fields, ?)) >= ?',
      ['$.'.$fieldId, $afterDate]
    );
  }

  /**
   * Apply per-part filters for car_body_map fields (cf_{fieldId}__{partId}).
   */
  public static function applyCarBodyMapPartFilters(Builder $query, string $fieldId, Request $request): void
  {
    foreach (CarBodyMapSupport::partIds() as $partId) {
      $param = CarBodyMapSupport::filterParamName($fieldId, $partId);
      $status = $request->input($param);
      if ($status === null || $status === '') {
        continue;
      }
      if (! in_array($status, CarBodyMapSupport::STATUSES, true)) {
        continue;
      }
      $query->where("custom_fields->{$fieldId}->parts->{$partId}", $status);
    }
  }
}
