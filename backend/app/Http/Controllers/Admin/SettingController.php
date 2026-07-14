<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token', '_method') as $key => $value) {
            // Determine group based on key prefix
            $group = 'general';
            if (str_starts_with($key, 'app_info_')) {
                $group = 'app_info';
            } elseif (str_starts_with($key, 'ad_') || str_starts_with($key, 'free_ads_') || str_starts_with($key, 'max_') || str_starts_with($key, 'require_')) {
                $group = 'ads';
            } elseif (str_starts_with($key, 'verification_')) {
                $group = 'general';
            } elseif (str_starts_with($key, 'terms_conditions_') || str_starts_with($key, 'privacy_policy_')) {
                $group = 'legal';
            } elseif (str_starts_with($key, 'email_')) {
                $group = 'email';
            } elseif ($key === 'country_codes') {
                $group = 'general';
                // Validate JSON format
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue; // Skip invalid JSON
                }
                $value = json_encode($decoded); // Ensure proper JSON encoding
                Setting::set($key, $value, $group, 'json');
                continue;
            }
            
            Setting::set($key, $value, $group);
        }

        // Update currency symbol when default_currency changes
        if ($request->has('default_currency')) {
            $currency = $request->input('default_currency', 'SYP');
            $symbol = get_currency_symbol_for_code($currency);
            Setting::set('currency_symbol', $symbol, 'general', 'text');
        }

        ActivityLog::log('settings_updated', null, $request->except('_token', '_method'));

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }

    public function addCountryCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10',
        ], [
            'code.required' => 'يجب إدخال كود الدولة',
            'code.max' => 'كود الدولة يجب ألا يتجاوز 10 أحرف',
        ]);

        // Get existing country codes
        $countryCodes = Setting::get('country_codes', []);
        
        // Check if code already exists
        $codeExists = collect($countryCodes)->contains(function ($item) use ($request) {
            return isset($item['code']) && $item['code'] === $request->code;
        });

        if ($codeExists) {
            return response()->json([
                'success' => false,
                'message' => 'كود الدولة موجود بالفعل'
            ], 422);
        }

        // Add new country code (only code, without other data)
        $countryCodes[] = [
            'code' => $request->code,
        ];

        // Save updated country codes
        Setting::set('country_codes', $countryCodes, 'general', 'json');

        ActivityLog::log('country_code_added', null, ['code' => $request->code]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة كود الدولة بنجاح',
            'code' => $request->code,
        ]);
    }

    public function deleteCountryCode($code)
    {
        // Decode the code from URL
        $code = urldecode($code);
        
        // Get existing country codes
        $countryCodes = Setting::get('country_codes', []);
        
        // Remove the code
        $countryCodes = collect($countryCodes)->reject(function ($item) use ($code) {
            return isset($item['code']) && $item['code'] === $code;
        })->values()->toArray();

        // Save updated country codes
        Setting::set('country_codes', $countryCodes, 'general', 'json');

        ActivityLog::log('country_code_deleted', null, ['code' => $code]);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف كود الدولة بنجاح',
        ]);
    }
}

