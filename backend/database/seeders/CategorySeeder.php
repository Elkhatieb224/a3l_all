<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Vehicles (المركبات)
        $vehicles = Category::create([
            'name_ar' => 'المركبات',
            'name_en' => 'Vehicles',
            'name_tr' => 'Vasıtalar',
            'slug' => 'vehicles',
            'icon' => null, // يتم رفع الصورة من لوحة التحكم
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
            'icon' => null,
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
            ['category_id' => $realEstate->id, 'name_ar' => 'أراضي للبيع', 'name_en' => 'Land for Sale', 'name_tr' => 'Satılık Arsa', 'slug' => 'land-for-sale', 'order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Jobs (الوظائف)
        Category::create([
            'name_ar' => 'الوظائف',
            'name_en' => 'Jobs',
            'name_tr' => 'İş İlanları',
            'slug' => 'jobs',
            'icon' => null,
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
            'icon' => null,
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
            'name_tr' => 'İş Makineleri',
            'slug' => 'machinery',
            'icon' => null,
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
            ['id' => 'gearbox', 'type' => 'select', 'required' => true, 'options' => [
                ['id' => 'manual', 'ar' => 'يدوي', 'en' => 'Manual', 'tr' => 'Manuel'],
                ['id' => 'automatic', 'ar' => 'أوتوماتيك', 'en' => 'Automatic', 'tr' => 'Otomatik']
            ], 'label' => ['ar' => 'ناقل الحركة', 'en' => 'Transmission', 'tr' => 'Vites']],
            ['id' => 'color', 'type' => 'select', 'required' => false, 'options' => [
                ['id' => 'white', 'ar' => 'أبيض', 'en' => 'White', 'tr' => 'Beyaz'],
                ['id' => 'black', 'ar' => 'أسود', 'en' => 'Black', 'tr' => 'Siyah'],
                ['id' => 'blue', 'ar' => 'أزرق', 'en' => 'Blue', 'tr' => 'Mavi'],
                ['id' => 'red', 'ar' => 'أحمر', 'en' => 'Red', 'tr' => 'Kırmızı'],
                ['id' => 'gray', 'ar' => 'رمادي', 'en' => 'Gray', 'tr' => 'Gri']
            ], 'label' => ['ar' => 'اللون', 'en' => 'Color', 'tr' => 'Renk']],
            ['id' => 'condition', 'type' => 'select', 'required' => true, 'options' => [
                ['id' => 'new', 'ar' => 'جديد', 'en' => 'New', 'tr' => 'Sıfır'],
                ['id' => 'used', 'ar' => 'مستعمل', 'en' => 'Used', 'tr' => 'İkinci El']
            ], 'label' => ['ar' => 'الحالة', 'en' => 'Condition', 'tr' => 'Durum']],
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
                ['id' => '4+1', 'ar' => '4+1', 'en' => '4+1', 'tr' => '4+1'],
            ], 'label' => ['ar' => 'عدد الغرف', 'en' => 'Rooms', 'tr' => 'Oda Sayısı']],
            ['id' => 'floor', 'type' => 'number', 'required' => false, 'label' => ['ar' => 'الطابق', 'en' => 'Floor', 'tr' => 'Kat']],
            ['id' => 'building_age', 'type' => 'select', 'required' => false, 'options' => [
                ['id' => '0', 'ar' => 'جديد', 'en' => 'New', 'tr' => 'Sıfır'],
                ['id' => '1-5', 'ar' => '1-5 سنوات', 'en' => '1-5 years', 'tr' => '1-5 Yıl'],
                ['id' => '10+', 'ar' => '10+ سنوات', 'en' => '10+ years', 'tr' => '10+ Yıl']
            ], 'label' => ['ar' => 'عمر البناء', 'en' => 'Building Age', 'tr' => 'Bina Yaşı']],
        ];
    }

    private function getJobsFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'عنوان الوظيفة', 'en' => 'Job Title', 'tr' => 'İş Başlığı']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'job_type', 'type' => 'select', 'required' => true, 'options' => [
                ['id' => 'full_time', 'ar' => 'دوام كامل', 'en' => 'Full Time', 'tr' => 'Tam Zamanlı'],
                ['id' => 'part_time', 'ar' => 'دوام جزئي', 'en' => 'Part Time', 'tr' => 'Yarı Zamanlı'],
                ['id' => 'remote', 'ar' => 'عن بعد', 'en' => 'Remote', 'tr' => 'Uzaktan'],
            ], 'label' => ['ar' => 'نوع العمل', 'en' => 'Job Type', 'tr' => 'İş Tipi']],
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
            ['id' => 'work_type', 'type' => 'select', 'required' => true, 'options' => [
                ['id' => 'live_in', 'ar' => 'مقيم', 'en' => 'Live-in', 'tr' => 'Yatılı'],
                ['id' => 'full_time', 'ar' => 'دوام كامل', 'en' => 'Full Time', 'tr' => 'Tam Zamanlı'],
                ['id' => 'part_time', 'ar' => 'دوام جزئي', 'en' => 'Part Time', 'tr' => 'Yarı Zamanlı'],
            ], 'label' => ['ar' => 'نوع العمل', 'en' => 'Work Type', 'tr' => 'Çalışma Şekli']],
            ['id' => 'nationality', 'type' => 'text', 'required' => false, 'label' => ['ar' => 'الجنسية', 'en' => 'Nationality', 'tr' => 'Uyruk']],
        ];
    }

    private function getMachineryFields()
    {
        return [
            ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
            ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
            ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
            ['id' => 'condition', 'type' => 'select', 'required' => true, 'options' => [
                ['id' => 'new', 'ar' => 'جديد', 'en' => 'New', 'tr' => 'Yeni'],
                ['id' => 'used', 'ar' => 'مستعمل', 'en' => 'Used', 'tr' => 'Kullanılmış']
            ], 'label' => ['ar' => 'الحالة', 'en' => 'Condition', 'tr' => 'Durum']],
        ];
    }

    private function createRemainingCategories()
    {
        // 6. Pets & Livestock
        Category::create([
            'name_ar' => 'الحيوانات الأليفة والمواشي',
            'name_en' => 'Pets & Livestock',
            'name_tr' => 'Hayvanlar',
            'slug' => 'pets-livestock',
            'icon' => null,
            'description_ar' => 'حيوانات أليفة ومواشي',
            'description_en' => 'Pets and livestock',
            'description_tr' => 'Evcil hayvanlar ve çiftlik hayvanları',
            'order' => 6,
            'is_active' => true,
            'custom_fields' => json_encode([
                ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
                ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
                ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
                ['id' => 'age', 'type' => 'select', 'required' => false, 'options' => [
                    ['id' => '0_3m', 'ar' => '0-3 شهور', 'en' => '0-3 months', 'tr' => '0-3 Ay'],
                    ['id' => '1y', 'ar' => 'سنة', 'en' => '1 Year', 'tr' => '1 Yıl'],
                ], 'label' => ['ar' => 'العمر', 'en' => 'Age', 'tr' => 'Yaş']],
            ])
        ]);

        // 7. Tutors & Private Lessons
        Category::create([
            'name_ar' => 'دروس خصوصية',
            'name_en' => 'Tutors & Private Lessons',
            'name_tr' => 'Özel Dersler',
            'slug' => 'tutors-private-lessons',
            'icon' => null,
            'description_ar' => 'دروس خصوصية ومعلمين',
            'description_en' => 'Private lessons and tutors',
            'description_tr' => 'Özel dersler ve öğretmenler',
            'order' => 7,
            'is_active' => true,
            'custom_fields' => json_encode([
                ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'عنوان الدرس', 'en' => 'Lesson Title', 'tr' => 'Ders Başlığı']],
                ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
                ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
            ])
        ]);

        // 8. Used & Brand New Items
        Category::create([
            'name_ar' => 'سلع مستعملة وجديدة',
            'name_en' => 'Used & Brand New Items',
            'name_tr' => 'İkinci El Eşyalar',
            'slug' => 'used-brand-new-items',
            'icon' => null,
            'description_ar' => 'سلع مستعملة وجديدة',
            'description_en' => 'Used and brand new items',
            'description_tr' => 'İkinci el ve sıfır eşyalar',
            'order' => 8,
            'is_active' => true,
            'custom_fields' => json_encode([
                ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
                ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
                ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
            ])
        ]);

        // 9. Vehicle Parts
        Category::create([
            'name_ar' => 'قطع غيار السيارات',
            'name_en' => 'Vehicle Parts',
            'name_tr' => 'Araç Yedek Parçaları',
            'slug' => 'vehicle-parts',
            'icon' => null,
            'description_ar' => 'قطع غيار ومستلزمات السيارات',
            'description_en' => 'Vehicle parts and accessories',
            'description_tr' => 'Araç yedek parçaları',
            'order' => 9,
            'is_active' => true,
            'custom_fields' => json_encode([
                ['id' => 'title', 'type' => 'text', 'required' => true, 'label' => ['ar' => 'العنوان', 'en' => 'Title', 'tr' => 'Başlık']],
                ['id' => 'description', 'type' => 'textarea', 'required' => true, 'label' => ['ar' => 'الوصف', 'en' => 'Description', 'tr' => 'Açıklama']],
                ['id' => 'price', 'type' => 'number', 'required' => true, 'label' => ['ar' => 'السعر', 'en' => 'Price', 'tr' => 'Fiyat']],
            ])
        ]);
    }
}
