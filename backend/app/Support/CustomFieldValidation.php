<?php

namespace App\Support;

use Illuminate\Http\Request;

class CustomFieldValidation
{
    /**
     * @param  array<int, array<string, mixed>>  $schema
     * @return array<string, mixed>
     */
    public static function rulesForSchema(array $schema, string $prefix = 'custom_fields'): array
    {
        $rules = [];
        $active = CustomFieldsResolver::onlyActive($schema);

        foreach ($active as $field) {
            $fieldId = $field['id'] ?? null;
            if (! is_string($fieldId) || $fieldId === '') {
                continue;
            }

            $isRequired = $field['required'] ?? false;
            $fieldType = $field['type'] ?? 'text';
            $isNumberWithCurrency = $fieldType === 'number' && ! empty($field['show_currency']);
            $allowTbd = ! empty($field['allow_tbd']);

            if ($isRequired) {
                if ($fieldType === 'location') {
                    $rules["{$prefix}.{$fieldId}.lat"] = 'required|numeric|between:-90,90';
                    $rules["{$prefix}.{$fieldId}.lng"] = 'required|numeric|between:-180,180';
                } elseif ($isNumberWithCurrency) {
                    $rules["{$prefix}.{$fieldId}.value"] = $allowTbd
                        ? "required_without:{$prefix}.{$fieldId}.tbd|nullable|numeric"
                        : 'required|numeric';
                } else {
                    $rules["{$prefix}.{$fieldId}"] = 'required';
                }
            } else {
                if ($fieldType === 'location') {
                    $rules["{$prefix}.{$fieldId}.lat"] = 'nullable|numeric|between:-90,90';
                    $rules["{$prefix}.{$fieldId}.lng"] = 'nullable|numeric|between:-180,180';
                    $rules["{$prefix}.{$fieldId}.address"] = 'nullable|string|max:255';
                } elseif ($isNumberWithCurrency) {
                    $rules["{$prefix}.{$fieldId}.value"] = 'nullable|numeric';
                    $rules["{$prefix}.{$fieldId}.currency"] = 'nullable|in:SYP,TRY,USD,EUR';
                } else {
                    $rules["{$prefix}.{$fieldId}"] = 'nullable';
                }
            }

            if ($fieldType === 'number' && ! $isNumberWithCurrency) {
                $rules["{$prefix}.{$fieldId}"] = ($rules["{$prefix}.{$fieldId}"] ?? 'nullable').'|numeric';
                if (isset($field['min'])) {
                    $rules["{$prefix}.{$fieldId}"] .= '|min:'.$field['min'];
                }
                if (isset($field['max'])) {
                    $rules["{$prefix}.{$fieldId}"] .= '|max:'.$field['max'];
                }
            }

            if ($fieldType === 'date') {
                $rules["{$prefix}.{$fieldId}"] = ($rules["{$prefix}.{$fieldId}"] ?? 'nullable').'|date_format:Y-m-d';
            }

            if ($isNumberWithCurrency) {
                if (isset($field['min'])) {
                    $rules["{$prefix}.{$fieldId}.value"] = ($rules["{$prefix}.{$fieldId}.value"] ?? 'nullable').'|min:'.$field['min'];
                }
                if (isset($field['max'])) {
                    $rules["{$prefix}.{$fieldId}.value"] = ($rules["{$prefix}.{$fieldId}.value"] ?? 'nullable').'|max:'.$field['max'];
                }
                $rules["{$prefix}.{$fieldId}.currency"] = 'nullable|in:SYP,TRY,USD,EUR';
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function storeFieldRules(Request $request): array
    {
        $rules = [
            'id' => 'required|string|max:100',
            'type' => 'required|in:text,textarea,number,select,checkbox,location,date,car_body_map',
            'label_ar' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'label_tr' => 'nullable|string|max:255',
            'required' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'nullable|array',
        ];

        if ($request->input('type') === 'select') {
            $rules['options'] = 'required|array|min:1';
            $rules['options.*.ar'] = 'required|string';
            $rules['options.*.en'] = 'nullable|string';
            $rules['options.*.tr'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildFieldFromRequest(Request $request, ?array $existing = null): array
    {
        $field = $existing ?? [];
        $field['id'] = $existing['id'] ?? $request->id;
        $field['type'] = $request->type;
        $field['label'] = [
            'ar' => $request->label_ar,
            'en' => $request->label_en ?? $request->label_ar,
            'tr' => $request->label_tr ?? $request->label_ar,
        ];
        $field['required'] = $request->boolean('required');
        $field['is_active'] = $request->boolean('is_active', true);

        if ($request->type === 'select' && $request->has('options')) {
            $field['options'] = $request->options;
        } else {
            unset($field['options']);
        }

        if ($request->type === 'number') {
            $field['min'] = $request->input('min');
            $field['max'] = $request->input('max');
            $field['step'] = $request->input('step', 1);
            $field['show_currency'] = $request->boolean('show_currency');
            $field['allow_tbd'] = $request->boolean('allow_tbd');
        } else {
            unset($field['min'], $field['max'], $field['step'], $field['show_currency'], $field['allow_tbd']);
        }

        return $field;
    }

    /**
     * @param  array<string, mixed>  $customFields
     * @return array<string, mixed>
     */
    public static function normalizeStoredValues(array $customFields): array
    {
        foreach ($customFields as $fid => $val) {
            if (is_array($val) && isset($val['parts']) && is_array($val['parts'])) {
                $customFields[$fid] = CarBodyMapSupport::normalizeValue($val);

                continue;
            }
            if (is_array($val) && ! empty($val['tbd'])) {
                $customFields[$fid] = ['tbd' => true];

                continue;
            }
            if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                continue;
            }
            if (is_array($val) && isset($val['value']) && (string) $val['value'] !== '') {
                if (empty($val['currency'])) {
                    $customFields[$fid]['currency'] = \App\Models\Setting::get('default_currency', 'SYP');
                }
            }
        }

        return $customFields;
    }
}
