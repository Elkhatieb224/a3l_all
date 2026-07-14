<?php

if (!function_exists('get_currency_symbol')) {
    /**
     * Get the default currency symbol from settings
     */
    function get_currency_symbol(): string
    {
        return \App\Models\Setting::get('currency_symbol', 'ل.س');
    }
}

if (!function_exists('get_currency_symbol_for_code')) {
    /**
     * Get the currency symbol for a given code (توحيد الرمز فقط: ل.س، $، TL، €)
     */
    function get_currency_symbol_for_code(?string $code): string
    {
        $symbols = [
            'SYP' => 'ل.س',
            'TRY' => 'TL',
            'USD' => '$',
            'EUR' => '€',
        ];
        if ($code === null || $code === '') {
            return get_currency_symbol();
        }
        return $symbols[strtoupper($code)] ?? get_currency_symbol();
    }
}

if (!function_exists('format_price')) {

    function format_price($price, $decimals = 2, $currency = null): string
    {
        if ($price === null || $price === '') {
            return '-';
        }
        if (is_array($price)) {
            $currency = $price['currency'] ?? $currency;
            $price = $price['value'] ?? null;
            if ($price === null || $price === '') {
                return '-';
            }
        }
        $num = is_numeric($price) ? (float) $price : null;
        if ($num === null) {
            return '-';
        }
        if ($decimals === 2) {
            $cents = (int) round(round($num, 2) * 100) % 100;
            if ($cents === 0) {
                $decimals = 0;
            }
        }
        $symbol = $currency ? get_currency_symbol_for_code($currency) : get_currency_symbol();
        return number_format($num, $decimals, '.', ',') . ' ' . $symbol;
    }
}

if (!function_exists('format_custom_field_display')) {
    /**
     * Convert a custom field value (string or array) to a display string.
     * Handles location (address), number+currency (price), lat/lng, or raw array.
     */
    function format_custom_field_display($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        if (!is_array($value)) {
            return (string) $value;
        }
        if (!empty($value['address'])) {
            return (string) $value['address'];
        }
        if (isset($value['value'], $value['currency'])) {
            return format_price($value['value'], 2, $value['currency']);
        }
        if (isset($value['latitude'], $value['longitude'])) {
            return ($value['latitude'] ?? '') . ', ' . ($value['longitude'] ?? '');
        }
        return json_encode($value);
    }
}

if (!function_exists('store_image_as_webp')) {
    /**

     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @return string
     */
    function store_image_as_webp(\Illuminate\Http\UploadedFile $file, string $directory): string
    {
        $service = app(\App\Services\WebPImageService::class);
        if ($service->isConvertible($file)) {
            $path = $service->convertAndStore($file, $directory);
            if ($path === null) {
                \Illuminate\Support\Facades\Log::warning('store_image_as_webp: conversion failed, storing original file', [
                    'directory' => $directory,
                    'client_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'webp_supported' => $service->supportsWebPEncoding(),
                ]);
            }

            return $path ?? $file->store($directory, 'public');
        }
        // WebP وغيره يُحفظ كما هو
        return $file->store($directory, 'public');
    }
}

if (!function_exists('store_ad_image_raw')) {
  
    function store_ad_image_raw(\Illuminate\Http\UploadedFile $file): string
    {
        $path = $file->store('ads/images', 'public');
        $full = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        if (is_file($full)) {
            @chmod($full, 0644);
        }

        return $path;
    }
}

if (!function_exists('store_ad_video_raw')) {

    function store_ad_video_raw(\Illuminate\Http\UploadedFile $file): string
    {
        $path = $file->store('ads/videos', 'public');
        $full = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        if (is_file($full)) {
            @chmod($full, 0644);
        }

        return $path;
    }
}

if (!function_exists('storage_url')) {

    function storage_url($path): string
    {
        $path = ltrim($path, '/');

        $publicStoragePath = base_path('public_storage/' . $path);
        if (file_exists($publicStoragePath)) {
            return asset('storage/' . $path);
        }

        $publicLinkPath = base_path('storage_public_link/' . $path);
        if (file_exists($publicLinkPath)) {
            $targetDir = dirname($publicStoragePath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            @copy($publicLinkPath, $publicStoragePath);
            @chmod($publicStoragePath, 0644);
            return asset('storage/' . $path);
        }

        $storagePath = storage_path('app/public/' . $path);
        if (file_exists($storagePath)) {
            $targetDir = dirname($publicStoragePath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            @copy($storagePath, $publicStoragePath);
            @chmod($publicStoragePath, 0644);
            return asset('storage/' . $path);
        }

        return asset('storage/' . $path);
    }
}
