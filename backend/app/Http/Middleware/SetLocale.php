<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from session or use default
        $locale = Session::get('locale', config('app.locale'));

        // Check if locale is available
        $availableLocales = array_keys(config('app.available_locales'));

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale');
        }

        // Set application locale
        App::setLocale($locale);

        return $next($request);
    }
}

