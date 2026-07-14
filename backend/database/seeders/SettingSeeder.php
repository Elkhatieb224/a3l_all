<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'site_name', 'value' => 'أعلنها', 'group' => 'general', 'type' => 'text'],
            ['key' => 'site_description', 'value' => 'منصة الإعلانات المبوبة الأولى', 'group' => 'general', 'type' => 'text'],
            ['key' => 'contact_email', 'value' => 'info@a3lenha.com', 'group' => 'general', 'type' => 'text'],
            ['key' => 'contact_phone', 'value' => '+963 11 123 4567', 'group' => 'general', 'type' => 'text'],
            ['key' => 'default_language', 'value' => 'ar', 'group' => 'general', 'type' => 'text'],
            ['key' => 'timezone', 'value' => 'Asia/Damascus', 'group' => 'general', 'type' => 'text'],
            ['key' => 'default_currency', 'value' => 'SYP', 'group' => 'general', 'type' => 'text'],
            ['key' => 'currency_symbol', 'value' => 'ل.س', 'group' => 'general', 'type' => 'text'],
            
            // Ad Settings
            ['key' => 'ad_default_duration', 'value' => '30', 'group' => 'ads', 'type' => 'number'],
            ['key' => 'free_ads_limit', 'value' => '3', 'group' => 'ads', 'type' => 'number'],
            ['key' => 'require_ad_approval', 'value' => '1', 'group' => 'ads', 'type' => 'boolean'],
            ['key' => 'max_images_per_ad', 'value' => '10', 'group' => 'ads', 'type' => 'number'],
            ['key' => 'max_image_size_mb', 'value' => '5', 'group' => 'ads', 'type' => 'number'],
            
            // Payment Settings
            ['key' => 'enable_payments', 'value' => '1', 'group' => 'payments', 'type' => 'boolean'],
            ['key' => 'payment_methods', 'value' => json_encode(['bank_transfer', 'cash']), 'group' => 'payments', 'type' => 'json'],
            
            // SEO Settings
            ['key' => 'meta_keywords', 'value' => 'إعلانات, بيع, شراء, سوريا', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'meta_description', 'value' => 'أفضل منصة للإعلانات المبوبة في سوريا', 'group' => 'seo', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

