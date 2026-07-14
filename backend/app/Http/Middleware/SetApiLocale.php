<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحدد لغة API من الـ header X-Locale أو Accept-Language
 * للطلبات القادمة من تطبيق Flutter
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale');

        if (empty($locale)) {
            $locale = $this->parseAcceptLanguage($request->header('Accept-Language'));
        }

        $availableLocales = array_keys(config('app.available_locales', ['ar' => 'العربية', 'en' => 'English', 'tr' => 'Türkçe']));

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale', 'ar');
        }

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * استخراج اللغة من Accept-Language (مثال: ar,en;q=0.9,tr;q=0.8)
     */
    private function parseAcceptLanguage(?string $acceptLanguage): string
    {
        if (empty($acceptLanguage)) {
            return config('app.fallback_locale', 'ar');
        }

        $parts = explode(',', $acceptLanguage);
        $lang = trim(explode(';', $parts[0])[0]);
        $code = strtolower(substr($lang, 0, 2));

        $map = ['ar' => 'ar', 'en' => 'en', 'tr' => 'tr'];
        return $map[$code] ?? config('app.fallback_locale', 'ar');
    }
}
