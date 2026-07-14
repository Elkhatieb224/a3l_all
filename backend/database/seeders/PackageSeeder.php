<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name_ar' => 'باقة مجانية',
                'name_en' => 'Free Package',
                'name_tr' => 'Ücretsiz Paket',
                'description_ar' => 'باقة مجانية للمستخدمين الجدد',
                'description_en' => 'Free package for new users',
                'description_tr' => 'Yeni kullanıcılar için ücretsiz paket',
                'price' => 0,
                'currency' => 'SYP',
                'duration_days' => 30,
                'ads_limit' => 3,
                'featured_ads' => false,
                'urgent_ads' => false,
                'priority_support' => false,
                'homepage_display' => false,
                'order' => 1,
            ],
            [
                'name_ar' => 'باقة أساسية',
                'name_en' => 'Basic Package',
                'name_tr' => 'Temel Paket',
                'description_ar' => 'باقة مناسبة للاستخدام الشخصي',
                'description_en' => 'Suitable package for personal use',
                'description_tr' => 'Kişisel kullanım için uygun paket',
                'price' => 500000,
                'currency' => 'SYP',
                'duration_days' => 30,
                'ads_limit' => 10,
                'featured_ads' => false,
                'urgent_ads' => false,
                'priority_support' => false,
                'homepage_display' => false,
                'order' => 2,
            ],
            [
                'name_ar' => 'باقة متقدمة',
                'name_en' => 'Advanced Package',
                'name_tr' => 'Gelişmiş Paket',
                'description_ar' => 'باقة للمستخدمين المحترفين',
                'description_en' => 'Package for professional users',
                'description_tr' => 'Profesyonel kullanıcılar için paket',
                'price' => 1200000,
                'currency' => 'SYP',
                'duration_days' => 30,
                'ads_limit' => 50,
                'featured_ads' => true,
                'urgent_ads' => true,
                'priority_support' => false,
                'homepage_display' => true,
                'order' => 3,
            ],
            [
                'name_ar' => 'باقة احترافية',
                'name_en' => 'Professional Package',
                'name_tr' => 'Profesyonel Paket',
                'description_ar' => 'باقة شاملة للشركات',
                'description_en' => 'Comprehensive package for businesses',
                'description_tr' => 'İşletmeler için kapsamlı paket',
                'price' => 2500000,
                'currency' => 'SYP',
                'duration_days' => 30,
                'ads_limit' => 200,
                'featured_ads' => true,
                'urgent_ads' => true,
                'priority_support' => true,
                'homepage_display' => true,
                'order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}

