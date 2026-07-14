<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AppInfoController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();
        $rulesKey = 'messaging_rules_' . $locale;
        $messagingRules = Setting::get($rulesKey, '');

        $appInfo = [
            'establishment_name' => Setting::get('app_info_establishment_name', 'Aalenha.com'),
            'commercial_name' => Setting::get('app_info_commercial_name', 'شركة أعلنها - aalenha لتقنيات المعلومات والتسويق والتجارة'),
            'responsible_person' => Setting::get('app_info_responsible_person', ''),
            'commercial_registration_number' => Setting::get('app_info_commercial_registration_number', ''),
            'official_email' => Setting::get('app_info_official_email', 'aalenha@hs02.kep.tr'),
            'mersis_number' => Setting::get('app_info_mersis_number', '0739014655600017'),
            'main_office' => Setting::get('app_info_main_office', ''),
            'call_center' => Setting::get('app_info_call_center', ''),
            'support_center' => Setting::get('app_info_support_center', 'التوجه لقسم البلاغات و المساعدة'),
            'map_location_url' => Setting::get('app_info_map_location_url', ''),
            'messaging_rules' => $messagingRules,
            'messaging_rules_title' => __('frontend.messages.important_alert'),
        ];

        return response()->json([
            'success' => true,
            'data' => $appInfo,
        ]);
    }
}
