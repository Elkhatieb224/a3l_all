<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch(Request $request, $locale)
    {
        // Check if locale is available
        $availableLocales = array_keys(config('app.available_locales'));

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale');
        }

        // Store locale in session
        Session::put('locale', $locale);

        // Set application locale
        App::setLocale($locale);

        return redirect()->back();
    }
}

