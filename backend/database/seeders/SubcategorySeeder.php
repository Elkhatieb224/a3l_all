<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates 2 subcategories for each main category
     * Each subcategory has 1 child subcategory (multi-level)
     */
    public function run(): void
    {
        // Get all main categories
        $categories = Category::all();

        foreach ($categories as $category) {
            // Create 2 main subcategories for each category
            for ($i = 1; $i <= 2; $i++) {
                $subcategory = Subcategory::create([
                    'category_id' => $category->id,
                    'parent_subcategory_id' => null, // Main level subcategory
                    'name_ar' => $category->name_ar . ' - القسم الفرعي ' . $i,
                    'name_en' => $category->name_en . ' - Subcategory ' . $i,
                    'name_tr' => $category->name_tr . ' - Alt Kategori ' . $i,
                    'slug' => Str::slug($category->slug . '-sub-' . $i),
                    'description_ar' => 'قسم فرعي تحت ' . $category->name_ar,
                    'description_en' => 'Subcategory under ' . $category->name_en,
                    'description_tr' => $category->name_tr . ' altında alt kategori',
                    'icon' => null, // Can be added later
                    'order' => $i,
                    'is_active' => true,
                ]);

                // Create 1 child subcategory for each main subcategory (Level 2)
                Subcategory::create([
                    'category_id' => $category->id,
                    'parent_subcategory_id' => $subcategory->id, // Child of the subcategory above
                    'name_ar' => $subcategory->name_ar . ' - تفصيل',
                    'name_en' => $subcategory->name_en . ' - Detail',
                    'name_tr' => $subcategory->name_tr . ' - Detay',
                    'slug' => Str::slug($subcategory->slug . '-detail'),
                    'description_ar' => 'تفصيل تحت ' . $subcategory->name_ar,
                    'description_en' => 'Detail under ' . $subcategory->name_en,
                    'description_tr' => $subcategory->name_tr . ' altında detay',
                    'icon' => null,
                    'order' => 1,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('✅ تم إنشاء الأقسام الفرعية بنجاح!');
        $this->command->info('📊 الإحصائيات:');
        $this->command->info('   - عدد الأقسام الرئيسية: ' . $categories->count());
        $this->command->info('   - أقسام فرعية المستوى الأول: ' . ($categories->count() * 2));
        $this->command->info('   - أقسام فرعية المستوى الثاني: ' . ($categories->count() * 2));
        $this->command->info('   - الإجمالي: ' . ($categories->count() * 4) . ' قسم فرعي');
    }
}

