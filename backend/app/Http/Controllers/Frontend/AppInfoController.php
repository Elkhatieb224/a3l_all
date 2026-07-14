<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AppInfoController extends Controller
{
    public function index()
    {
        $mapLocationUrl = Setting::get('app_info_map_location_url', '');
        $mapLat = '33.5138';
        $mapLng = '36.2765';
        
        // Extract coordinates from Google Maps URL if provided
        if ($mapLocationUrl) {
            if (preg_match('/[?&]q=([^&]+)/', $mapLocationUrl, $matches)) {
                $coords = explode(',', $matches[1]);
                if (count($coords) === 2) {
                    $mapLat = trim($coords[0]);
                    $mapLng = trim($coords[1]);
                }
            } elseif (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $mapLocationUrl, $matches)) {
                $mapLat = $matches[1];
                $mapLng = $matches[2];
            }
        }
        
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
            'map_location_url' => $mapLocationUrl,
            'map_latitude' => $mapLat,
            'map_longitude' => $mapLng,
        ];

        return view('frontend.app-info.index', compact('appInfo'));
    }
}
