<?php


return [
    [
        'code' => 'TR-34',
        'name_ar' => 'إسطنبول',
        'name_en' => 'Istanbul',
        'name_tr' => 'İstanbul',
        'cities' => [
            [
                'code' => 'TR-34-FTH',
                'name_ar' => 'فاتح',
                'name_en' => 'Fatih',
                'name_tr' => 'Fatih',
                'match_names' => [
                    'Fatih',
                    'Ayasofya',
                    'Aya Sofya',
                    'Hagia Sophia',
                    'Ayasofya Camii',
                    'Hagia Sophia Grand Mosque',
                    'Sultan Ahmet',
                    'Sultanahmet',
                ],
                'districts' => [
                    ['code' => 'TR-34-FTH-01', 'name_ar' => 'المركز', 'name_en' => 'Center', 'name_tr' => 'Merkez'],
                    [
                        'code' => 'TR-34-FTH-02',
                        'name_ar' => 'السلطان أحمد',
                        'name_en' => 'Sultanahmet',
                        'name_tr' => 'Sultanahmet',
                        'match_names' => [
                            'Sultanahmet',
                            'Sultan Ahmet',
                            'Ayasofya',
                            'Aya Sofya',
                            'Hagia Sophia',
                            'Ayasofya Camii',
                            'Hagia Sophia Grand Mosque',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'TR-34-KDY',
                'name_ar' => 'كاديكوي',
                'name_en' => 'Kadıköy',
                'name_tr' => 'Kadıköy',
                'districts' => [
                    ['code' => 'TR-34-KDY-01', 'name_ar' => 'المركز', 'name_en' => 'Center', 'name_tr' => 'Merkez'],
                ],
            ],
        ],
    ],
    [
        'code' => 'TR-06',
        'name_ar' => 'أنقرة',
        'name_en' => 'Ankara',
        'name_tr' => 'Ankara',
        'cities' => [
            [
                'code' => 'TR-06-CNK',
                'name_ar' => 'تشانكايا',
                'name_en' => 'Çankaya',
                'name_tr' => 'Çankaya',
                'districts' => [
                    ['code' => 'TR-06-CNK-01', 'name_ar' => 'المركز', 'name_en' => 'Center', 'name_tr' => 'Merkez'],
                ],
            ],
        ],
    ],
    [
        'code' => 'TR-35',
        'name_ar' => 'إزمير',
        'name_en' => 'İzmir',
        'name_tr' => 'İzmir',
        'cities' => [
            [
                'code' => 'TR-35-KON',
                'name_ar' => 'كوناك',
                'name_en' => 'Konak',
                'name_tr' => 'Konak',
                'districts' => [
                    ['code' => 'TR-35-KON-01', 'name_ar' => 'المركز', 'name_en' => 'Center', 'name_tr' => 'Merkez'],
                ],
            ],
        ],
    ],
];
