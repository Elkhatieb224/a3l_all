<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'ads/search-categories-json'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? array_filter(array_merge(
            explode(',', env('CORS_ALLOWED_ORIGINS')),
            env('CORS_EXTRA_ORIGINS') ? explode(',', env('CORS_EXTRA_ORIGINS')) : [],
          ))
        : ['*'],

    // أنماط للسماح بـ localhost بأي منفذ (لتشغيل Flutter Web محلياً)
    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
