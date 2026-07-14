<?php

/**
 * طبقات حدود سوريا الإدارية (GeoJSON).
 *
 * المصدر: https://github.com/alahwa/Syria-GeoJson-Maps
 * الملفات تحت public/geo/syria-geojson/ وتُخدم كأصول ثابتة.
 */
return [
    'public_path_prefix' => '/geo/syria-geojson',

    'layers' => [
        [
            'id' => 'syr_admin1',
            'file' => 'syr_admin1.geojson',
            'admin_level' => 1,
            'geometry_type' => 'Polygon',
            'label_ar' => 'المحافظات',
            'label_en' => 'Governorates',
            'label_tr' => 'İller',
        ],
        [
            'id' => 'syr_admin2',
            'file' => 'syr_admin2.geojson',
            'admin_level' => 2,
            'geometry_type' => 'Polygon',
            'label_ar' => 'المستوى الإداري 2',
            'label_en' => 'Admin level 2',
            'label_tr' => 'İdari seviye 2',
        ],
        [
            'id' => 'syr_admin3',
            'file' => 'syr_admin3.geojson',
            'admin_level' => 3,
            'geometry_type' => 'Polygon',
            'label_ar' => 'المستوى الإداري 3',
            'label_en' => 'Admin level 3',
            'label_tr' => 'İdari seviye 3',
        ],
        [
            'id' => 'syr_admin4_point',
            'file' => 'syr_admin4_point.geojson',
            'admin_level' => 4,
            'geometry_type' => 'Point',
            'label_ar' => 'المستوى 4 (نقاط)',
            'label_en' => 'Admin level 4 (points)',
            'label_tr' => 'Seviye 4 (noktalar)',
        ],
        [
            'id' => 'syr_admin5',
            'file' => 'syr_admin5.geojson',
            'admin_level' => 5,
            'geometry_type' => 'Polygon',
            'label_ar' => 'أحياء دمشق (مستخدمة في بناء ad_regions_sy)',
            'label_en' => 'Damascus neighborhoods (feeds ad_regions_sy build)',
            'label_tr' => 'Şam mahalleleri (ad_regions_sy için)',
        ],
    ],
];
