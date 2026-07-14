<?php

/**
 * سوريا: محافظات ← مراكز ← مناطق/أحياء من GeoJSON.
 * - admin1/2/3 (OCHA) للمحافظات الأخرى.
 * - admin5 لدمشق (C1001): كل حي كمركز مع مستوى ثالث ثابت (ضمن الحي) لأن admin2/admin3
 *   يعطيان خلية واحدة فقط لمدينة دمشق بينما الأحياء التفصيلية في syr_admin5.
 *   الأسماء العربية لأحياء دمشق من DAMASCUS_HAY_AR في database/scripts/build_ad_regions_sy_from_geojson.py.
 *
 * تحديث: python3 database/scripts/build_ad_regions_sy_from_geojson.py
 * المصدر: https://github.com/alahwa/Syria-GeoJson-Maps
 */
return [
    [
        'code' => 'SY01',
        'name_ar' => 'مدينة دمشق',
        'name_en' => 'Damascus',
        'name_tr' => 'Şam ili',
        'match_names' => [
            'مدينة دمشق',
            'Damascus',
        ],
        'cities' => [
            [
                'code' => 'C10011010',
                'name_ar' => 'كفر سوسة',
                'name_en' => 'Kafar Soussa',
                'name_tr' => 'Kafar Soussa',
                'match_names' => [
                    'كفر سوسة',
                    'Kafar Soussa',
                ],
                'districts' => [
                    [
                        'code' => 'C10011010-MAIN',
                        'name_ar' => 'كفر سوسة',
                        'name_en' => 'Kafar Soussa',
                        'name_tr' => 'Kafar Soussa',
                        'match_names' => [
                        'كفر سوسة',
                        'Kafar Soussa',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011011',
                'name_ar' => 'جوبر',
                'name_en' => 'Jowbar',
                'name_tr' => 'Jowbar',
                'match_names' => [
                    'جوبر',
                    'Jowbar',
                ],
                'districts' => [
                    [
                        'code' => 'C10011011-MAIN',
                        'name_ar' => 'جوبر',
                        'name_en' => 'Jowbar',
                        'name_tr' => 'Jowbar',
                        'match_names' => [
                        'جوبر',
                        'Jowbar',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011012',
                'name_ar' => 'القابون',
                'name_en' => 'Al-Kaboon',
                'name_tr' => 'Al-Kaboon',
                'match_names' => [
                    'القابون',
                    'Al-Kaboon',
                ],
                'districts' => [
                    [
                        'code' => 'C10011012-MAIN',
                        'name_ar' => 'القابون',
                        'name_en' => 'Al-Kaboon',
                        'name_tr' => 'Al-Kaboon',
                        'match_names' => [
                        'القابون',
                        'Al-Kaboon',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011013',
                'name_ar' => 'برزة',
                'name_en' => 'Barza',
                'name_tr' => 'Barza',
                'match_names' => [
                    'برزة',
                    'Barza',
                ],
                'districts' => [
                    [
                        'code' => 'C10011013-MAIN',
                        'name_ar' => 'برزة',
                        'name_en' => 'Barza',
                        'name_tr' => 'Barza',
                        'match_names' => [
                        'برزة',
                        'Barza',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011014',
                'name_ar' => 'حميش',
                'name_en' => 'Hamish',
                'name_tr' => 'Hamish',
                'match_names' => [
                    'حميش',
                    'Hamish',
                ],
                'districts' => [
                    [
                        'code' => 'C10011014-MAIN',
                        'name_ar' => 'حميش',
                        'name_en' => 'Hamish',
                        'name_tr' => 'Hamish',
                        'match_names' => [
                        'حميش',
                        'Hamish',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011015',
                'name_ar' => 'مساكن برزة',
                'name_en' => 'Masakin Barza',
                'name_tr' => 'Masakin Barza',
                'match_names' => [
                    'مساكن برزة',
                    'Masakin Barza',
                ],
                'districts' => [
                    [
                        'code' => 'C10011015-MAIN',
                        'name_ar' => 'مساكن برزة',
                        'name_en' => 'Masakin Barza',
                        'name_tr' => 'Masakin Barza',
                        'match_names' => [
                        'مساكن برزة',
                        'Masakin Barza',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011016',
                'name_ar' => 'بساتين أبو جرش',
                'name_en' => 'Basatin Abou Jarash',
                'name_tr' => 'Basatin Abou Jarash',
                'match_names' => [
                    'بساتين أبو جرش',
                    'Basatin Abou Jarash',
                ],
                'districts' => [
                    [
                        'code' => 'C10011016-MAIN',
                        'name_ar' => 'بساتين أبو جرش',
                        'name_en' => 'Basatin Abou Jarash',
                        'name_tr' => 'Basatin Abou Jarash',
                        'match_names' => [
                        'بساتين أبو جرش',
                        'Basatin Abou Jarash',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011017',
                'name_ar' => 'التجارة',
                'name_en' => 'Al Tijara',
                'name_tr' => 'Al Tijara',
                'match_names' => [
                    'التجارة',
                    'Al Tijara',
                ],
                'districts' => [
                    [
                        'code' => 'C10011017-MAIN',
                        'name_ar' => 'التجارة',
                        'name_en' => 'Al Tijara',
                        'name_tr' => 'Al Tijara',
                        'match_names' => [
                        'التجارة',
                        'Al Tijara',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011018',
                'name_ar' => 'العباسيين',
                'name_en' => 'Al  \'Abassiyin',
                'name_tr' => 'Al  \'Abassiyin',
                'match_names' => [
                    'العباسيين',
                    'Al  \'Abassiyin',
                ],
                'districts' => [
                    [
                        'code' => 'C10011018-MAIN',
                        'name_ar' => 'العباسيين',
                        'name_en' => 'Al  \'Abassiyin',
                        'name_tr' => 'Al  \'Abassiyin',
                        'match_names' => [
                        'العباسيين',
                        'Al  \'Abassiyin',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011019',
                'name_ar' => 'الزبلطاني',
                'name_en' => 'Al Zabaltali',
                'name_tr' => 'Al Zabaltali',
                'match_names' => [
                    'الزبلطاني',
                    'Al Zabaltali',
                ],
                'districts' => [
                    [
                        'code' => 'C10011019-MAIN',
                        'name_ar' => 'الزبلطاني',
                        'name_en' => 'Al Zabaltali',
                        'name_tr' => 'Al Zabaltali',
                        'match_names' => [
                        'الزبلطاني',
                        'Al Zabaltali',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011020',
                'name_ar' => 'الدويلعة',
                'name_en' => 'Douwayl\'a',
                'name_tr' => 'Douwayl\'a',
                'match_names' => [
                    'الدويلعة',
                    'Douwayl\'a',
                ],
                'districts' => [
                    [
                        'code' => 'C10011020-MAIN',
                        'name_ar' => 'الدويلعة',
                        'name_en' => 'Douwayl\'a',
                        'name_tr' => 'Douwayl\'a',
                        'match_names' => [
                        'الدويلعة',
                        'Douwayl\'a',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011021',
                'name_ar' => 'الصناعة',
                'name_en' => 'Al Sina\'a',
                'name_tr' => 'Al Sina\'a',
                'match_names' => [
                    'الصناعة',
                    'Al Sina\'a',
                ],
                'districts' => [
                    [
                        'code' => 'C10011021-MAIN',
                        'name_ar' => 'الصناعة',
                        'name_en' => 'Al Sina\'a',
                        'name_tr' => 'Al Sina\'a',
                        'match_names' => [
                        'الصناعة',
                        'Al Sina\'a',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011022',
                'name_ar' => 'بستان النور',
                'name_en' => 'Bustan Al Nour',
                'name_tr' => 'Bustan Al Nour',
                'match_names' => [
                    'بستان النور',
                    'Bustan Al Nour',
                ],
                'districts' => [
                    [
                        'code' => 'C10011022-MAIN',
                        'name_ar' => 'بستان النور',
                        'name_en' => 'Bustan Al Nour',
                        'name_tr' => 'Bustan Al Nour',
                        'match_names' => [
                        'بستان النور',
                        'Bustan Al Nour',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011023',
                'name_ar' => 'اليرموك',
                'name_en' => 'Yarmouk',
                'name_tr' => 'Yarmouk',
                'match_names' => [
                    'اليرموك',
                    'Yarmouk',
                ],
                'districts' => [
                    [
                        'code' => 'C10011023-MAIN',
                        'name_ar' => 'اليرموك',
                        'name_en' => 'Yarmouk',
                        'name_tr' => 'Yarmouk',
                        'match_names' => [
                        'اليرموك',
                        'Yarmouk',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011024',
                'name_ar' => 'فلسطين',
                'name_en' => 'Falastin',
                'name_tr' => 'Falastin',
                'match_names' => [
                    'فلسطين',
                    'Falastin',
                ],
                'districts' => [
                    [
                        'code' => 'C10011024-MAIN',
                        'name_ar' => 'فلسطين',
                        'name_en' => 'Falastin',
                        'name_tr' => 'Falastin',
                        'match_names' => [
                        'فلسطين',
                        'Falastin',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011025',
                'name_ar' => 'الحجر الأسود',
                'name_en' => 'Al Hajar Al Aswad',
                'name_tr' => 'Al Hajar Al Aswad',
                'match_names' => [
                    'الحجر الأسود',
                    'Al Hajar Al Aswad',
                ],
                'districts' => [
                    [
                        'code' => 'C10011025-MAIN',
                        'name_ar' => 'الحجر الأسود',
                        'name_en' => 'Al Hajar Al Aswad',
                        'name_tr' => 'Al Hajar Al Aswad',
                        'match_names' => [
                        'الحجر الأسود',
                        'Al Hajar Al Aswad',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011026',
                'name_ar' => 'العصاعة',
                'name_en' => 'Al Qusa\'',
                'name_tr' => 'Al Qusa\'',
                'match_names' => [
                    'العصاعة',
                    'Al Qusa\'',
                ],
                'districts' => [
                    [
                        'code' => 'C10011026-MAIN',
                        'name_ar' => 'العصاعة',
                        'name_en' => 'Al Qusa\'',
                        'name_tr' => 'Al Qusa\'',
                        'match_names' => [
                        'العصاعة',
                        'Al Qusa\'',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011027',
                'name_ar' => 'دمشق القديمة',
                'name_en' => 'Old Damasus',
                'name_tr' => 'Old Damasus',
                'match_names' => [
                    'دمشق القديمة',
                    'Old Damasus',
                ],
                'districts' => [
                    [
                        'code' => 'C10011027-MAIN',
                        'name_ar' => 'دمشق القديمة',
                        'name_en' => 'Old Damasus',
                        'name_tr' => 'Old Damasus',
                        'match_names' => [
                        'دمشق القديمة',
                        'Old Damasus',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011028',
                'name_ar' => 'القصور',
                'name_en' => 'Al Qusur',
                'name_tr' => 'Al Qusur',
                'match_names' => [
                    'القصور',
                    'Al Qusur',
                ],
                'districts' => [
                    [
                        'code' => 'C10011028-MAIN',
                        'name_ar' => 'القصور',
                        'name_en' => 'Al Qusur',
                        'name_tr' => 'Al Qusur',
                        'match_names' => [
                        'القصور',
                        'Al Qusur',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011029',
                'name_ar' => 'الأضوية',
                'name_en' => 'Al \'Adwi',
                'name_tr' => 'Al \'Adwi',
                'match_names' => [
                    'الأضوية',
                    'Al \'Adwi',
                ],
                'districts' => [
                    [
                        'code' => 'C10011029-MAIN',
                        'name_ar' => 'الأضوية',
                        'name_en' => 'Al \'Adwi',
                        'name_tr' => 'Al \'Adwi',
                        'match_names' => [
                        'الأضوية',
                        'Al \'Adwi',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011030',
                'name_ar' => 'الديوانية',
                'name_en' => 'Al Diwaniah',
                'name_tr' => 'Al Diwaniah',
                'match_names' => [
                    'الديوانية',
                    'Al Diwaniah',
                ],
                'districts' => [
                    [
                        'code' => 'C10011030-MAIN',
                        'name_ar' => 'الديوانية',
                        'name_en' => 'Al Diwaniah',
                        'name_tr' => 'Al Diwaniah',
                        'match_names' => [
                        'الديوانية',
                        'Al Diwaniah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011031',
                'name_ar' => 'الخطيب',
                'name_en' => 'Al Khatib',
                'name_tr' => 'Al Khatib',
                'match_names' => [
                    'الخطيب',
                    'Al Khatib',
                ],
                'districts' => [
                    [
                        'code' => 'C10011031-MAIN',
                        'name_ar' => 'الخطيب',
                        'name_en' => 'Al Khatib',
                        'name_tr' => 'Al Khatib',
                        'match_names' => [
                        'الخطيب',
                        'Al Khatib',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011032',
                'name_ar' => 'العقبة',
                'name_en' => 'Al \'Uqaybah',
                'name_tr' => 'Al \'Uqaybah',
                'match_names' => [
                    'العقبة',
                    'Al \'Uqaybah',
                ],
                'districts' => [
                    [
                        'code' => 'C10011032-MAIN',
                        'name_ar' => 'العقبة',
                        'name_en' => 'Al \'Uqaybah',
                        'name_tr' => 'Al \'Uqaybah',
                        'match_names' => [
                        'العقبة',
                        'Al \'Uqaybah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011033',
                'name_ar' => 'بغداد',
                'name_en' => 'Baghdad',
                'name_tr' => 'Baghdad',
                'match_names' => [
                    'بغداد',
                    'Baghdad',
                ],
                'districts' => [
                    [
                        'code' => 'C10011033-MAIN',
                        'name_ar' => 'بغداد',
                        'name_en' => 'Baghdad',
                        'name_tr' => 'Baghdad',
                        'match_names' => [
                        'بغداد',
                        'Baghdad',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011034',
                'name_ar' => 'الأقصاب',
                'name_en' => 'Al  Aqsab',
                'name_tr' => 'Al  Aqsab',
                'match_names' => [
                    'الأقصاب',
                    'Al  Aqsab',
                ],
                'districts' => [
                    [
                        'code' => 'C10011034-MAIN',
                        'name_ar' => 'الأقصاب',
                        'name_en' => 'Al  Aqsab',
                        'name_tr' => 'Al  Aqsab',
                        'match_names' => [
                        'الأقصاب',
                        'Al  Aqsab',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011035',
                'name_ar' => 'ركن الدين',
                'name_en' => 'Rukn Eddine',
                'name_tr' => 'Rukn Eddine',
                'match_names' => [
                    'ركن الدين',
                    'Rukn Eddine',
                ],
                'districts' => [
                    [
                        'code' => 'C10011035-MAIN',
                        'name_ar' => 'ركن الدين',
                        'name_en' => 'Rukn Eddine',
                        'name_tr' => 'Rukn Eddine',
                        'match_names' => [
                        'ركن الدين',
                        'Rukn Eddine',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011036',
                'name_ar' => 'المهاجرين',
                'name_en' => 'Al Muhajirin',
                'name_tr' => 'Al Muhajirin',
                'match_names' => [
                    'المهاجرين',
                    'Al Muhajirin',
                ],
                'districts' => [
                    [
                        'code' => 'C10011036-MAIN',
                        'name_ar' => 'المهاجرين',
                        'name_en' => 'Al Muhajirin',
                        'name_tr' => 'Al Muhajirin',
                        'match_names' => [
                        'المهاجرين',
                        'Al Muhajirin',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011037',
                'name_ar' => 'الصالحية',
                'name_en' => 'Al Salihya',
                'name_tr' => 'Al Salihya',
                'match_names' => [
                    'الصالحية',
                    'Al Salihya',
                ],
                'districts' => [
                    [
                        'code' => 'C10011037-MAIN',
                        'name_ar' => 'الصالحية',
                        'name_en' => 'Al Salihya',
                        'name_tr' => 'Al Salihya',
                        'match_names' => [
                        'الصالحية',
                        'Al Salihya',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011038',
                'name_ar' => 'الطلباني',
                'name_en' => 'Al Talbani',
                'name_tr' => 'Al Talbani',
                'match_names' => [
                    'الطلباني',
                    'Al Talbani',
                ],
                'districts' => [
                    [
                        'code' => 'C10011038-MAIN',
                        'name_ar' => 'الطلباني',
                        'name_en' => 'Al Talbani',
                        'name_tr' => 'Al Talbani',
                        'match_names' => [
                        'الطلباني',
                        'Al Talbani',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011039',
                'name_ar' => 'المزرعة',
                'name_en' => 'Al Mazra\'a',
                'name_tr' => 'Al Mazra\'a',
                'match_names' => [
                    'المزرعة',
                    'Al Mazra\'a',
                ],
                'districts' => [
                    [
                        'code' => 'C10011039-MAIN',
                        'name_ar' => 'المزرعة',
                        'name_en' => 'Al Mazra\'a',
                        'name_tr' => 'Al Mazra\'a',
                        'match_names' => [
                        'المزرعة',
                        'Al Mazra\'a',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011040',
                'name_ar' => 'الفيحاء',
                'name_en' => 'Al Fayha',
                'name_tr' => 'Al Fayha',
                'match_names' => [
                    'الفيحاء',
                    'Al Fayha',
                ],
                'districts' => [
                    [
                        'code' => 'C10011040-MAIN',
                        'name_ar' => 'الفيحاء',
                        'name_en' => 'Al Fayha',
                        'name_tr' => 'Al Fayha',
                        'match_names' => [
                        'الفيحاء',
                        'Al Fayha',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011041',
                'name_ar' => 'الميسات',
                'name_en' => 'Al Maysat',
                'name_tr' => 'Al Maysat',
                'match_names' => [
                    'الميسات',
                    'Al Maysat',
                ],
                'districts' => [
                    [
                        'code' => 'C10011041-MAIN',
                        'name_ar' => 'الميسات',
                        'name_en' => 'Al Maysat',
                        'name_tr' => 'Al Maysat',
                        'match_names' => [
                        'الميسات',
                        'Al Maysat',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011042',
                'name_ar' => 'عرنوس',
                'name_en' => 'A\'arnous',
                'name_tr' => 'A\'arnous',
                'match_names' => [
                    'عرنوس',
                    'A\'arnous',
                ],
                'districts' => [
                    [
                        'code' => 'C10011042-MAIN',
                        'name_ar' => 'عرنوس',
                        'name_en' => 'A\'arnous',
                        'name_tr' => 'A\'arnous',
                        'match_names' => [
                        'عرنوس',
                        'A\'arnous',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011043',
                'name_ar' => 'السلحية',
                'name_en' => 'As Salhieh',
                'name_tr' => 'As Salhieh',
                'match_names' => [
                    'السلحية',
                    'As Salhieh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011043-MAIN',
                        'name_ar' => 'السلحية',
                        'name_en' => 'As Salhieh',
                        'name_tr' => 'As Salhieh',
                        'match_names' => [
                        'السلحية',
                        'As Salhieh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011044',
                'name_ar' => 'صروجة',
                'name_en' => 'Saroujah',
                'name_tr' => 'Saroujah',
                'match_names' => [
                    'صروجة',
                    'Saroujah',
                ],
                'districts' => [
                    [
                        'code' => 'C10011044-MAIN',
                        'name_ar' => 'صروجة',
                        'name_en' => 'Saroujah',
                        'name_tr' => 'Saroujah',
                        'match_names' => [
                        'صروجة',
                        'Saroujah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011045',
                'name_ar' => 'الروضة',
                'name_en' => 'Ar Rawda',
                'name_tr' => 'Ar Rawda',
                'match_names' => [
                    'الروضة',
                    'Ar Rawda',
                ],
                'districts' => [
                    [
                        'code' => 'C10011045-MAIN',
                        'name_ar' => 'الروضة',
                        'name_en' => 'Ar Rawda',
                        'name_tr' => 'Ar Rawda',
                        'match_names' => [
                        'الروضة',
                        'Ar Rawda',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011046',
                'name_ar' => 'أبو رمانة',
                'name_en' => 'Abou Rummaneh',
                'name_tr' => 'Abou Rummaneh',
                'match_names' => [
                    'أبو رمانة',
                    'Abou Rummaneh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011046-MAIN',
                        'name_ar' => 'أبو رمانة',
                        'name_en' => 'Abou Rummaneh',
                        'name_tr' => 'Abou Rummaneh',
                        'match_names' => [
                        'أبو رمانة',
                        'Abou Rummaneh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011047',
                'name_ar' => 'المالكي',
                'name_en' => 'Al Maliki',
                'name_tr' => 'Al Maliki',
                'match_names' => [
                    'المالكي',
                    'Al Maliki',
                ],
                'districts' => [
                    [
                        'code' => 'C10011047-MAIN',
                        'name_ar' => 'المالكي',
                        'name_en' => 'Al Maliki',
                        'name_tr' => 'Al Maliki',
                        'match_names' => [
                        'المالكي',
                        'Al Maliki',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011048',
                'name_ar' => 'البرامكة',
                'name_en' => 'Al Baramkeh',
                'name_tr' => 'Al Baramkeh',
                'match_names' => [
                    'البرامكة',
                    'Al Baramkeh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011048-MAIN',
                        'name_ar' => 'البرامكة',
                        'name_en' => 'Al Baramkeh',
                        'name_tr' => 'Al Baramkeh',
                        'match_names' => [
                        'البرامكة',
                        'Al Baramkeh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011049',
                'name_ar' => 'الحلبوني',
                'name_en' => 'Al Halbuneh',
                'name_tr' => 'Al Halbuneh',
                'match_names' => [
                    'الحلبوني',
                    'Al Halbuneh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011049-MAIN',
                        'name_ar' => 'الحلبوني',
                        'name_en' => 'Al Halbuneh',
                        'name_tr' => 'Al Halbuneh',
                        'match_names' => [
                        'الحلبوني',
                        'Al Halbuneh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011050',
                'name_ar' => 'المرجة',
                'name_en' => 'El Marjeh',
                'name_tr' => 'El Marjeh',
                'match_names' => [
                    'المرجة',
                    'El Marjeh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011050-MAIN',
                        'name_ar' => 'المرجة',
                        'name_en' => 'El Marjeh',
                        'name_tr' => 'El Marjeh',
                        'match_names' => [
                        'المرجة',
                        'El Marjeh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011051',
                'name_ar' => 'الشعلان',
                'name_en' => 'Al Shaalan',
                'name_tr' => 'Al Shaalan',
                'match_names' => [
                    'الشعلان',
                    'Al Shaalan',
                ],
                'districts' => [
                    [
                        'code' => 'C10011051-MAIN',
                        'name_ar' => 'الشعلان',
                        'name_en' => 'Al Shaalan',
                        'name_tr' => 'Al Shaalan',
                        'match_names' => [
                        'الشعلان',
                        'Al Shaalan',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011052',
                'name_ar' => 'جسر الأبيض',
                'name_en' => 'Jisr El  Abyad',
                'name_tr' => 'Jisr El  Abyad',
                'match_names' => [
                    'جسر الأبيض',
                    'Jisr El  Abyad',
                ],
                'districts' => [
                    [
                        'code' => 'C10011052-MAIN',
                        'name_ar' => 'جسر الأبيض',
                        'name_en' => 'Jisr El  Abyad',
                        'name_tr' => 'Jisr El  Abyad',
                        'match_names' => [
                        'جسر الأبيض',
                        'Jisr El  Abyad',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011053',
                'name_ar' => 'أبو جرش',
                'name_en' => 'Abou Jarash',
                'name_tr' => 'Abou Jarash',
                'match_names' => [
                    'أبو جرش',
                    'Abou Jarash',
                ],
                'districts' => [
                    [
                        'code' => 'C10011053-MAIN',
                        'name_ar' => 'أبو جرش',
                        'name_en' => 'Abou Jarash',
                        'name_tr' => 'Abou Jarash',
                        'match_names' => [
                        'أبو جرش',
                        'Abou Jarash',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011054',
                'name_ar' => 'الشاغور',
                'name_en' => 'Al Shaghour',
                'name_tr' => 'Al Shaghour',
                'match_names' => [
                    'الشاغور',
                    'Al Shaghour',
                ],
                'districts' => [
                    [
                        'code' => 'C10011054-MAIN',
                        'name_ar' => 'الشاغور',
                        'name_en' => 'Al Shaghour',
                        'name_tr' => 'Al Shaghour',
                        'match_names' => [
                        'الشاغور',
                        'Al Shaghour',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011055',
                'name_ar' => 'القنوات',
                'name_en' => 'Al Qanawat',
                'name_tr' => 'Al Qanawat',
                'match_names' => [
                    'القنوات',
                    'Al Qanawat',
                ],
                'districts' => [
                    [
                        'code' => 'C10011055-MAIN',
                        'name_ar' => 'القنوات',
                        'name_en' => 'Al Qanawat',
                        'name_tr' => 'Al Qanawat',
                        'match_names' => [
                        'القنوات',
                        'Al Qanawat',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011056',
                'name_ar' => 'المجتهد',
                'name_en' => 'Al Moujtahed',
                'name_tr' => 'Al Moujtahed',
                'match_names' => [
                    'المجتهد',
                    'Al Moujtahed',
                ],
                'districts' => [
                    [
                        'code' => 'C10011056-MAIN',
                        'name_ar' => 'المجتهد',
                        'name_en' => 'Al Moujtahed',
                        'name_tr' => 'Al Moujtahed',
                        'match_names' => [
                        'المجتهد',
                        'Al Moujtahed',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011057',
                'name_ar' => 'الفحامة',
                'name_en' => 'Al Fakhama',
                'name_tr' => 'Al Fakhama',
                'match_names' => [
                    'الفحامة',
                    'Al Fakhama',
                ],
                'districts' => [
                    [
                        'code' => 'C10011057-MAIN',
                        'name_ar' => 'الفحامة',
                        'name_en' => 'Al Fakhama',
                        'name_tr' => 'Al Fakhama',
                        'match_names' => [
                        'الفحامة',
                        'Al Fakhama',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011058',
                'name_ar' => 'الزهرة',
                'name_en' => 'Al Zahira',
                'name_tr' => 'Al Zahira',
                'match_names' => [
                    'الزهرة',
                    'Al Zahira',
                ],
                'districts' => [
                    [
                        'code' => 'C10011058-MAIN',
                        'name_ar' => 'الزهرة',
                        'name_en' => 'Al Zahira',
                        'name_tr' => 'Al Zahira',
                        'match_names' => [
                        'الزهرة',
                        'Al Zahira',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011059',
                'name_ar' => 'الميدان',
                'name_en' => 'Al Midan',
                'name_tr' => 'Al Midan',
                'match_names' => [
                    'الميدان',
                    'Al Midan',
                ],
                'districts' => [
                    [
                        'code' => 'C10011059-MAIN',
                        'name_ar' => 'الميدان',
                        'name_en' => 'Al Midan',
                        'name_tr' => 'Al Midan',
                        'match_names' => [
                        'الميدان',
                        'Al Midan',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011060',
                'name_ar' => 'حديقة تشرين',
                'name_en' => 'Tishreen Garden',
                'name_tr' => 'Tishreen Garden',
                'match_names' => [
                    'حديقة تشرين',
                    'Tishreen Garden',
                ],
                'districts' => [
                    [
                        'code' => 'C10011060-MAIN',
                        'name_ar' => 'حديقة تشرين',
                        'name_en' => 'Tishreen Garden',
                        'name_tr' => 'Tishreen Garden',
                        'match_names' => [
                        'حديقة تشرين',
                        'Tishreen Garden',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011061',
                'name_ar' => 'الرابية',
                'name_en' => 'Al Rabweh',
                'name_tr' => 'Al Rabweh',
                'match_names' => [
                    'الرابية',
                    'Al Rabweh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011061-MAIN',
                        'name_ar' => 'الرابية',
                        'name_en' => 'Al Rabweh',
                        'name_tr' => 'Al Rabweh',
                        'match_names' => [
                        'الرابية',
                        'Al Rabweh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011062',
                'name_ar' => 'وادي الرازي',
                'name_en' => 'Wadi El rez',
                'name_tr' => 'Wadi El rez',
                'match_names' => [
                    'وادي الرازي',
                    'Wadi El rez',
                ],
                'districts' => [
                    [
                        'code' => 'C10011062-MAIN',
                        'name_ar' => 'وادي الرازي',
                        'name_en' => 'Wadi El rez',
                        'name_tr' => 'Wadi El rez',
                        'match_names' => [
                        'وادي الرازي',
                        'Wadi El rez',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011063',
                'name_ar' => 'دمر البلد',
                'name_en' => 'Dummar El Balad',
                'name_tr' => 'Dummar El Balad',
                'match_names' => [
                    'دمر البلد',
                    'Dummar El Balad',
                ],
                'districts' => [
                    [
                        'code' => 'C10011063-MAIN',
                        'name_ar' => 'دمر البلد',
                        'name_en' => 'Dummar El Balad',
                        'name_tr' => 'Dummar El Balad',
                        'match_names' => [
                        'دمر البلد',
                        'Dummar El Balad',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011064',
                'name_ar' => 'قاسيون',
                'name_en' => 'Quasioun',
                'name_tr' => 'Quasioun',
                'match_names' => [
                    'قاسيون',
                    'Quasioun',
                ],
                'districts' => [
                    [
                        'code' => 'C10011064-MAIN',
                        'name_ar' => 'قاسيون',
                        'name_en' => 'Quasioun',
                        'name_tr' => 'Quasioun',
                        'match_names' => [
                        'قاسيون',
                        'Quasioun',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011065',
                'name_ar' => 'مشروع دمر',
                'name_en' => 'Mashrou3 Dummar',
                'name_tr' => 'Mashrou3 Dummar',
                'match_names' => [
                    'مشروع دمر',
                    'Mashrou3 Dummar',
                ],
                'districts' => [
                    [
                        'code' => 'C10011065-MAIN',
                        'name_ar' => 'مشروع دمر',
                        'name_en' => 'Mashrou3 Dummar',
                        'name_tr' => 'Mashrou3 Dummar',
                        'match_names' => [
                        'مشروع دمر',
                        'Mashrou3 Dummar',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011066',
                'name_ar' => 'القصر الجمهوري',
                'name_en' => 'Presidential Palace',
                'name_tr' => 'Presidential Palace',
                'match_names' => [
                    'القصر الجمهوري',
                    'Presidential Palace',
                ],
                'districts' => [
                    [
                        'code' => 'C10011066-MAIN',
                        'name_ar' => 'القصر الجمهوري',
                        'name_en' => 'Presidential Palace',
                        'name_tr' => 'Presidential Palace',
                        'match_names' => [
                        'القصر الجمهوري',
                        'Presidential Palace',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011067',
                'name_ar' => 'غير معروف',
                'name_en' => 'Unknown',
                'name_tr' => 'Unknown',
                'match_names' => [
                    'غير معروف',
                    'Unknown',
                ],
                'districts' => [
                    [
                        'code' => 'C10011067-MAIN',
                        'name_ar' => 'غير معروف',
                        'name_en' => 'Unknown',
                        'name_tr' => 'Unknown',
                        'match_names' => [
                        'غير معروف',
                        'Unknown',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011068',
                'name_ar' => 'القدم',
                'name_en' => 'Al Qadam',
                'name_tr' => 'Al Qadam',
                'match_names' => [
                    'القدم',
                    'Al Qadam',
                ],
                'districts' => [
                    [
                        'code' => 'C10011068-MAIN',
                        'name_ar' => 'القدم',
                        'name_en' => 'Al Qadam',
                        'name_tr' => 'Al Qadam',
                        'match_names' => [
                        'القدم',
                        'Al Qadam',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011069',
                'name_ar' => 'نهر عائشة',
                'name_en' => 'Nahr \'Aisha',
                'name_tr' => 'Nahr \'Aisha',
                'match_names' => [
                    'نهر عائشة',
                    'Nahr \'Aisha',
                ],
                'districts' => [
                    [
                        'code' => 'C10011069-MAIN',
                        'name_ar' => 'نهر عائشة',
                        'name_en' => 'Nahr \'Aisha',
                        'name_tr' => 'Nahr \'Aisha',
                        'match_names' => [
                        'نهر عائشة',
                        'Nahr \'Aisha',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011070',
                'name_ar' => 'مطار المزة',
                'name_en' => 'Mazzeh Airport',
                'name_tr' => 'Mazzeh Airport',
                'match_names' => [
                    'مطار المزة',
                    'Mazzeh Airport',
                ],
                'districts' => [
                    [
                        'code' => 'C10011070-MAIN',
                        'name_ar' => 'مطار المزة',
                        'name_en' => 'Mazzeh Airport',
                        'name_tr' => 'Mazzeh Airport',
                        'match_names' => [
                        'مطار المزة',
                        'Mazzeh Airport',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011071',
                'name_ar' => 'المزة',
                'name_en' => 'Mazzeh',
                'name_tr' => 'Mazzeh',
                'match_names' => [
                    'المزة',
                    'Mazzeh',
                ],
                'districts' => [
                    [
                        'code' => 'C10011071-MAIN',
                        'name_ar' => 'المزة',
                        'name_en' => 'Mazzeh',
                        'name_tr' => 'Mazzeh',
                        'match_names' => [
                        'المزة',
                        'Mazzeh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011072',
                'name_ar' => 'القزاز',
                'name_en' => 'Al Qazzaz',
                'name_tr' => 'Al Qazzaz',
                'match_names' => [
                    'القزاز',
                    'Al Qazzaz',
                ],
                'districts' => [
                    [
                        'code' => 'C10011072-MAIN',
                        'name_ar' => 'القزاز',
                        'name_en' => 'Al Qazzaz',
                        'name_tr' => 'Al Qazzaz',
                        'match_names' => [
                        'القزاز',
                        'Al Qazzaz',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'C10011073',
                'name_ar' => 'باب مصلى',
                'name_en' => 'Bab Musalla',
                'name_tr' => 'Bab Musalla',
                'match_names' => [
                    'باب مصلى',
                    'Bab Musalla',
                ],
                'districts' => [
                    [
                        'code' => 'C10011073-MAIN',
                        'name_ar' => 'باب مصلى',
                        'name_en' => 'Bab Musalla',
                        'name_tr' => 'Bab Musalla',
                        'match_names' => [
                        'باب مصلى',
                        'Bab Musalla',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY02',
        'name_ar' => 'حلب',
        'name_en' => 'Aleppo',
        'name_tr' => 'Halep ili',
        'match_names' => [
            'حلب',
            'Aleppo',
        ],
        'cities' => [
            [
                'code' => 'SY0204',
                'name_ar' => 'اعزاز',
                'name_en' => 'A\'zaz',
                'name_tr' => 'A\'zaz',
                'match_names' => [
                    'اعزاز',
                    'A\'zaz',
                    'Azaz',
                ],
                'districts' => [
                    [
                        'code' => 'SY020401',
                        'name_ar' => 'اخترين',
                        'name_en' => 'Aghtrin',
                        'name_tr' => 'Aghtrin',
                        'match_names' => [
                        'اخترين',
                        'Aghtrin',
                        ],
                    ],
                    [
                        'code' => 'SY020402',
                        'name_ar' => 'تل رفعت',
                        'name_en' => 'Tall Refaat',
                        'name_tr' => 'Tall Refaat',
                        'match_names' => [
                        'تل رفعت',
                        'Tall Refaat',
                        ],
                    ],
                    [
                        'code' => 'SY020405',
                        'name_ar' => 'صوران',
                        'name_en' => 'Suran',
                        'name_tr' => 'Suran',
                        'match_names' => [
                        'صوران',
                        'Suran',
                        ],
                    ],
                    [
                        'code' => 'SY020403',
                        'name_ar' => 'مارع',
                        'name_en' => 'Mare\'',
                        'name_tr' => 'Mare\'',
                        'match_names' => [
                        'مارع',
                        'Mare\'',
                        'Mare',
                        ],
                    ],
                    [
                        'code' => 'SY020400',
                        'name_ar' => 'مركز اعزاز',
                        'name_en' => 'A\'zaz',
                        'name_tr' => 'A\'zaz',
                        'match_names' => [
                        'مركز اعزاز',
                        'A\'zaz',
                        'Azaz',
                        ],
                    ],
                    [
                        'code' => 'SY020404',
                        'name_ar' => 'نبل',
                        'name_en' => 'Nabul',
                        'name_tr' => 'Nabul',
                        'match_names' => [
                        'نبل',
                        'Nabul',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0202',
                'name_ar' => 'الباب',
                'name_en' => 'Al Bab',
                'name_tr' => 'Al Bab',
                'match_names' => [
                    'الباب',
                    'Al Bab',
                ],
                'districts' => [
                    [
                        'code' => 'SY020203',
                        'name_ar' => 'الراعي',
                        'name_en' => 'Ar-Ra\'ee',
                        'name_tr' => 'Ar-Ra\'ee',
                        'match_names' => [
                        'الراعي',
                        'Ar-Ra\'ee',
                        'Ar-Raee',
                        ],
                    ],
                    [
                        'code' => 'SY020201',
                        'name_ar' => 'تادف',
                        'name_en' => 'Tadaf',
                        'name_tr' => 'Tadaf',
                        'match_names' => [
                        'تادف',
                        'Tadaf',
                        ],
                    ],
                    [
                        'code' => 'SY020202',
                        'name_ar' => 'دير حافر',
                        'name_en' => 'Dayr Hafir',
                        'name_tr' => 'Dayr Hafir',
                        'match_names' => [
                        'دير حافر',
                        'Dayr Hafir',
                        ],
                    ],
                    [
                        'code' => 'SY020205',
                        'name_ar' => 'رسم حرمل الامام',
                        'name_en' => 'Rasm Haram El-Imam',
                        'name_tr' => 'Rasm Haram El-Imam',
                        'match_names' => [
                        'رسم حرمل الامام',
                        'Rasm Haram El-Imam',
                        ],
                    ],
                    [
                        'code' => 'SY020206',
                        'name_ar' => 'عريمة',
                        'name_en' => 'A\'rima',
                        'name_tr' => 'A\'rima',
                        'match_names' => [
                        'عريمة',
                        'A\'rima',
                        'Arima',
                        ],
                    ],
                    [
                        'code' => 'SY020204',
                        'name_ar' => 'كويرس شرقي',
                        'name_en' => 'Eastern Kwaires',
                        'name_tr' => 'Eastern Kwaires',
                        'match_names' => [
                        'كويرس شرقي',
                        'Eastern Kwaires',
                        ],
                    ],
                    [
                        'code' => 'SY020200',
                        'name_ar' => 'مركز الباب',
                        'name_en' => 'Al Bab',
                        'name_tr' => 'Al Bab',
                        'match_names' => [
                        'مركز الباب',
                        'Al Bab',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0207',
                'name_ar' => 'السفيرة',
                'name_en' => 'As-Safira',
                'name_tr' => 'As-Safira',
                'match_names' => [
                    'السفيرة',
                    'As-Safira',
                ],
                'districts' => [
                    [
                        'code' => 'SY020703',
                        'name_ar' => 'الحاجب',
                        'name_en' => 'Hajeb',
                        'name_tr' => 'Hajeb',
                        'match_names' => [
                        'الحاجب',
                        'Hajeb',
                        ],
                    ],
                    [
                        'code' => 'SY020702',
                        'name_ar' => 'بنان',
                        'name_en' => 'Banan',
                        'name_tr' => 'Banan',
                        'match_names' => [
                        'بنان',
                        'Banan',
                        ],
                    ],
                    [
                        'code' => 'SY020701',
                        'name_ar' => 'خناصر',
                        'name_en' => 'Khanaser',
                        'name_tr' => 'Khanaser',
                        'match_names' => [
                        'خناصر',
                        'Khanaser',
                        ],
                    ],
                    [
                        'code' => 'SY020700',
                        'name_ar' => 'مركز السفيرة',
                        'name_en' => 'As-Safira',
                        'name_tr' => 'As-Safira',
                        'match_names' => [
                        'مركز السفيرة',
                        'As-Safira',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0200',
                'name_ar' => 'جبل سمعان',
                'name_en' => 'Jebel Saman',
                'name_tr' => 'Jebel Saman',
                'match_names' => [
                    'جبل سمعان',
                    'Jebel Saman',
                ],
                'districts' => [
                    [
                        'code' => 'SY020001',
                        'name_ar' => 'أتارب',
                        'name_en' => 'Atareb',
                        'name_tr' => 'Atareb',
                        'match_names' => [
                        'أتارب',
                        'Atareb',
                        ],
                    ],
                    [
                        'code' => 'SY020006',
                        'name_ar' => 'الحاضر',
                        'name_en' => 'Hadher',
                        'name_tr' => 'Hadher',
                        'match_names' => [
                        'الحاضر',
                        'Hadher',
                        ],
                    ],
                    [
                        'code' => 'SY020005',
                        'name_ar' => 'الزربة',
                        'name_en' => 'Zarbah',
                        'name_tr' => 'Zarbah',
                        'match_names' => [
                        'الزربة',
                        'Zarbah',
                        ],
                    ],
                    [
                        'code' => 'SY020002',
                        'name_ar' => 'تل الضمان',
                        'name_en' => 'Tall Ed-daman',
                        'name_tr' => 'Tall Ed-daman',
                        'match_names' => [
                        'تل الضمان',
                        'Tall Ed-daman',
                        ],
                    ],
                    [
                        'code' => 'SY020003',
                        'name_ar' => 'حريتان',
                        'name_en' => 'Haritan',
                        'name_tr' => 'Haritan',
                        'match_names' => [
                        'حريتان',
                        'Haritan',
                        ],
                    ],
                    [
                        'code' => 'SY020004',
                        'name_ar' => 'دارة عزة',
                        'name_en' => 'Daret Azza',
                        'name_tr' => 'Daret Azza',
                        'match_names' => [
                        'دارة عزة',
                        'Daret Azza',
                        ],
                    ],
                    [
                        'code' => 'SY020000',
                        'name_ar' => 'مركز جبل سمعان',
                        'name_en' => 'Jebel Saman',
                        'name_tr' => 'Jebel Saman',
                        'match_names' => [
                        'مركز جبل سمعان',
                        'Jebel Saman',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0208',
                'name_ar' => 'جرابلس',
                'name_en' => 'Jarablus',
                'name_tr' => 'Jarablus',
                'match_names' => [
                    'جرابلس',
                    'Jarablus',
                ],
                'districts' => [
                    [
                        'code' => 'SY020801',
                        'name_ar' => 'غندورة',
                        'name_en' => 'Ghandorah',
                        'name_tr' => 'Ghandorah',
                        'match_names' => [
                        'غندورة',
                        'Ghandorah',
                        ],
                    ],
                    [
                        'code' => 'SY020800',
                        'name_ar' => 'مركز جرابلس',
                        'name_en' => 'Jarablus',
                        'name_tr' => 'Jarablus',
                        'match_names' => [
                        'مركز جرابلس',
                        'Jarablus',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0203',
                'name_ar' => 'عفرين',
                'name_en' => 'Afrin',
                'name_tr' => 'Afrin',
                'match_names' => [
                    'عفرين',
                    'Afrin',
                ],
                'districts' => [
                    [
                        'code' => 'SY020301',
                        'name_ar' => 'بلبل',
                        'name_en' => 'Bulbul',
                        'name_tr' => 'Bulbul',
                        'match_names' => [
                        'بلبل',
                        'Bulbul',
                        ],
                    ],
                    [
                        'code' => 'SY020302',
                        'name_ar' => 'جنديرس',
                        'name_en' => 'Jandairis',
                        'name_tr' => 'Jandairis',
                        'match_names' => [
                        'جنديرس',
                        'Jandairis',
                        ],
                    ],
                    [
                        'code' => 'SY020303',
                        'name_ar' => 'راجو',
                        'name_en' => 'Raju',
                        'name_tr' => 'Raju',
                        'match_names' => [
                        'راجو',
                        'Raju',
                        ],
                    ],
                    [
                        'code' => 'SY020304',
                        'name_ar' => 'شران',
                        'name_en' => 'Sharan',
                        'name_tr' => 'Sharan',
                        'match_names' => [
                        'شران',
                        'Sharan',
                        ],
                    ],
                    [
                        'code' => 'SY020305',
                        'name_ar' => 'شيخ الحديد',
                        'name_en' => 'Sheikh El-Hadid',
                        'name_tr' => 'Sheikh El-Hadid',
                        'match_names' => [
                        'شيخ الحديد',
                        'Sheikh El-Hadid',
                        ],
                    ],
                    [
                        'code' => 'SY020300',
                        'name_ar' => 'مركز عفرين',
                        'name_en' => 'Afrin',
                        'name_tr' => 'Afrin',
                        'match_names' => [
                        'مركز عفرين',
                        'Afrin',
                        ],
                    ],
                    [
                        'code' => 'SY020306',
                        'name_ar' => 'معبطلي',
                        'name_en' => 'Ma\'btali',
                        'name_tr' => 'Ma\'btali',
                        'match_names' => [
                        'معبطلي',
                        'Ma\'btali',
                        'Mabtali',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0206',
                'name_ar' => 'عين العرب',
                'name_en' => 'Ain Al Arab',
                'name_tr' => 'Ain Al Arab',
                'match_names' => [
                    'عين العرب',
                    'Ain Al Arab',
                ],
                'districts' => [
                    [
                        'code' => 'SY020601',
                        'name_ar' => 'شيوخ تحتاني',
                        'name_en' => 'Lower Shyookh',
                        'name_tr' => 'Lower Shyookh',
                        'match_names' => [
                        'شيوخ تحتاني',
                        'Lower Shyookh',
                        ],
                    ],
                    [
                        'code' => 'SY020602',
                        'name_ar' => 'صرين',
                        'name_en' => 'Sarin',
                        'name_tr' => 'Sarin',
                        'match_names' => [
                        'صرين',
                        'Sarin',
                        ],
                    ],
                    [
                        'code' => 'SY020600',
                        'name_ar' => 'مركز عين العرب',
                        'name_en' => 'Ain al Arab',
                        'name_tr' => 'Ain al Arab',
                        'match_names' => [
                        'مركز عين العرب',
                        'Ain al Arab',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0205',
                'name_ar' => 'منبج',
                'name_en' => 'Menbij',
                'name_tr' => 'Menbij',
                'match_names' => [
                    'منبج',
                    'Menbij',
                ],
                'districts' => [
                    [
                        'code' => 'SY020501',
                        'name_ar' => 'أبو قلقل',
                        'name_en' => 'Abu Qalqal',
                        'name_tr' => 'Abu Qalqal',
                        'match_names' => [
                        'أبو قلقل',
                        'Abu Qalqal',
                        ],
                    ],
                    [
                        'code' => 'SY020502',
                        'name_ar' => 'الخفسة',
                        'name_en' => 'Al-Khafsa',
                        'name_tr' => 'Al-Khafsa',
                        'match_names' => [
                        'الخفسة',
                        'Al-Khafsa',
                        ],
                    ],
                    [
                        'code' => 'SY020500',
                        'name_ar' => 'مركز منبج',
                        'name_en' => 'Menbij',
                        'name_tr' => 'Menbij',
                        'match_names' => [
                        'مركز منبج',
                        'Menbij',
                        ],
                    ],
                    [
                        'code' => 'SY020503',
                        'name_ar' => 'مسكنة',
                        'name_en' => 'Maskana',
                        'name_tr' => 'Maskana',
                        'match_names' => [
                        'مسكنة',
                        'Maskana',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY03',
        'name_ar' => 'ريف دمشق',
        'name_en' => 'Rural Damascus',
        'name_tr' => 'Şam kırsalı',
        'match_names' => [
            'ريف دمشق',
            'Rural Damascus',
        ],
        'cities' => [
            [
                'code' => 'SY0304',
                'name_ar' => 'التل',
                'name_en' => 'At Tall',
                'name_tr' => 'At Tall',
                'match_names' => [
                    'التل',
                    'At Tall',
                ],
                'districts' => [
                    [
                        'code' => 'SY030402',
                        'name_ar' => 'رنكوس',
                        'name_en' => 'Rankus',
                        'name_tr' => 'Rankus',
                        'match_names' => [
                        'رنكوس',
                        'Rankus',
                        ],
                    ],
                    [
                        'code' => 'SY030401',
                        'name_ar' => 'صيدنايا',
                        'name_en' => 'Sidnaya',
                        'name_tr' => 'Sidnaya',
                        'match_names' => [
                        'صيدنايا',
                        'Sidnaya',
                        ],
                    ],
                    [
                        'code' => 'SY030400',
                        'name_ar' => 'مركز التل',
                        'name_en' => 'At Tall',
                        'name_tr' => 'At Tall',
                        'match_names' => [
                        'مركز التل',
                        'At Tall',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0307',
                'name_ar' => 'الزبداني',
                'name_en' => 'Az-Zabdani',
                'name_tr' => 'Az-Zabdani',
                'match_names' => [
                    'الزبداني',
                    'Az-Zabdani',
                ],
                'districts' => [
                    [
                        'code' => 'SY030701',
                        'name_ar' => 'الديماس',
                        'name_en' => 'Dimas',
                        'name_tr' => 'Dimas',
                        'match_names' => [
                        'الديماس',
                        'Dimas',
                        ],
                    ],
                    [
                        'code' => 'SY030704',
                        'name_ar' => 'سرغايا',
                        'name_en' => 'Sarghaya',
                        'name_tr' => 'Sarghaya',
                        'match_names' => [
                        'سرغايا',
                        'Sarghaya',
                        ],
                    ],
                    [
                        'code' => 'SY030702',
                        'name_ar' => 'عين الفيجة',
                        'name_en' => 'Ein Elfijeh',
                        'name_tr' => 'Ein Elfijeh',
                        'match_names' => [
                        'عين الفيجة',
                        'Ein Elfijeh',
                        ],
                    ],
                    [
                        'code' => 'SY030700',
                        'name_ar' => 'مركز الزبداني',
                        'name_en' => 'Az-Zabdani',
                        'name_tr' => 'Az-Zabdani',
                        'match_names' => [
                        'مركز الزبداني',
                        'Az-Zabdani',
                        ],
                    ],
                    [
                        'code' => 'SY030703',
                        'name_ar' => 'مضايا',
                        'name_en' => 'Madaya',
                        'name_tr' => 'Madaya',
                        'match_names' => [
                        'مضايا',
                        'Madaya',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0303',
                'name_ar' => 'القطيفة',
                'name_en' => 'Al Qutayfah',
                'name_tr' => 'Al Qutayfah',
                'match_names' => [
                    'القطيفة',
                    'Al Qutayfah',
                ],
                'districts' => [
                    [
                        'code' => 'SY030303',
                        'name_ar' => 'الرحيبة',
                        'name_en' => 'Raheiba',
                        'name_tr' => 'Raheiba',
                        'match_names' => [
                        'الرحيبة',
                        'Raheiba',
                        ],
                    ],
                    [
                        'code' => 'SY030301',
                        'name_ar' => 'جيرود',
                        'name_en' => 'Jirud',
                        'name_tr' => 'Jirud',
                        'match_names' => [
                        'جيرود',
                        'Jirud',
                        ],
                    ],
                    [
                        'code' => 'SY030300',
                        'name_ar' => 'مركز القطيفة',
                        'name_en' => 'Al Qutayfah',
                        'name_tr' => 'Al Qutayfah',
                        'match_names' => [
                        'مركز القطيفة',
                        'Al Qutayfah',
                        ],
                    ],
                    [
                        'code' => 'SY030302',
                        'name_ar' => 'معلولا',
                        'name_en' => 'Ma\'loula',
                        'name_tr' => 'Ma\'loula',
                        'match_names' => [
                        'معلولا',
                        'Ma\'loula',
                        'Maloula',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0306',
                'name_ar' => 'النبك',
                'name_en' => 'An Nabk',
                'name_tr' => 'An Nabk',
                'match_names' => [
                    'النبك',
                    'An Nabk',
                ],
                'districts' => [
                    [
                        'code' => 'SY030601',
                        'name_ar' => 'دير عطية',
                        'name_en' => 'Deir Attiyeh',
                        'name_tr' => 'Deir Attiyeh',
                        'match_names' => [
                        'دير عطية',
                        'Deir Attiyeh',
                        ],
                    ],
                    [
                        'code' => 'SY030600',
                        'name_ar' => 'مركز النبك',
                        'name_en' => 'An Nabk',
                        'name_tr' => 'An Nabk',
                        'match_names' => [
                        'مركز النبك',
                        'An Nabk',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0309',
                'name_ar' => 'داريا',
                'name_en' => 'Darayya',
                'name_tr' => 'Darayya',
                'match_names' => [
                    'داريا',
                    'Darayya',
                ],
                'districts' => [
                    [
                        'code' => 'SY030902',
                        'name_ar' => 'الحجر الأسود',
                        'name_en' => 'Hajar Aswad',
                        'name_tr' => 'Hajar Aswad',
                        'match_names' => [
                        'الحجر الأسود',
                        'Hajar Aswad',
                        ],
                    ],
                    [
                        'code' => 'SY030901',
                        'name_ar' => 'صحنايا',
                        'name_en' => 'Sahnaya',
                        'name_tr' => 'Sahnaya',
                        'match_names' => [
                        'صحنايا',
                        'Sahnaya',
                        ],
                    ],
                    [
                        'code' => 'SY030900',
                        'name_ar' => 'مركز داريا',
                        'name_en' => 'Markaz Darayya',
                        'name_tr' => 'Markaz Darayya',
                        'match_names' => [
                        'مركز داريا',
                        'Markaz Darayya',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0302',
                'name_ar' => 'دوما',
                'name_en' => 'Duma',
                'name_tr' => 'Duma',
                'match_names' => [
                    'دوما',
                    'Duma',
                ],
                'districts' => [
                    [
                        'code' => 'SY030202',
                        'name_ar' => 'السبع بيار',
                        'name_en' => 'Sabe Byar',
                        'name_tr' => 'Sabe Byar',
                        'match_names' => [
                        'السبع بيار',
                        'Sabe Byar',
                        ],
                    ],
                    [
                        'code' => 'SY030203',
                        'name_ar' => 'الضمير',
                        'name_en' => 'Dhameer',
                        'name_tr' => 'Dhameer',
                        'match_names' => [
                        'الضمير',
                        'Dhameer',
                        ],
                    ],
                    [
                        'code' => 'SY030205',
                        'name_ar' => 'الغزلانية',
                        'name_en' => 'Ghizlaniyyeh',
                        'name_tr' => 'Ghizlaniyyeh',
                        'match_names' => [
                        'الغزلانية',
                        'Ghizlaniyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY030204',
                        'name_ar' => 'النشابية',
                        'name_en' => 'Nashabiyeh',
                        'name_tr' => 'Nashabiyeh',
                        'match_names' => [
                        'النشابية',
                        'Nashabiyeh',
                        ],
                    ],
                    [
                        'code' => 'SY030206',
                        'name_ar' => 'حران العواميد',
                        'name_en' => 'Haran Al\'awameed',
                        'name_tr' => 'Haran Al\'awameed',
                        'match_names' => [
                        'حران العواميد',
                        'Haran Al\'awameed',
                        'Haran Alawameed',
                        ],
                    ],
                    [
                        'code' => 'SY030201',
                        'name_ar' => 'حرستا',
                        'name_en' => 'Harasta',
                        'name_tr' => 'Harasta',
                        'match_names' => [
                        'حرستا',
                        'Harasta',
                        ],
                    ],
                    [
                        'code' => 'SY030200',
                        'name_ar' => 'مركز دوما',
                        'name_en' => 'Duma',
                        'name_tr' => 'Duma',
                        'match_names' => [
                        'مركز دوما',
                        'Duma',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0308',
                'name_ar' => 'قطنا',
                'name_en' => 'Qatana',
                'name_tr' => 'Qatana',
                'match_names' => [
                    'قطنا',
                    'Qatana',
                ],
                'districts' => [
                    [
                        'code' => 'SY030801',
                        'name_ar' => 'بيت جن',
                        'name_en' => 'Bait Jan',
                        'name_tr' => 'Bait Jan',
                        'match_names' => [
                        'بيت جن',
                        'Bait Jan',
                        ],
                    ],
                    [
                        'code' => 'SY030802',
                        'name_ar' => 'سعسع',
                        'name_en' => 'Sa\'sa\'',
                        'name_tr' => 'Sa\'sa\'',
                        'match_names' => [
                        'سعسع',
                        'Sa\'sa\'',
                        'Sasa',
                        ],
                    ],
                    [
                        'code' => 'SY030800',
                        'name_ar' => 'مركز قطنا',
                        'name_en' => 'Qatana',
                        'name_tr' => 'Qatana',
                        'match_names' => [
                        'مركز قطنا',
                        'Qatana',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0301',
                'name_ar' => 'مركز ريف دمشق',
                'name_en' => 'Rural Damascus',
                'name_tr' => 'Rural Damascus',
                'match_names' => [
                    'مركز ريف دمشق',
                    'Rural Damascus',
                ],
                'districts' => [
                    [
                        'code' => 'SY030101',
                        'name_ar' => 'الكسوة',
                        'name_en' => 'Kisweh',
                        'name_tr' => 'Kisweh',
                        'match_names' => [
                        'الكسوة',
                        'Kisweh',
                        ],
                    ],
                    [
                        'code' => 'SY030104',
                        'name_ar' => 'المليحة',
                        'name_en' => 'Maliha',
                        'name_tr' => 'Maliha',
                        'match_names' => [
                        'المليحة',
                        'Maliha',
                        ],
                    ],
                    [
                        'code' => 'SY030102',
                        'name_ar' => 'ببيلا',
                        'name_en' => 'Babella',
                        'name_tr' => 'Babella',
                        'match_names' => [
                        'ببيلا',
                        'Babella',
                        ],
                    ],
                    [
                        'code' => 'SY030103',
                        'name_ar' => 'جرمانا',
                        'name_en' => 'Jaramana',
                        'name_tr' => 'Jaramana',
                        'match_names' => [
                        'جرمانا',
                        'Jaramana',
                        ],
                    ],
                    [
                        'code' => 'SY030106',
                        'name_ar' => 'عربين',
                        'name_en' => 'Arbin',
                        'name_tr' => 'Arbin',
                        'match_names' => [
                        'عربين',
                        'Arbin',
                        ],
                    ],
                    [
                        'code' => 'SY030107',
                        'name_ar' => 'قدسيا',
                        'name_en' => 'Qudsiya',
                        'name_tr' => 'Qudsiya',
                        'match_names' => [
                        'قدسيا',
                        'Qudsiya',
                        ],
                    ],
                    [
                        'code' => 'SY030105',
                        'name_ar' => 'كفر بطنا',
                        'name_en' => 'Kafr Batna',
                        'name_tr' => 'Kafr Batna',
                        'match_names' => [
                        'كفر بطنا',
                        'Kafr Batna',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0305',
                'name_ar' => 'يبرود',
                'name_en' => 'Yabroud',
                'name_tr' => 'Yabroud',
                'match_names' => [
                    'يبرود',
                    'Yabroud',
                ],
                'districts' => [
                    [
                        'code' => 'SY030501',
                        'name_ar' => 'عسال الورد',
                        'name_en' => 'Esal El-Ward',
                        'name_tr' => 'Esal El-Ward',
                        'match_names' => [
                        'عسال الورد',
                        'Esal El-Ward',
                        ],
                    ],
                    [
                        'code' => 'SY030500',
                        'name_ar' => 'مركز يبرود',
                        'name_en' => 'Yabroud',
                        'name_tr' => 'Yabroud',
                        'match_names' => [
                        'مركز يبرود',
                        'Yabroud',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY04',
        'name_ar' => 'حمص',
        'name_en' => 'Homs',
        'name_tr' => 'Humus ili',
        'match_names' => [
            'حمص',
            'Homs',
        ],
        'cities' => [
            [
                'code' => 'SY0404',
                'name_ar' => 'الرستن',
                'name_en' => 'Ar-Rastan',
                'name_tr' => 'Ar-Rastan',
                'match_names' => [
                    'الرستن',
                    'Ar-Rastan',
                ],
                'districts' => [
                    [
                        'code' => 'SY040401',
                        'name_ar' => 'تلبيسة',
                        'name_en' => 'Talbiseh',
                        'name_tr' => 'Talbiseh',
                        'match_names' => [
                        'تلبيسة',
                        'Talbiseh',
                        ],
                    ],
                    [
                        'code' => 'SY040400',
                        'name_ar' => 'مركز الرستن',
                        'name_en' => 'Ar-Rastan',
                        'name_tr' => 'Ar-Rastan',
                        'match_names' => [
                        'مركز الرستن',
                        'Ar-Rastan',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0402',
                'name_ar' => 'القصير',
                'name_en' => 'Al-Qusayr',
                'name_tr' => 'Al-Qusayr',
                'match_names' => [
                    'القصير',
                    'Al-Qusayr',
                ],
                'districts' => [
                    [
                        'code' => 'SY040200',
                        'name_ar' => 'مركز القصير',
                        'name_en' => 'Al Quasir',
                        'name_tr' => 'Al Quasir',
                        'match_names' => [
                        'مركز القصير',
                        'Al Quasir',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0406',
                'name_ar' => 'المخرم',
                'name_en' => 'Al Makhrim',
                'name_tr' => 'Al Makhrim',
                'match_names' => [
                    'المخرم',
                    'Al Makhrim',
                ],
                'districts' => [
                    [
                        'code' => 'SY040600',
                        'name_ar' => 'المخرم',
                        'name_en' => 'Al Makhrim',
                        'name_tr' => 'Al Makhrim',
                        'match_names' => [
                        'المخرم',
                        'Al Makhrim',
                        ],
                    ],
                    [
                        'code' => 'SY040601',
                        'name_ar' => 'جب الجراح',
                        'name_en' => 'Jeb Ej-Jarrah',
                        'name_tr' => 'Jeb Ej-Jarrah',
                        'match_names' => [
                        'جب الجراح',
                        'Jeb Ej-Jarrah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0405',
                'name_ar' => 'تدمر',
                'name_en' => 'Tadmor',
                'name_tr' => 'Tadmor',
                'match_names' => [
                    'تدمر',
                    'Tadmor',
                ],
                'districts' => [
                    [
                        'code' => 'SY040501',
                        'name_ar' => 'السخنة',
                        'name_en' => 'Sokhneh',
                        'name_tr' => 'Sokhneh',
                        'match_names' => [
                        'السخنة',
                        'Sokhneh',
                        ],
                    ],
                    [
                        'code' => 'SY040500',
                        'name_ar' => 'مركز تدمر',
                        'name_en' => 'Tadmor',
                        'name_tr' => 'Tadmor',
                        'match_names' => [
                        'مركز تدمر',
                        'Tadmor',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0403',
                'name_ar' => 'تلكلخ',
                'name_en' => 'Tall Kalakh',
                'name_tr' => 'Tall Kalakh',
                'match_names' => [
                    'تلكلخ',
                    'Tall Kalakh',
                ],
                'districts' => [
                    [
                        'code' => 'SY040304',
                        'name_ar' => 'الحواش',
                        'name_en' => 'Hawash',
                        'name_tr' => 'Hawash',
                        'match_names' => [
                        'الحواش',
                        'Hawash',
                        ],
                    ],
                    [
                        'code' => 'SY040303',
                        'name_ar' => 'الناصرة',
                        'name_en' => 'Nasra',
                        'name_tr' => 'Nasra',
                        'match_names' => [
                        'الناصرة',
                        'Nasra',
                        ],
                    ],
                    [
                        'code' => 'SY040301',
                        'name_ar' => 'حديدة',
                        'name_en' => 'Hadideh',
                        'name_tr' => 'Hadideh',
                        'match_names' => [
                        'حديدة',
                        'Hadideh',
                        ],
                    ],
                    [
                        'code' => 'SY040300',
                        'name_ar' => 'مركز تلكلخ',
                        'name_en' => 'Tall Kalakh',
                        'name_tr' => 'Tall Kalakh',
                        'match_names' => [
                        'مركز تلكلخ',
                        'Tall Kalakh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0401',
                'name_ar' => 'مركز حمص',
                'name_en' => 'Homs',
                'name_tr' => 'Homs',
                'match_names' => [
                    'مركز حمص',
                    'Homs',
                ],
                'districts' => [
                    [
                        'code' => 'SY040105',
                        'name_ar' => 'الرقاما',
                        'name_en' => 'Raqama',
                        'name_tr' => 'Raqama',
                        'match_names' => [
                        'الرقاما',
                        'Raqama',
                        ],
                    ],
                    [
                        'code' => 'SY040104',
                        'name_ar' => 'الفرقلس',
                        'name_en' => 'Farqalas',
                        'name_tr' => 'Farqalas',
                        'match_names' => [
                        'الفرقلس',
                        'Farqalas',
                        ],
                    ],
                    [
                        'code' => 'SY040110',
                        'name_ar' => 'القبو',
                        'name_en' => 'Qabu',
                        'name_tr' => 'Qabu',
                        'match_names' => [
                        'القبو',
                        'Qabu',
                        ],
                    ],
                    [
                        'code' => 'SY040106',
                        'name_ar' => 'القريتين',
                        'name_en' => 'Qaryatein',
                        'name_tr' => 'Qaryatein',
                        'match_names' => [
                        'القريتين',
                        'Qaryatein',
                        ],
                    ],
                    [
                        'code' => 'SY040101',
                        'name_ar' => 'تلدو',
                        'name_en' => 'Taldu',
                        'name_tr' => 'Taldu',
                        'match_names' => [
                        'تلدو',
                        'Taldu',
                        ],
                    ],
                    [
                        'code' => 'SY040108',
                        'name_ar' => 'حسياء',
                        'name_en' => 'Hasyaa',
                        'name_tr' => 'Hasyaa',
                        'match_names' => [
                        'حسياء',
                        'Hasyaa',
                        ],
                    ],
                    [
                        'code' => 'SY040102',
                        'name_ar' => 'خربة تين نور',
                        'name_en' => 'Kherbet Tin Noor',
                        'name_tr' => 'Kherbet Tin Noor',
                        'match_names' => [
                        'خربة تين نور',
                        'Kherbet Tin Noor',
                        ],
                    ],
                    [
                        'code' => 'SY040111',
                        'name_ar' => 'شين',
                        'name_en' => 'Shin',
                        'name_tr' => 'Shin',
                        'match_names' => [
                        'شين',
                        'Shin',
                        ],
                    ],
                    [
                        'code' => 'SY040109',
                        'name_ar' => 'صدد',
                        'name_en' => 'Sadad',
                        'name_tr' => 'Sadad',
                        'match_names' => [
                        'صدد',
                        'Sadad',
                        ],
                    ],
                    [
                        'code' => 'SY040103',
                        'name_ar' => 'عين النسر',
                        'name_en' => 'Ein Elniser',
                        'name_tr' => 'Ein Elniser',
                        'match_names' => [
                        'عين النسر',
                        'Ein Elniser',
                        ],
                    ],
                    [
                        'code' => 'SY040100',
                        'name_ar' => 'مركز حمص',
                        'name_en' => 'Homs',
                        'name_tr' => 'Homs',
                        'match_names' => [
                        'مركز حمص',
                        'Homs',
                        ],
                    ],
                    [
                        'code' => 'SY040107',
                        'name_ar' => 'مهين',
                        'name_en' => 'Mahin',
                        'name_tr' => 'Mahin',
                        'match_names' => [
                        'مهين',
                        'Mahin',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY05',
        'name_ar' => 'حماة',
        'name_en' => 'Hama',
        'name_tr' => 'Hama ili',
        'match_names' => [
            'حماة',
            'Hama',
        ],
        'cities' => [
            [
                'code' => 'SY0502',
                'name_ar' => 'السقيلبية',
                'name_en' => 'As-Suqaylabiyah',
                'name_tr' => 'As-Suqaylabiyah',
                'match_names' => [
                    'السقيلبية',
                    'As-Suqaylabiyah',
                ],
                'districts' => [
                    [
                        'code' => 'SY050202',
                        'name_ar' => 'الزيارة',
                        'name_en' => 'Ziyara',
                        'name_tr' => 'Ziyara',
                        'match_names' => [
                        'الزيارة',
                        'Ziyara',
                        ],
                    ],
                    [
                        'code' => 'SY050201',
                        'name_ar' => 'تلسلحب',
                        'name_en' => 'Tell Salhib',
                        'name_tr' => 'Tell Salhib',
                        'match_names' => [
                        'تلسلحب',
                        'Tell Salhib',
                        ],
                    ],
                    [
                        'code' => 'SY050203',
                        'name_ar' => 'شطحة',
                        'name_en' => 'Shat-ha',
                        'name_tr' => 'Shat-ha',
                        'match_names' => [
                        'شطحة',
                        'Shat-ha',
                        ],
                    ],
                    [
                        'code' => 'SY050204',
                        'name_ar' => 'قلعة المضيق',
                        'name_en' => 'Madiq Castle',
                        'name_tr' => 'Madiq Castle',
                        'match_names' => [
                        'قلعة المضيق',
                        'Madiq Castle',
                        ],
                    ],
                    [
                        'code' => 'SY050200',
                        'name_ar' => 'مركز السقيلبية',
                        'name_en' => 'As-Suqaylabiyah',
                        'name_tr' => 'As-Suqaylabiyah',
                        'match_names' => [
                        'مركز السقيلبية',
                        'As-Suqaylabiyah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0503',
                'name_ar' => 'السلمية',
                'name_en' => 'As-Salamiyeh',
                'name_tr' => 'As-Salamiyeh',
                'match_names' => [
                    'السلمية',
                    'As-Salamiyeh',
                ],
                'districts' => [
                    [
                        'code' => 'SY050302',
                        'name_ar' => 'السعن',
                        'name_en' => 'As-Saan',
                        'name_tr' => 'As-Saan',
                        'match_names' => [
                        'السعن',
                        'As-Saan',
                        ],
                    ],
                    [
                        'code' => 'SY050301',
                        'name_ar' => 'بري شرقي',
                        'name_en' => 'Eastern Bari',
                        'name_tr' => 'Eastern Bari',
                        'match_names' => [
                        'بري شرقي',
                        'Eastern Bari',
                        ],
                    ],
                    [
                        'code' => 'SY050303',
                        'name_ar' => 'صبورة',
                        'name_en' => 'Saboura',
                        'name_tr' => 'Saboura',
                        'match_names' => [
                        'صبورة',
                        'Saboura',
                        ],
                    ],
                    [
                        'code' => 'SY050304',
                        'name_ar' => 'عقيربات',
                        'name_en' => 'Oqeirbat',
                        'name_tr' => 'Oqeirbat',
                        'match_names' => [
                        'عقيربات',
                        'Oqeirbat',
                        ],
                    ],
                    [
                        'code' => 'SY050300',
                        'name_ar' => 'مركز السلمية',
                        'name_en' => 'As-Salamiyeh',
                        'name_tr' => 'As-Salamiyeh',
                        'match_names' => [
                        'مركز السلمية',
                        'As-Salamiyeh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0505',
                'name_ar' => 'محردة',
                'name_en' => 'Muhradah',
                'name_tr' => 'Muhradah',
                'match_names' => [
                    'محردة',
                    'Muhradah',
                ],
                'districts' => [
                    [
                        'code' => 'SY050502',
                        'name_ar' => 'كرناز',
                        'name_en' => 'Karnaz',
                        'name_tr' => 'Karnaz',
                        'match_names' => [
                        'كرناز',
                        'Karnaz',
                        ],
                    ],
                    [
                        'code' => 'SY050501',
                        'name_ar' => 'كفرزيتا',
                        'name_en' => 'Kafr Zeita',
                        'name_tr' => 'Kafr Zeita',
                        'match_names' => [
                        'كفرزيتا',
                        'Kafr Zeita',
                        ],
                    ],
                    [
                        'code' => 'SY050500',
                        'name_ar' => 'مركز محردة',
                        'name_en' => 'Muhradah',
                        'name_tr' => 'Muhradah',
                        'match_names' => [
                        'مركز محردة',
                        'Muhradah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0501',
                'name_ar' => 'مركز حماة',
                'name_en' => 'Hama',
                'name_tr' => 'Hama',
                'match_names' => [
                    'مركز حماة',
                    'Hama',
                ],
                'districts' => [
                    [
                        'code' => 'SY050103',
                        'name_ar' => 'الحمراء',
                        'name_en' => 'Hamra',
                        'name_tr' => 'Hamra',
                        'match_names' => [
                        'الحمراء',
                        'Hamra',
                        ],
                    ],
                    [
                        'code' => 'SY050102',
                        'name_ar' => 'حربنفسه',
                        'name_en' => 'Harbanifse',
                        'name_tr' => 'Harbanifse',
                        'match_names' => [
                        'حربنفسه',
                        'Harbanifse',
                        ],
                    ],
                    [
                        'code' => 'SY050101',
                        'name_ar' => 'صوران',
                        'name_en' => 'Suran',
                        'name_tr' => 'Suran',
                        'match_names' => [
                        'صوران',
                        'Suran',
                        ],
                    ],
                    [
                        'code' => 'SY050100',
                        'name_ar' => 'مركز حماة',
                        'name_en' => 'Hama',
                        'name_tr' => 'Hama',
                        'match_names' => [
                        'مركز حماة',
                        'Hama',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0504',
                'name_ar' => 'مصياف',
                'name_en' => 'Masyaf',
                'name_tr' => 'Masyaf',
                'match_names' => [
                    'مصياف',
                    'Masyaf',
                ],
                'districts' => [
                    [
                        'code' => 'SY050401',
                        'name_ar' => 'جب رملة',
                        'name_en' => 'Jeb Ramleh',
                        'name_tr' => 'Jeb Ramleh',
                        'match_names' => [
                        'جب رملة',
                        'Jeb Ramleh',
                        ],
                    ],
                    [
                        'code' => 'SY050402',
                        'name_ar' => 'عوج',
                        'name_en' => 'Oj',
                        'name_tr' => 'Oj',
                        'match_names' => [
                        'عوج',
                        'Oj',
                        ],
                    ],
                    [
                        'code' => 'SY050403',
                        'name_ar' => 'عين حلاقيم',
                        'name_en' => 'Ein Halaqim',
                        'name_tr' => 'Ein Halaqim',
                        'match_names' => [
                        'عين حلاقيم',
                        'Ein Halaqim',
                        ],
                    ],
                    [
                        'code' => 'SY050400',
                        'name_ar' => 'مركز مصياف',
                        'name_en' => 'Masyaf',
                        'name_tr' => 'Masyaf',
                        'match_names' => [
                        'مركز مصياف',
                        'Masyaf',
                        ],
                    ],
                    [
                        'code' => 'SY050404',
                        'name_ar' => 'وادي العيون',
                        'name_en' => 'Wadi El-oyoun',
                        'name_tr' => 'Wadi El-oyoun',
                        'match_names' => [
                        'وادي العيون',
                        'Wadi El-oyoun',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY06',
        'name_ar' => 'اللاذقية',
        'name_en' => 'Lattakia',
        'name_tr' => 'Lazkiye ili',
        'match_names' => [
            'اللاذقية',
            'Lattakia',
        ],
        'cities' => [
            [
                'code' => 'SY0603',
                'name_ar' => 'الحفة',
                'name_en' => 'Al-Haffa',
                'name_tr' => 'Al-Haffa',
                'match_names' => [
                    'الحفة',
                    'Al-Haffa',
                ],
                'districts' => [
                    [
                        'code' => 'SY060301',
                        'name_ar' => 'صلنفة',
                        'name_en' => 'Salanfa',
                        'name_tr' => 'Salanfa',
                        'match_names' => [
                        'صلنفة',
                        'Salanfa',
                        ],
                    ],
                    [
                        'code' => 'SY060302',
                        'name_ar' => 'عين التينة',
                        'name_en' => 'Ein Et-teeneh',
                        'name_tr' => 'Ein Et-teeneh',
                        'match_names' => [
                        'عين التينة',
                        'Ein Et-teeneh',
                        ],
                    ],
                    [
                        'code' => 'SY060303',
                        'name_ar' => 'كنسبا',
                        'name_en' => 'Kansaba',
                        'name_tr' => 'Kansaba',
                        'match_names' => [
                        'كنسبا',
                        'Kansaba',
                        ],
                    ],
                    [
                        'code' => 'SY060300',
                        'name_ar' => 'مركزالحفة',
                        'name_en' => 'Al-Haffa',
                        'name_tr' => 'Al-Haffa',
                        'match_names' => [
                        'مركزالحفة',
                        'Al-Haffa',
                        ],
                    ],
                    [
                        'code' => 'SY060304',
                        'name_ar' => 'مزيرعة',
                        'name_en' => 'Mzair\'a',
                        'name_tr' => 'Mzair\'a',
                        'match_names' => [
                        'مزيرعة',
                        'Mzair\'a',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0604',
                'name_ar' => 'القرداحة',
                'name_en' => 'Al-Qardaha',
                'name_tr' => 'Al-Qardaha',
                'match_names' => [
                    'القرداحة',
                    'Al-Qardaha',
                ],
                'districts' => [
                    [
                        'code' => 'SY060402',
                        'name_ar' => 'الفاخورة',
                        'name_en' => 'Fakhura',
                        'name_tr' => 'Fakhura',
                        'match_names' => [
                        'الفاخورة',
                        'Fakhura',
                        ],
                    ],
                    [
                        'code' => 'SY060403',
                        'name_ar' => 'جوبة برغال',
                        'name_en' => 'Jobet Berghal',
                        'name_tr' => 'Jobet Berghal',
                        'match_names' => [
                        'جوبة برغال',
                        'Jobet Berghal',
                        ],
                    ],
                    [
                        'code' => 'SY060401',
                        'name_ar' => 'حرف المسيترة',
                        'name_en' => 'Harf Elmseitra',
                        'name_tr' => 'Harf Elmseitra',
                        'match_names' => [
                        'حرف المسيترة',
                        'Harf Elmseitra',
                        ],
                    ],
                    [
                        'code' => 'SY060400',
                        'name_ar' => 'مركز القرداحة',
                        'name_en' => 'Al-Qardaha',
                        'name_tr' => 'Al-Qardaha',
                        'match_names' => [
                        'مركز القرداحة',
                        'Al-Qardaha',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0602',
                'name_ar' => 'جبلة',
                'name_en' => 'Jablah',
                'name_tr' => 'Jablah',
                'match_names' => [
                    'جبلة',
                    'Jablah',
                ],
                'districts' => [
                    [
                        'code' => 'SY060202',
                        'name_ar' => 'القطيلبية',
                        'name_en' => 'Qteilbiyyeh',
                        'name_tr' => 'Qteilbiyyeh',
                        'match_names' => [
                        'القطيلبية',
                        'Qteilbiyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY060205',
                        'name_ar' => 'بيت ياشوط',
                        'name_en' => 'Beit Yashout',
                        'name_tr' => 'Beit Yashout',
                        'match_names' => [
                        'بيت ياشوط',
                        'Beit Yashout',
                        ],
                    ],
                    [
                        'code' => 'SY060204',
                        'name_ar' => 'دالية',
                        'name_en' => 'Dalyeh',
                        'name_tr' => 'Dalyeh',
                        'match_names' => [
                        'دالية',
                        'Dalyeh',
                        ],
                    ],
                    [
                        'code' => 'SY060201',
                        'name_ar' => 'عين الشرقية',
                        'name_en' => 'Ein Elshaqiyeh',
                        'name_tr' => 'Ein Elshaqiyeh',
                        'match_names' => [
                        'عين الشرقية',
                        'Ein Elshaqiyeh',
                        ],
                    ],
                    [
                        'code' => 'SY060203',
                        'name_ar' => 'عين شقاق',
                        'name_en' => 'Ein Shaqaq',
                        'name_tr' => 'Ein Shaqaq',
                        'match_names' => [
                        'عين شقاق',
                        'Ein Shaqaq',
                        ],
                    ],
                    [
                        'code' => 'SY060200',
                        'name_ar' => 'مركز جبلة',
                        'name_en' => 'Jablah',
                        'name_tr' => 'Jablah',
                        'match_names' => [
                        'مركز جبلة',
                        'Jablah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0600',
                'name_ar' => 'مركز اللاذقية',
                'name_en' => 'Lattakia',
                'name_tr' => 'Lattakia',
                'match_names' => [
                    'مركز اللاذقية',
                    'Lattakia',
                ],
                'districts' => [
                    [
                        'code' => 'SY060001',
                        'name_ar' => 'البهلولية',
                        'name_en' => 'Bahlawaniyeh',
                        'name_tr' => 'Bahlawaniyeh',
                        'match_names' => [
                        'البهلولية',
                        'Bahlawaniyeh',
                        ],
                    ],
                    [
                        'code' => 'SY060002',
                        'name_ar' => 'ربيعة',
                        'name_en' => 'Rabee\'a',
                        'name_tr' => 'Rabee\'a',
                        'match_names' => [
                        'ربيعة',
                        'Rabee\'a',
                        'Rabeea',
                        ],
                    ],
                    [
                        'code' => 'SY060003',
                        'name_ar' => 'عين البيضا',
                        'name_en' => 'Ein El-Bayda',
                        'name_tr' => 'Ein El-Bayda',
                        'match_names' => [
                        'عين البيضا',
                        'Ein El-Bayda',
                        ],
                    ],
                    [
                        'code' => 'SY060004',
                        'name_ar' => 'قسطل معاف',
                        'name_en' => 'Qastal Maaf',
                        'name_tr' => 'Qastal Maaf',
                        'match_names' => [
                        'قسطل معاف',
                        'Qastal Maaf',
                        ],
                    ],
                    [
                        'code' => 'SY060005',
                        'name_ar' => 'كسب',
                        'name_en' => 'Kiseb',
                        'name_tr' => 'Kiseb',
                        'match_names' => [
                        'كسب',
                        'Kiseb',
                        ],
                    ],
                    [
                        'code' => 'SY060000',
                        'name_ar' => 'مركز اللاذقية',
                        'name_en' => 'Lattakia',
                        'name_tr' => 'Lattakia',
                        'match_names' => [
                        'مركز اللاذقية',
                        'Lattakia',
                        ],
                    ],
                    [
                        'code' => 'SY060006',
                        'name_ar' => 'هنادي',
                        'name_en' => 'Hanadi',
                        'name_tr' => 'Hanadi',
                        'match_names' => [
                        'هنادي',
                        'Hanadi',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY07',
        'name_ar' => 'إدلب',
        'name_en' => 'Idleb',
        'name_tr' => 'İdlib ili',
        'match_names' => [
            'إدلب',
            'Idleb',
        ],
        'cities' => [
            [
                'code' => 'SY0705',
                'name_ar' => 'أريحا',
                'name_en' => 'Ariha',
                'name_tr' => 'Ariha',
                'match_names' => [
                    'أريحا',
                    'Ariha',
                ],
                'districts' => [
                    [
                        'code' => 'SY070501',
                        'name_ar' => 'احسم',
                        'name_en' => 'Ehsem',
                        'name_tr' => 'Ehsem',
                        'match_names' => [
                        'احسم',
                        'Ehsem',
                        ],
                    ],
                    [
                        'code' => 'SY070502',
                        'name_ar' => 'محمبل',
                        'name_en' => 'Mhambal',
                        'name_tr' => 'Mhambal',
                        'match_names' => [
                        'محمبل',
                        'Mhambal',
                        ],
                    ],
                    [
                        'code' => 'SY070500',
                        'name_ar' => 'مركز أريحا',
                        'name_en' => 'Ariha',
                        'name_tr' => 'Ariha',
                        'match_names' => [
                        'مركز أريحا',
                        'Ariha',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0704',
                'name_ar' => 'جسر الشغور',
                'name_en' => 'Jisr-Ash-Shugur',
                'name_tr' => 'Jisr-Ash-Shugur',
                'match_names' => [
                    'جسر الشغور',
                    'Jisr-Ash-Shugur',
                ],
                'districts' => [
                    [
                        'code' => 'SY070403',
                        'name_ar' => 'الجانودية',
                        'name_en' => 'Janudiyeh',
                        'name_tr' => 'Janudiyeh',
                        'match_names' => [
                        'الجانودية',
                        'Janudiyeh',
                        ],
                    ],
                    [
                        'code' => 'SY070401',
                        'name_ar' => 'بداما',
                        'name_en' => 'Badama',
                        'name_tr' => 'Badama',
                        'match_names' => [
                        'بداما',
                        'Badama',
                        ],
                    ],
                    [
                        'code' => 'SY070402',
                        'name_ar' => 'دركوش',
                        'name_en' => 'Darkosh',
                        'name_tr' => 'Darkosh',
                        'match_names' => [
                        'دركوش',
                        'Darkosh',
                        ],
                    ],
                    [
                        'code' => 'SY070400',
                        'name_ar' => 'مركز جسر الشغور',
                        'name_en' => 'Jisr-Ash-Shugur',
                        'name_tr' => 'Jisr-Ash-Shugur',
                        'match_names' => [
                        'مركز جسر الشغور',
                        'Jisr-Ash-Shugur',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0703',
                'name_ar' => 'حارم',
                'name_en' => 'Harim',
                'name_tr' => 'Harim',
                'match_names' => [
                    'حارم',
                    'Harim',
                ],
                'districts' => [
                    [
                        'code' => 'SY070305',
                        'name_ar' => 'أرمناز',
                        'name_en' => 'Armanaz',
                        'name_tr' => 'Armanaz',
                        'match_names' => [
                        'أرمناز',
                        'Armanaz',
                        ],
                    ],
                    [
                        'code' => 'SY070301',
                        'name_ar' => 'دانا',
                        'name_en' => 'Dana',
                        'name_tr' => 'Dana',
                        'match_names' => [
                        'دانا',
                        'Dana',
                        ],
                    ],
                    [
                        'code' => 'SY070302',
                        'name_ar' => 'سلقين',
                        'name_en' => 'Salqin',
                        'name_tr' => 'Salqin',
                        'match_names' => [
                        'سلقين',
                        'Salqin',
                        ],
                    ],
                    [
                        'code' => 'SY070304',
                        'name_ar' => 'قورقينا',
                        'name_en' => 'Qourqeena',
                        'name_tr' => 'Qourqeena',
                        'match_names' => [
                        'قورقينا',
                        'Qourqeena',
                        ],
                    ],
                    [
                        'code' => 'SY070303',
                        'name_ar' => 'كفر تخاريم',
                        'name_en' => 'Kafr Takharim',
                        'name_tr' => 'Kafr Takharim',
                        'match_names' => [
                        'كفر تخاريم',
                        'Kafr Takharim',
                        ],
                    ],
                    [
                        'code' => 'SY070300',
                        'name_ar' => 'مركز حارم',
                        'name_en' => 'Harim',
                        'name_tr' => 'Harim',
                        'match_names' => [
                        'مركز حارم',
                        'Harim',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0700',
                'name_ar' => 'مركز إدلب',
                'name_en' => 'Idleb',
                'name_tr' => 'Idleb',
                'match_names' => [
                    'مركز إدلب',
                    'Idleb',
                ],
                'districts' => [
                    [
                        'code' => 'SY070001',
                        'name_ar' => 'أبو الظهور',
                        'name_en' => 'Abul Thohur',
                        'name_tr' => 'Abul Thohur',
                        'match_names' => [
                        'أبو الظهور',
                        'Abul Thohur',
                        ],
                    ],
                    [
                        'code' => 'SY070002',
                        'name_ar' => 'بنش',
                        'name_en' => 'Bennsh',
                        'name_tr' => 'Bennsh',
                        'match_names' => [
                        'بنش',
                        'Bennsh',
                        ],
                    ],
                    [
                        'code' => 'SY070004',
                        'name_ar' => 'تفتناز',
                        'name_en' => 'Teftnaz',
                        'name_tr' => 'Teftnaz',
                        'match_names' => [
                        'تفتناز',
                        'Teftnaz',
                        ],
                    ],
                    [
                        'code' => 'SY070003',
                        'name_ar' => 'سراقب',
                        'name_en' => 'Saraqab',
                        'name_tr' => 'Saraqab',
                        'match_names' => [
                        'سراقب',
                        'Saraqab',
                        ],
                    ],
                    [
                        'code' => 'SY070006',
                        'name_ar' => 'سرمين',
                        'name_en' => 'Sarmin',
                        'name_tr' => 'Sarmin',
                        'match_names' => [
                        'سرمين',
                        'Sarmin',
                        ],
                    ],
                    [
                        'code' => 'SY070000',
                        'name_ar' => 'مركز إدلب',
                        'name_en' => 'Idleb',
                        'name_tr' => 'Idleb',
                        'match_names' => [
                        'مركز إدلب',
                        'Idleb',
                        ],
                    ],
                    [
                        'code' => 'SY070005',
                        'name_ar' => 'معرة تمصرين',
                        'name_en' => 'Maaret Tamsrin',
                        'name_tr' => 'Maaret Tamsrin',
                        'match_names' => [
                        'معرة تمصرين',
                        'Maaret Tamsrin',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0702',
                'name_ar' => 'معرة النعمان',
                'name_en' => 'Al Ma\'ra',
                'name_tr' => 'Al Ma\'ra',
                'match_names' => [
                    'معرة النعمان',
                    'Al Ma\'ra',
                    'Al Mara',
                ],
                'districts' => [
                    [
                        'code' => 'SY070204',
                        'name_ar' => 'التمانعة',
                        'name_en' => 'Tamanaah',
                        'name_tr' => 'Tamanaah',
                        'match_names' => [
                        'التمانعة',
                        'Tamanaah',
                        ],
                    ],
                    [
                        'code' => 'SY070205',
                        'name_ar' => 'حيش',
                        'name_en' => 'Heish',
                        'name_tr' => 'Heish',
                        'match_names' => [
                        'حيش',
                        'Heish',
                        ],
                    ],
                    [
                        'code' => 'SY070201',
                        'name_ar' => 'خان شيخون',
                        'name_en' => 'Khan Shaykun',
                        'name_tr' => 'Khan Shaykun',
                        'match_names' => [
                        'خان شيخون',
                        'Khan Shaykun',
                        ],
                    ],
                    [
                        'code' => 'SY070202',
                        'name_ar' => 'سنجار',
                        'name_en' => 'Sanjar',
                        'name_tr' => 'Sanjar',
                        'match_names' => [
                        'سنجار',
                        'Sanjar',
                        ],
                    ],
                    [
                        'code' => 'SY070203',
                        'name_ar' => 'كفر نبل',
                        'name_en' => 'Kafr Nobol',
                        'name_tr' => 'Kafr Nobol',
                        'match_names' => [
                        'كفر نبل',
                        'Kafr Nobol',
                        ],
                    ],
                    [
                        'code' => 'SY070200',
                        'name_ar' => 'مركز معرة النعمان',
                        'name_en' => 'Ma\'arrat An Nu\'man',
                        'name_tr' => 'Ma\'arrat An Nu\'man',
                        'match_names' => [
                        'مركز معرة النعمان',
                        'Ma\'arrat An Nu\'man',
                        'Maarrat An Numan',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY08',
        'name_ar' => 'الحسكة',
        'name_en' => 'Al-Hasakeh',
        'name_tr' => 'Haseke ili',
        'match_names' => [
            'الحسكة',
            'Al-Hasakeh',
        ],
        'cities' => [
            [
                'code' => 'SY0802',
                'name_ar' => 'القامشلي',
                'name_en' => 'Quamishli',
                'name_tr' => 'Quamishli',
                'match_names' => [
                    'القامشلي',
                    'Quamishli',
                ],
                'districts' => [
                    [
                        'code' => 'SY080201',
                        'name_ar' => 'تل حميس',
                        'name_en' => 'Tal Hmis',
                        'name_tr' => 'Tal Hmis',
                        'match_names' => [
                        'تل حميس',
                        'Tal Hmis',
                        ],
                    ],
                    [
                        'code' => 'SY080202',
                        'name_ar' => 'عامودا',
                        'name_en' => 'Amuda',
                        'name_tr' => 'Amuda',
                        'match_names' => [
                        'عامودا',
                        'Amuda',
                        ],
                    ],
                    [
                        'code' => 'SY080203',
                        'name_ar' => 'قحطانية',
                        'name_en' => 'Qahtaniyyeh',
                        'name_tr' => 'Qahtaniyyeh',
                        'match_names' => [
                        'قحطانية',
                        'Qahtaniyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY080200',
                        'name_ar' => 'مركز القامشلي',
                        'name_en' => 'Quamishli',
                        'name_tr' => 'Quamishli',
                        'match_names' => [
                        'مركز القامشلي',
                        'Quamishli',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0803',
                'name_ar' => 'المالكية',
                'name_en' => 'Al-Malikeyyeh',
                'name_tr' => 'Al-Malikeyyeh',
                'match_names' => [
                    'المالكية',
                    'Al-Malikeyyeh',
                ],
                'districts' => [
                    [
                        'code' => 'SY080301',
                        'name_ar' => 'جوادية',
                        'name_en' => 'Jawadiyah',
                        'name_tr' => 'Jawadiyah',
                        'match_names' => [
                        'جوادية',
                        'Jawadiyah',
                        ],
                    ],
                    [
                        'code' => 'SY080300',
                        'name_ar' => 'مركز المالكية',
                        'name_en' => 'Al-Malikeyyeh',
                        'name_tr' => 'Al-Malikeyyeh',
                        'match_names' => [
                        'مركز المالكية',
                        'Al-Malikeyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY080302',
                        'name_ar' => 'يعربية',
                        'name_en' => 'Ya\'robiyah',
                        'name_tr' => 'Ya\'robiyah',
                        'match_names' => [
                        'يعربية',
                        'Ya\'robiyah',
                        'Yarobiyah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0804',
                'name_ar' => 'رأس العين',
                'name_en' => 'Ras Al Ain',
                'name_tr' => 'Ras Al Ain',
                'match_names' => [
                    'رأس العين',
                    'Ras Al Ain',
                ],
                'districts' => [
                    [
                        'code' => 'SY080401',
                        'name_ar' => 'درباسية',
                        'name_en' => 'Darbasiyah',
                        'name_tr' => 'Darbasiyah',
                        'match_names' => [
                        'درباسية',
                        'Darbasiyah',
                        ],
                    ],
                    [
                        'code' => 'SY080400',
                        'name_ar' => 'مركز رأس العين',
                        'name_en' => 'Ras Al Ain',
                        'name_tr' => 'Ras Al Ain',
                        'match_names' => [
                        'مركز رأس العين',
                        'Ras Al Ain',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0800',
                'name_ar' => 'مركز الحسكة',
                'name_en' => 'Al-Hasakeh',
                'name_tr' => 'Al-Hasakeh',
                'match_names' => [
                    'مركز الحسكة',
                    'Al-Hasakeh',
                ],
                'districts' => [
                    [
                        'code' => 'SY080005',
                        'name_ar' => 'العريشة',
                        'name_en' => 'Areesheh',
                        'name_tr' => 'Areesheh',
                        'match_names' => [
                        'العريشة',
                        'Areesheh',
                        ],
                    ],
                    [
                        'code' => 'SY080006',
                        'name_ar' => 'الهول',
                        'name_en' => 'Hole',
                        'name_tr' => 'Hole',
                        'match_names' => [
                        'الهول',
                        'Hole',
                        ],
                    ],
                    [
                        'code' => 'SY080004',
                        'name_ar' => 'بئر الحلو الوردية',
                        'name_en' => 'Be\'r Al-Hulo Al-Wardeyyeh',
                        'name_tr' => 'Be\'r Al-Hulo Al-Wardeyyeh',
                        'match_names' => [
                        'بئر الحلو الوردية',
                        'Be\'r Al-Hulo Al-Wardeyyeh',
                        'Ber Al-Hulo Al-Wardeyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY080001',
                        'name_ar' => 'تل تمر',
                        'name_en' => 'Tal Tamer',
                        'name_tr' => 'Tal Tamer',
                        'match_names' => [
                        'تل تمر',
                        'Tal Tamer',
                        ],
                    ],
                    [
                        'code' => 'SY080002',
                        'name_ar' => 'شدادة',
                        'name_en' => 'Shadadah',
                        'name_tr' => 'Shadadah',
                        'match_names' => [
                        'شدادة',
                        'Shadadah',
                        ],
                    ],
                    [
                        'code' => 'SY080003',
                        'name_ar' => 'مركدة',
                        'name_en' => 'Markada',
                        'name_tr' => 'Markada',
                        'match_names' => [
                        'مركدة',
                        'Markada',
                        ],
                    ],
                    [
                        'code' => 'SY080000',
                        'name_ar' => 'مركز الحسكة',
                        'name_en' => 'Al-Hasakeh',
                        'name_tr' => 'Al-Hasakeh',
                        'match_names' => [
                        'مركز الحسكة',
                        'Al-Hasakeh',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY09',
        'name_ar' => 'دير الزور',
        'name_en' => 'Deir-ez-Zor',
        'name_tr' => 'Deyrizor ili',
        'match_names' => [
            'دير الزور',
            'Deir-ez-Zor',
        ],
        'cities' => [
            [
                'code' => 'SY0902',
                'name_ar' => 'البوكمال',
                'name_en' => 'Abu Kamal',
                'name_tr' => 'Abu Kamal',
                'match_names' => [
                    'البوكمال',
                    'Abu Kamal',
                ],
                'districts' => [
                    [
                        'code' => 'SY090202',
                        'name_ar' => 'الجلاء',
                        'name_en' => 'Jalaa',
                        'name_tr' => 'Jalaa',
                        'match_names' => [
                        'الجلاء',
                        'Jalaa',
                        ],
                    ],
                    [
                        'code' => 'SY090203',
                        'name_ar' => 'سوسة',
                        'name_en' => 'Susat',
                        'name_tr' => 'Susat',
                        'match_names' => [
                        'سوسة',
                        'Susat',
                        ],
                    ],
                    [
                        'code' => 'SY090200',
                        'name_ar' => 'مركز البوكمال',
                        'name_en' => 'Abu Kamal',
                        'name_tr' => 'Abu Kamal',
                        'match_names' => [
                        'مركز البوكمال',
                        'Abu Kamal',
                        ],
                    ],
                    [
                        'code' => 'SY090201',
                        'name_ar' => 'هجين',
                        'name_en' => 'Hajin',
                        'name_tr' => 'Hajin',
                        'match_names' => [
                        'هجين',
                        'Hajin',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0903',
                'name_ar' => 'الميادين',
                'name_en' => 'Al Mayadin',
                'name_tr' => 'Al Mayadin',
                'match_names' => [
                    'الميادين',
                    'Al Mayadin',
                ],
                'districts' => [
                    [
                        'code' => 'SY090301',
                        'name_ar' => 'ذيبان',
                        'name_en' => 'Thiban',
                        'name_tr' => 'Thiban',
                        'match_names' => [
                        'ذيبان',
                        'Thiban',
                        ],
                    ],
                    [
                        'code' => 'SY090302',
                        'name_ar' => 'عشارة',
                        'name_en' => 'Ashara',
                        'name_tr' => 'Ashara',
                        'match_names' => [
                        'عشارة',
                        'Ashara',
                        ],
                    ],
                    [
                        'code' => 'SY090300',
                        'name_ar' => 'مركز الميادين',
                        'name_en' => 'Al Mayadin',
                        'name_tr' => 'Al Mayadin',
                        'match_names' => [
                        'مركز الميادين',
                        'Al Mayadin',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY0901',
                'name_ar' => 'مركز دير الزور',
                'name_en' => 'Deir-ez-Zor',
                'name_tr' => 'Deir-ez-Zor',
                'match_names' => [
                    'مركز دير الزور',
                    'Deir-ez-Zor',
                ],
                'districts' => [
                    [
                        'code' => 'SY090104',
                        'name_ar' => 'التبني',
                        'name_en' => 'Tabni',
                        'name_tr' => 'Tabni',
                        'match_names' => [
                        'التبني',
                        'Tabni',
                        ],
                    ],
                    [
                        'code' => 'SY090102',
                        'name_ar' => 'بصيرة',
                        'name_en' => 'Basira',
                        'name_tr' => 'Basira',
                        'match_names' => [
                        'بصيرة',
                        'Basira',
                        ],
                    ],
                    [
                        'code' => 'SY090105',
                        'name_ar' => 'خشام',
                        'name_en' => 'Khasham',
                        'name_tr' => 'Khasham',
                        'match_names' => [
                        'خشام',
                        'Khasham',
                        ],
                    ],
                    [
                        'code' => 'SY090106',
                        'name_ar' => 'صور',
                        'name_en' => 'Sur',
                        'name_tr' => 'Sur',
                        'match_names' => [
                        'صور',
                        'Sur',
                        ],
                    ],
                    [
                        'code' => 'SY090101',
                        'name_ar' => 'كسرة',
                        'name_en' => 'Kisreh',
                        'name_tr' => 'Kisreh',
                        'match_names' => [
                        'كسرة',
                        'Kisreh',
                        ],
                    ],
                    [
                        'code' => 'SY090100',
                        'name_ar' => 'مركز دير الزور',
                        'name_en' => 'Deir-ez-Zor',
                        'name_tr' => 'Deir-ez-Zor',
                        'match_names' => [
                        'مركز دير الزور',
                        'Deir-ez-Zor',
                        ],
                    ],
                    [
                        'code' => 'SY090103',
                        'name_ar' => 'موحسن',
                        'name_en' => 'Muhasan',
                        'name_tr' => 'Muhasan',
                        'match_names' => [
                        'موحسن',
                        'Muhasan',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY10',
        'name_ar' => 'طرطوس',
        'name_en' => 'Tartous',
        'name_tr' => 'Tartus ili',
        'match_names' => [
            'طرطوس',
            'Tartous',
        ],
        'cities' => [
            [
                'code' => 'SY1005',
                'name_ar' => 'الشيخ بدر',
                'name_en' => 'Sheikh Badr',
                'name_tr' => 'Sheikh Badr',
                'match_names' => [
                    'الشيخ بدر',
                    'Sheikh Badr',
                ],
                'districts' => [
                    [
                        'code' => 'SY100501',
                        'name_ar' => 'برمانة المشايخ',
                        'name_en' => 'Baramanet Elmashayekh',
                        'name_tr' => 'Baramanet Elmashayekh',
                        'match_names' => [
                        'برمانة المشايخ',
                        'Baramanet Elmashayekh',
                        ],
                    ],
                    [
                        'code' => 'SY100502',
                        'name_ar' => 'قمصية',
                        'name_en' => 'Qumseyyeh',
                        'name_tr' => 'Qumseyyeh',
                        'match_names' => [
                        'قمصية',
                        'Qumseyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY100500',
                        'name_ar' => 'مركز الشيخ بدر',
                        'name_en' => 'Sheikh Badr',
                        'name_tr' => 'Sheikh Badr',
                        'match_names' => [
                        'مركز الشيخ بدر',
                        'Sheikh Badr',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1002',
                'name_ar' => 'بانياس',
                'name_en' => 'Banyas',
                'name_tr' => 'Banyas',
                'match_names' => [
                    'بانياس',
                    'Banyas',
                ],
                'districts' => [
                    [
                        'code' => 'SY100201',
                        'name_ar' => 'الروضة',
                        'name_en' => 'Rawda',
                        'name_tr' => 'Rawda',
                        'match_names' => [
                        'الروضة',
                        'Rawda',
                        ],
                    ],
                    [
                        'code' => 'SY100205',
                        'name_ar' => 'الطواحين',
                        'name_en' => 'Tawahin',
                        'name_tr' => 'Tawahin',
                        'match_names' => [
                        'الطواحين',
                        'Tawahin',
                        ],
                    ],
                    [
                        'code' => 'SY100202',
                        'name_ar' => 'العنازة',
                        'name_en' => 'Anaza',
                        'name_tr' => 'Anaza',
                        'match_names' => [
                        'العنازة',
                        'Anaza',
                        ],
                    ],
                    [
                        'code' => 'SY100203',
                        'name_ar' => 'القدموس',
                        'name_en' => 'Qadmous',
                        'name_tr' => 'Qadmous',
                        'match_names' => [
                        'القدموس',
                        'Qadmous',
                        ],
                    ],
                    [
                        'code' => 'SY100206',
                        'name_ar' => 'تالين',
                        'name_en' => 'Taleen',
                        'name_tr' => 'Taleen',
                        'match_names' => [
                        'تالين',
                        'Taleen',
                        ],
                    ],
                    [
                        'code' => 'SY100204',
                        'name_ar' => 'حمام واصل',
                        'name_en' => 'Hamam Wasil',
                        'name_tr' => 'Hamam Wasil',
                        'match_names' => [
                        'حمام واصل',
                        'Hamam Wasil',
                        ],
                    ],
                    [
                        'code' => 'SY100200',
                        'name_ar' => 'مركز بانياس',
                        'name_en' => 'Banyas',
                        'name_tr' => 'Banyas',
                        'match_names' => [
                        'مركز بانياس',
                        'Banyas',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1004',
                'name_ar' => 'دريكيش',
                'name_en' => 'Dreikish',
                'name_tr' => 'Dreikish',
                'match_names' => [
                    'دريكيش',
                    'Dreikish',
                ],
                'districts' => [
                    [
                        'code' => 'SY100401',
                        'name_ar' => 'جنينة رسلان',
                        'name_en' => 'Jneinet Raslan',
                        'name_tr' => 'Jneinet Raslan',
                        'match_names' => [
                        'جنينة رسلان',
                        'Jneinet Raslan',
                        ],
                    ],
                    [
                        'code' => 'SY100402',
                        'name_ar' => 'حمين',
                        'name_en' => 'Hamin',
                        'name_tr' => 'Hamin',
                        'match_names' => [
                        'حمين',
                        'Hamin',
                        ],
                    ],
                    [
                        'code' => 'SY100403',
                        'name_ar' => 'دوير رسلان',
                        'name_en' => 'Dweir Raslan',
                        'name_tr' => 'Dweir Raslan',
                        'match_names' => [
                        'دوير رسلان',
                        'Dweir Raslan',
                        ],
                    ],
                    [
                        'code' => 'SY100400',
                        'name_ar' => 'مركز دريكيش',
                        'name_en' => 'Dreikish',
                        'name_tr' => 'Dreikish',
                        'match_names' => [
                        'مركز دريكيش',
                        'Dreikish',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1003',
                'name_ar' => 'صافيتا',
                'name_en' => 'Safita',
                'name_tr' => 'Safita',
                'match_names' => [
                    'صافيتا',
                    'Safita',
                ],
                'districts' => [
                    [
                        'code' => 'SY100302',
                        'name_ar' => 'البارقية',
                        'name_en' => 'Bariqiyeh',
                        'name_tr' => 'Bariqiyeh',
                        'match_names' => [
                        'البارقية',
                        'Bariqiyeh',
                        ],
                    ],
                    [
                        'code' => 'SY100304',
                        'name_ar' => 'السيسنية',
                        'name_en' => 'Sisniyyeh',
                        'name_tr' => 'Sisniyyeh',
                        'match_names' => [
                        'السيسنية',
                        'Sisniyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY100305',
                        'name_ar' => 'رأس الخشوفة',
                        'name_en' => 'Ras El-Khashufeh',
                        'name_tr' => 'Ras El-Khashufeh',
                        'match_names' => [
                        'رأس الخشوفة',
                        'Ras El-Khashufeh',
                        ],
                    ],
                    [
                        'code' => 'SY100303',
                        'name_ar' => 'سبة',
                        'name_en' => 'Sibbeh',
                        'name_tr' => 'Sibbeh',
                        'match_names' => [
                        'سبة',
                        'Sibbeh',
                        ],
                    ],
                    [
                        'code' => 'SY100300',
                        'name_ar' => 'مركز صافيتا',
                        'name_en' => 'Safita',
                        'name_tr' => 'Safita',
                        'match_names' => [
                        'مركز صافيتا',
                        'Safita',
                        ],
                    ],
                    [
                        'code' => 'SY100301',
                        'name_ar' => 'مشتى الحلو',
                        'name_en' => 'Mashta Elhiu',
                        'name_tr' => 'Mashta Elhiu',
                        'match_names' => [
                        'مشتى الحلو',
                        'Mashta Elhiu',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1000',
                'name_ar' => 'مركز طرطوس',
                'name_en' => 'Tartous',
                'name_tr' => 'Tartous',
                'match_names' => [
                    'مركز طرطوس',
                    'Tartous',
                ],
                'districts' => [
                    [
                        'code' => 'SY100001',
                        'name_ar' => 'أرواد',
                        'name_en' => 'Arwad',
                        'name_tr' => 'Arwad',
                        'match_names' => [
                        'أرواد',
                        'Arwad',
                        ],
                    ],
                    [
                        'code' => 'SY100002',
                        'name_ar' => 'الحميدية',
                        'name_en' => 'Hameidiyyeh',
                        'name_tr' => 'Hameidiyyeh',
                        'match_names' => [
                        'الحميدية',
                        'Hameidiyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY100003',
                        'name_ar' => 'خربة المعزة',
                        'name_en' => 'Kherbet Elma\'aza',
                        'name_tr' => 'Kherbet Elma\'aza',
                        'match_names' => [
                        'خربة المعزة',
                        'Kherbet Elma\'aza',
                        'Kherbet Elmaaza',
                        ],
                    ],
                    [
                        'code' => 'SY100004',
                        'name_ar' => 'سودا خوابي',
                        'name_en' => 'Soda Khawabi',
                        'name_tr' => 'Soda Khawabi',
                        'match_names' => [
                        'سودا خوابي',
                        'Soda Khawabi',
                        ],
                    ],
                    [
                        'code' => 'SY100006',
                        'name_ar' => 'صفصافة',
                        'name_en' => 'Safsafa',
                        'name_tr' => 'Safsafa',
                        'match_names' => [
                        'صفصافة',
                        'Safsafa',
                        ],
                    ],
                    [
                        'code' => 'SY100005',
                        'name_ar' => 'كريمة',
                        'name_en' => 'Kareemeh',
                        'name_tr' => 'Kareemeh',
                        'match_names' => [
                        'كريمة',
                        'Kareemeh',
                        ],
                    ],
                    [
                        'code' => 'SY100000',
                        'name_ar' => 'مركز طرطوس',
                        'name_en' => 'Tartous',
                        'name_tr' => 'Tartous',
                        'match_names' => [
                        'مركز طرطوس',
                        'Tartous',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY11',
        'name_ar' => 'الرقة',
        'name_en' => 'Ar-Raqqa',
        'name_tr' => 'Rakka ili',
        'match_names' => [
            'الرقة',
            'Ar-Raqqa',
        ],
        'cities' => [
            [
                'code' => 'SY1103',
                'name_ar' => 'الثورة',
                'name_en' => 'Ath-Thawrah',
                'name_tr' => 'Ath-Thawrah',
                'match_names' => [
                    'الثورة',
                    'Ath-Thawrah',
                ],
                'districts' => [
                    [
                        'code' => 'SY110302',
                        'name_ar' => 'الجرنية',
                        'name_en' => 'Jurneyyeh',
                        'name_tr' => 'Jurneyyeh',
                        'match_names' => [
                        'الجرنية',
                        'Jurneyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY110301',
                        'name_ar' => 'المنصورة',
                        'name_en' => 'Mansura',
                        'name_tr' => 'Mansura',
                        'match_names' => [
                        'المنصورة',
                        'Mansura',
                        ],
                    ],
                    [
                        'code' => 'SY110300',
                        'name_ar' => 'مركز الثورة',
                        'name_en' => 'Al-Thawrah',
                        'name_tr' => 'Al-Thawrah',
                        'match_names' => [
                        'مركز الثورة',
                        'Al-Thawrah',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1102',
                'name_ar' => 'تل أبيض',
                'name_en' => 'Tell Abiad',
                'name_tr' => 'Tell Abiad',
                'match_names' => [
                    'تل أبيض',
                    'Tell Abiad',
                ],
                'districts' => [
                    [
                        'code' => 'SY110201',
                        'name_ar' => 'سلوك',
                        'name_en' => 'Suluk',
                        'name_tr' => 'Suluk',
                        'match_names' => [
                        'سلوك',
                        'Suluk',
                        ],
                    ],
                    [
                        'code' => 'SY110202',
                        'name_ar' => 'عين عيسى',
                        'name_en' => 'Ein Issa',
                        'name_tr' => 'Ein Issa',
                        'match_names' => [
                        'عين عيسى',
                        'Ein Issa',
                        ],
                    ],
                    [
                        'code' => 'SY110200',
                        'name_ar' => 'مركز تل أبيض',
                        'name_en' => 'Tell Abiad',
                        'name_tr' => 'Tell Abiad',
                        'match_names' => [
                        'مركز تل أبيض',
                        'Tell Abiad',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1101',
                'name_ar' => 'مركز الرقة',
                'name_en' => 'Ar-Raqqa',
                'name_tr' => 'Ar-Raqqa',
                'match_names' => [
                    'مركز الرقة',
                    'Ar-Raqqa',
                ],
                'districts' => [
                    [
                        'code' => 'SY110101',
                        'name_ar' => 'السبخة',
                        'name_en' => 'Sabka',
                        'name_tr' => 'Sabka',
                        'match_names' => [
                        'السبخة',
                        'Sabka',
                        ],
                    ],
                    [
                        'code' => 'SY110102',
                        'name_ar' => 'الكرامة',
                        'name_en' => 'Karama',
                        'name_tr' => 'Karama',
                        'match_names' => [
                        'الكرامة',
                        'Karama',
                        ],
                    ],
                    [
                        'code' => 'SY110100',
                        'name_ar' => 'مركز الرقة',
                        'name_en' => 'Ar-Raqqa',
                        'name_tr' => 'Ar-Raqqa',
                        'match_names' => [
                        'مركز الرقة',
                        'Ar-Raqqa',
                        ],
                    ],
                    [
                        'code' => 'SY110103',
                        'name_ar' => 'معدان',
                        'name_en' => 'Maadan',
                        'name_tr' => 'Maadan',
                        'match_names' => [
                        'معدان',
                        'Maadan',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY12',
        'name_ar' => 'درعا',
        'name_en' => 'Dar\'a',
        'name_tr' => 'Dera ili',
        'match_names' => [
            'درعا',
            'Dar\'a',
            'Dara',
        ],
        'cities' => [
            [
                'code' => 'SY1203',
                'name_ar' => 'ازرع',
                'name_en' => 'Izra\'',
                'name_tr' => 'Izra\'',
                'match_names' => [
                    'ازرع',
                    'Izra\'',
                    'Izra',
                ],
                'districts' => [
                    [
                        'code' => 'SY120302',
                        'name_ar' => 'الحراك',
                        'name_en' => 'Hrak',
                        'name_tr' => 'Hrak',
                        'match_names' => [
                        'الحراك',
                        'Hrak',
                        ],
                    ],
                    [
                        'code' => 'SY120304',
                        'name_ar' => 'الشيخ مسكين',
                        'name_en' => 'Sheikh Miskine',
                        'name_tr' => 'Sheikh Miskine',
                        'match_names' => [
                        'الشيخ مسكين',
                        'Sheikh Miskine',
                        ],
                    ],
                    [
                        'code' => 'SY120305',
                        'name_ar' => 'تسيل',
                        'name_en' => 'Tassil',
                        'name_tr' => 'Tassil',
                        'match_names' => [
                        'تسيل',
                        'Tassil',
                        ],
                    ],
                    [
                        'code' => 'SY120301',
                        'name_ar' => 'جاسم',
                        'name_en' => 'Jasim',
                        'name_tr' => 'Jasim',
                        'match_names' => [
                        'جاسم',
                        'Jasim',
                        ],
                    ],
                    [
                        'code' => 'SY120300',
                        'name_ar' => 'مركز ازرع',
                        'name_en' => 'Izra\'',
                        'name_tr' => 'Izra\'',
                        'match_names' => [
                        'مركز ازرع',
                        'Izra\'',
                        'Izra',
                        ],
                    ],
                    [
                        'code' => 'SY120303',
                        'name_ar' => 'نوى',
                        'name_en' => 'Nawa',
                        'name_tr' => 'Nawa',
                        'match_names' => [
                        'نوى',
                        'Nawa',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1202',
                'name_ar' => 'الصنمين',
                'name_en' => 'As-Sanamayn',
                'name_tr' => 'As-Sanamayn',
                'match_names' => [
                    'الصنمين',
                    'As-Sanamayn',
                ],
                'districts' => [
                    [
                        'code' => 'SY120201',
                        'name_ar' => 'المسمية',
                        'name_en' => 'Masmiyyeh',
                        'name_tr' => 'Masmiyyeh',
                        'match_names' => [
                        'المسمية',
                        'Masmiyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY120202',
                        'name_ar' => 'غباغب',
                        'name_en' => 'Ghabagheb',
                        'name_tr' => 'Ghabagheb',
                        'match_names' => [
                        'غباغب',
                        'Ghabagheb',
                        ],
                    ],
                    [
                        'code' => 'SY120200',
                        'name_ar' => 'مركز الصنمين',
                        'name_en' => 'As-Sanamayn',
                        'name_tr' => 'As-Sanamayn',
                        'match_names' => [
                        'مركز الصنمين',
                        'As-Sanamayn',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1200',
                'name_ar' => 'درعا',
                'name_en' => 'Dar\'a',
                'name_tr' => 'Dar\'a',
                'match_names' => [
                    'درعا',
                    'Dar\'a',
                    'Dara',
                ],
                'districts' => [
                    [
                        'code' => 'SY120006',
                        'name_ar' => 'الجيزة',
                        'name_en' => 'Jizeh',
                        'name_tr' => 'Jizeh',
                        'match_names' => [
                        'الجيزة',
                        'Jizeh',
                        ],
                    ],
                    [
                        'code' => 'SY120003',
                        'name_ar' => 'الشجرة',
                        'name_en' => 'Ash-Shajara',
                        'name_tr' => 'Ash-Shajara',
                        'match_names' => [
                        'الشجرة',
                        'Ash-Shajara',
                        ],
                    ],
                    [
                        'code' => 'SY120007',
                        'name_ar' => 'المسيفرة',
                        'name_en' => 'Mseifra',
                        'name_tr' => 'Mseifra',
                        'match_names' => [
                        'المسيفرة',
                        'Mseifra',
                        ],
                    ],
                    [
                        'code' => 'SY120001',
                        'name_ar' => 'بصرى الشام',
                        'name_en' => 'Busra Esh-Sham',
                        'name_tr' => 'Busra Esh-Sham',
                        'match_names' => [
                        'بصرى الشام',
                        'Busra Esh-Sham',
                        ],
                    ],
                    [
                        'code' => 'SY120002',
                        'name_ar' => 'خربة غزالة',
                        'name_en' => 'Kherbet Ghazala',
                        'name_tr' => 'Kherbet Ghazala',
                        'match_names' => [
                        'خربة غزالة',
                        'Kherbet Ghazala',
                        ],
                    ],
                    [
                        'code' => 'SY120004',
                        'name_ar' => 'داعل',
                        'name_en' => 'Da\'el',
                        'name_tr' => 'Da\'el',
                        'match_names' => [
                        'داعل',
                        'Da\'el',
                        'Dael',
                        ],
                    ],
                    [
                        'code' => 'SY120000',
                        'name_ar' => 'مركز درعا',
                        'name_en' => 'Dar\'a',
                        'name_tr' => 'Dar\'a',
                        'match_names' => [
                        'مركز درعا',
                        'Dar\'a',
                        'Dara',
                        ],
                    ],
                    [
                        'code' => 'SY120005',
                        'name_ar' => 'مزيريب',
                        'name_en' => 'Mzeireb',
                        'name_tr' => 'Mzeireb',
                        'match_names' => [
                        'مزيريب',
                        'Mzeireb',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY13',
        'name_ar' => 'السويداء',
        'name_en' => 'As-Sweida',
        'name_tr' => 'Süveyda ili',
        'match_names' => [
            'السويداء',
            'As-Sweida',
        ],
        'cities' => [
            [
                'code' => 'SY1303',
                'name_ar' => 'شهبا',
                'name_en' => 'Shahba',
                'name_tr' => 'Shahba',
                'match_names' => [
                    'شهبا',
                    'Shahba',
                ],
                'districts' => [
                    [
                        'code' => 'SY130303',
                        'name_ar' => 'الصورة الصغيرة',
                        'name_en' => 'Little Sura',
                        'name_tr' => 'Little Sura',
                        'match_names' => [
                        'الصورة الصغيرة',
                        'Little Sura',
                        ],
                    ],
                    [
                        'code' => 'SY130302',
                        'name_ar' => 'العريقة',
                        'name_en' => 'Ariqa',
                        'name_tr' => 'Ariqa',
                        'match_names' => [
                        'العريقة',
                        'Ariqa',
                        ],
                    ],
                    [
                        'code' => 'SY130301',
                        'name_ar' => 'شقا',
                        'name_en' => 'Shaqa',
                        'name_tr' => 'Shaqa',
                        'match_names' => [
                        'شقا',
                        'Shaqa',
                        ],
                    ],
                    [
                        'code' => 'SY130300',
                        'name_ar' => 'مركز شهبا',
                        'name_en' => 'Shahba',
                        'name_tr' => 'Shahba',
                        'match_names' => [
                        'مركز شهبا',
                        'Shahba',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1302',
                'name_ar' => 'صلخد',
                'name_en' => 'Salkhad',
                'name_tr' => 'Salkhad',
                'match_names' => [
                    'صلخد',
                    'Salkhad',
                ],
                'districts' => [
                    [
                        'code' => 'SY130202',
                        'name_ar' => 'الغارية',
                        'name_en' => 'Gharyeh',
                        'name_tr' => 'Gharyeh',
                        'match_names' => [
                        'الغارية',
                        'Gharyeh',
                        ],
                    ],
                    [
                        'code' => 'SY130201',
                        'name_ar' => 'القريا',
                        'name_en' => 'Qarayya',
                        'name_tr' => 'Qarayya',
                        'match_names' => [
                        'القريا',
                        'Qarayya',
                        ],
                    ],
                    [
                        'code' => 'SY130203',
                        'name_ar' => 'ذيبين',
                        'name_en' => 'Thibeen',
                        'name_tr' => 'Thibeen',
                        'match_names' => [
                        'ذيبين',
                        'Thibeen',
                        ],
                    ],
                    [
                        'code' => 'SY130200',
                        'name_ar' => 'مركز صلخد',
                        'name_en' => 'Salkhad',
                        'name_tr' => 'Salkhad',
                        'match_names' => [
                        'مركز صلخد',
                        'Salkhad',
                        ],
                    ],
                    [
                        'code' => 'SY130204',
                        'name_ar' => 'ملح',
                        'name_en' => 'Milh',
                        'name_tr' => 'Milh',
                        'match_names' => [
                        'ملح',
                        'Milh',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1300',
                'name_ar' => 'مركز السويداء',
                'name_en' => 'As-Sweida',
                'name_tr' => 'As-Sweida',
                'match_names' => [
                    'مركز السويداء',
                    'As-Sweida',
                ],
                'districts' => [
                    [
                        'code' => 'SY130001',
                        'name_ar' => 'المزرعة',
                        'name_en' => 'Mazra\'a',
                        'name_tr' => 'Mazra\'a',
                        'match_names' => [
                        'المزرعة',
                        'Mazra\'a',
                        'Mazraa',
                        ],
                    ],
                    [
                        'code' => 'SY130002',
                        'name_ar' => 'المشنف',
                        'name_en' => 'Mashnaf',
                        'name_tr' => 'Mashnaf',
                        'match_names' => [
                        'المشنف',
                        'Mashnaf',
                        ],
                    ],
                    [
                        'code' => 'SY130000',
                        'name_ar' => 'مركز السويداء',
                        'name_en' => 'As-Sweida',
                        'name_tr' => 'As-Sweida',
                        'match_names' => [
                        'مركز السويداء',
                        'As-Sweida',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'code' => 'SY14',
        'name_ar' => 'القنيطرة',
        'name_en' => 'Quneitra',
        'name_tr' => 'Kuneytira ili',
        'match_names' => [
            'القنيطرة',
            'Quneitra',
        ],
        'cities' => [
            [
                'code' => 'SY1402',
                'name_ar' => 'فيق',
                'name_en' => 'Al Fiq',
                'name_tr' => 'Al Fiq',
                'match_names' => [
                    'فيق',
                    'Al Fiq',
                ],
                'districts' => [
                    [
                        'code' => 'SY140201',
                        'name_ar' => 'البطيحة',
                        'name_en' => 'Al-Butayhah',
                        'name_tr' => 'Al-Butayhah',
                        'match_names' => [
                        'البطيحة',
                        'Al-Butayhah',
                        ],
                    ],
                    [
                        'code' => 'SY140200',
                        'name_ar' => 'مركز فيق',
                        'name_en' => 'Fiq',
                        'name_tr' => 'Fiq',
                        'match_names' => [
                        'مركز فيق',
                        'Fiq',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SY1400',
                'name_ar' => 'مركز القنيطرة',
                'name_en' => 'Quneitra',
                'name_tr' => 'Quneitra',
                'match_names' => [
                    'مركز القنيطرة',
                    'Quneitra',
                ],
                'districts' => [
                    [
                        'code' => 'SY140002',
                        'name_ar' => 'الخشنية',
                        'name_en' => 'Al-Khashniyyeh',
                        'name_tr' => 'Al-Khashniyyeh',
                        'match_names' => [
                        'الخشنية',
                        'Al-Khashniyyeh',
                        ],
                    ],
                    [
                        'code' => 'SY140001',
                        'name_ar' => 'خان أرنبة',
                        'name_en' => 'Khan Arnaba',
                        'name_tr' => 'Khan Arnaba',
                        'match_names' => [
                        'خان أرنبة',
                        'Khan Arnaba',
                        ],
                    ],
                    [
                        'code' => 'SY140000',
                        'name_ar' => 'مركز القنيطرة',
                        'name_en' => 'Quneitra',
                        'name_tr' => 'Quneitra',
                        'match_names' => [
                        'مركز القنيطرة',
                        'Quneitra',
                        ],
                    ],
                    [
                        'code' => 'SY140003',
                        'name_ar' => 'مسعدة',
                        'name_en' => 'Masaada',
                        'name_tr' => 'Masaada',
                        'match_names' => [
                        'مسعدة',
                        'Masaada',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
