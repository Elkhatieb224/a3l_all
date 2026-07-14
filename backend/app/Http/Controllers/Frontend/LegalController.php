<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function terms()
    {
        $locale = app()->getLocale();
        $content = Setting::get("terms_conditions_{$locale}", '');
        
        return view('frontend.legal.terms', compact('content'));
    }

    public function privacy()
    {
        $locale = app()->getLocale();
        $content = Setting::get("privacy_policy_{$locale}", '');
        
        return view('frontend.legal.privacy', compact('content'));
    }
}
