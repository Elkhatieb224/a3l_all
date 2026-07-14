<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class UpdatedCategorySeeder extends Seeder
{
    public function run(): void
    {
        // حذف الأقسام القديمة
        Category::query()->delete();
        Subcategory::query()->delete();

        // 1. Vehicles (المركبات)
        $vehicles = Category::create([
            'name_ar' => 'المركبات',
            'name_en' => 'Vehicles',
            'name_tr' => 'Vasıtalar',
            'slug' => 'vehicles',
            'icon' => '🚗',
            'description_ar' => 'سيارات ومركبات للبيع والشراء',
            'description_en' => 'Cars and vehicles for sale and purchase',
            'description_tr' => 'Satılık ve alınır araçlar',
            'order' => 1,
            'is_active' => true,
            'custom_fields' => json_encode($this->getVehiclesFields())
        ]);

        // 2. Real Estate (العقارات)
        $realEstate = Category::create([
            'name_ar' => 'العقارات',
            'name_en' => 'Real Estate',
            'name_tr' => 'Emlak',
            'slug' => 'real-estate',
            'icon' => '🏠',
            'description_ar' => 'عقارات للبيع والإيجار',
            'description_en' => 'Real estate for sale and rent',
            'description_tr' => 'Satılık ve kiralık emlak',
            'order' => 2,
            'is_active' => true,
            'custom_fields' => json_encode($this->getRealEstateFields())
        ]);

        Subcategory::insert([
            ['category_id' => $realEstate->id, 'name_ar' => 'شقق للبيع', 'name_en' => 'Apartments for Sale', 'name_tr' => 'Satılık Daire', 'slug' => 'apartments-for-sale', 'order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $realEstate->id, 'name_ar' => 'شقق للإيجار', 'name_en' => 'Apartments for Rent', 'name_tr' => 'Kiralık Daire', 'slug' => 'apartments-for-rent', 'order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $realEstate->id, 'name_ar' => 'فلل للبيع', 'name_en' => 'Villas for Sale', 'name_tr' => 'Satılık Villa', 'slug' => 'villas-for-sale', 'order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Jobs (الوظائف)
        Category::create([
            'name_ar' => 'الوظائف',
            'name_en' => 'Jobs',
            'name_tr' => 'İş İlanları',
            'slug' => 'jobs',
            'icon' => '💼',
            'description_ar' => 'فرص عمل ووظائف',
            'description_en' => 'Job opportunities',
            'description_tr' => 'İş fırsatları',
            'order' => 3,
            'is_active' => true,
            'custom_fields' => json_encode($this->getJobsFields())
        ]);

        // 4. In-home Help (مساعدين منزليين)
        Category::create([
            'name_ar' => 'مساعدين منزليين',
            'name_en' => 'In-home Help',
            'name_tr' => 'Ev İçi Yardım',
            'slug' => 'in-home-help',
            'icon' => '🏡',
            'description_ar' => 'عمال منزليين ومساعدين',
            'description_en' => 'Domestic workers and helpers',
            'description_tr' => 'Ev işleri yardımcıları',
            'order' => 4,
            'is_active' => true,
            'custom_fields' => json_encode($this->getInHomeHelpFields())
        ]);

        // 5. Machinery (الآلات والمعدات)
        $machinery = Category::create([
            'name_ar' => 'الآلات والمعدات',
            'name_en' => 'Machinery',
            'name_tr' => 'İş Makineleri & Sanayi',
            'slug' => 'machinery',
            'icon' => '🏗️',
            'description_ar' => 'آلات ومعدات صناعية وبناء',
            'description_en' => 'Industrial and construction machinery',
            'description_tr' => 'Endüstriyel ve inşaat makineleri',
            'order' => 5,
            'is_active' => true,
            'custom_fields' => json_encode($this->getMachineryFields())
        ]);

        Subcategory::insert([
            ['category_id' => $machinery->id, 'name_ar' => 'آلات البناء', 'name_en' => 'Construction Machinery', 'name_tr' => 'İnşaat Makineleri', 'slug' => 'construction-machinery', 'order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $machinery->id, 'name_ar' => 'الآلات الزراعية', 'name_en' => 'Agricultural Machinery', 'name_tr' => 'Tarım Makineleri', 'slug' => 'agricultural-machinery', 'order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => $machinery->id, 'name_ar' => 'الصناعة', 'name_en' => 'Industry', 'name_tr' => 'Sanayi', 'slug' => 'industry', 'order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 6-9: Categories أخرى
        $this->createRemainingCategories();
    }

    private function getVehiclesFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
            ['id' => 'brand', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'الماركة', 'en' => 'Brand', 'tr' => 'Marka']],
            ['id' => 'model', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'الموديل', 'en' => 'Model', 'tr' => 'Model']],
            ['id' => 'year', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السنة', 'en' => 'Year', 'tr' => 'Yıl']],
            ['id' => 'mileage', 'type' => 'number', 'required' => false, 'label' => ['ar' => 'الكيلومترات', 'en' => 'Mileage', 'tr' => 'Kilometre']],
        ];
    }

    private function getRealEstateFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
            ['id' => 'area_m2', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'المساحة (م²)', 'en' => 'Area (m²)', 'tr' => 'Alan (m²)']],
            ['id' => 'room_count', 'type' => 'select', 'required' => true, 'options' => [
                ['id' => '1+0', 'ar' => '1+0', 'en' => '1+0', 'tr' => '1+0'],
                ['id' => '1+1', 'ar' => '1+1', 'en' => '1+1', 'tr' => '1+1'],
                ['id' => '2+1', 'ar' => '2+1', 'en' => '2+1', 'tr' => '2+1'],
                ['id' => '3+1', 'ar' => '3+1', 'en' => '3+1', 'tr' => '3+1'],
            ], 'label' => ['ar' => 'عدد الغرف', 'en' => 'Rooms', 'tr' => 'Oda Sayısı']],
        ];
    }

    private function getJobsFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'عنوان الوظيفة', 'en' => 'Job Title', 'tr' => 'İş Başlığı']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'salary', 'type' => 'number', 'required' => false, 'label' => ['ar' => 'الراتب', 'en' => 'Salary', 'tr' => 'Maaş']],
            ['id' => 'company_name', 'type' => 'text', 'required' => false, 'label' => ['ar' => 'اسم الشركة', 'en' => 'Company', 'tr' => 'Şirket']],
        ];
    }

    private function getInHomeHelpFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'price', 'type' => 'number', 'required' => false, 'label' => ['ar' => 'الراتب', 'en' => 'Salary', 'tr' => 'Maaş']],
        ];
    }

    private function getMachineryFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
        ];
    }

    private function createRemainingCategories()
    {
        $categories = [
            ['name_ar' => 'الحيوانات', 'name_en' => 'Pets & Livestock', 'name_tr' => 'Hayvanlar', 'slug' => 'pets', 'icon' => '🐾', 'order' => 6],
            ['name_ar' => 'دروس خصوصية', 'name_en' => 'Tutors', 'name_tr' => 'Özel Dersler', 'slug' => 'tutors', 'icon' => '📚', 'order' => 7],
            ['name_ar' => 'سلع مستعملة', 'name_en' => 'Used Items', 'name_tr' => 'İkinci El', 'slug' => 'used-items', 'icon' => '📦', 'order' => 8],
            ['name_ar' => 'قطع غيار', 'name_en' => 'Vehicle Parts', 'name_tr' => 'Yedek Parça', 'slug' => 'vehicle-parts', 'icon' => '🔧', 'order' => 9],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name_ar' => $cat['name_ar'],
                'name_en' => $cat['name_en'],
                'name_tr' => $cat['name_tr'],
                'slug' => $cat['slug'],
                'icon' => $cat['icon'],
                'description_ar' => $cat['name_ar'],
                'description_en' => $cat['name_en'],
                'description_tr' => $cat['name_tr'],
                'order' => $cat['order'],
                'is_active' => true,
                'custom_fields' => json_encode([
                    ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
                    ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
                    ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
                ])
            ]);
        }
    }
}
