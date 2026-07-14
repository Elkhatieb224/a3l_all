<?php

return [
    'required' => ':attribute alanı gereklidir.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'min' => [
        'string' => ':attribute en az :min karakter olmalıdır.',
        'numeric' => ':attribute en az :min olmalıdır.',
    ],
    'max' => [
        'string' => ':attribute en fazla :max karakter olabilir.',
        'numeric' => ':attribute en fazla :max olabilir.',
        'file' => ':attribute en fazla :max kilobayt olabilir.',
        'array' => ':attribute en fazla :max öğe içerebilir.',
    ],
    'unique' => ':attribute zaten kullanılıyor.',
    'confirmed' => ':attribute onayı eşleşmiyor.',
    'string' => ':attribute metin olmalıdır.',
    'numeric' => ':attribute sayı olmalıdır.',
    'integer' => ':attribute tam sayı olmalıdır.',
    'in' => 'Seçilen :attribute geçersiz.',
    'exists' => 'Seçilen :attribute mevcut değil.',
    'image' => ':attribute resim olmalıdır.',
    'uploaded' => ':attribute yüklenemedi. Daha küçük bir görsel deneyin (tercihen 5 MB altı).',
    'mimes' => ':attribute şu türde bir dosya olmalıdır: :values.',
    'date' => ':attribute geçerli bir tarih değil.',
    'after' => ':attribute, :date tarihinden sonra olmalıdır.',
    'before' => ':attribute, :date tarihinden önce olmalıdır.',

    'attributes' => [
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'şifre',
        'password_confirmation' => 'şifre onayı',
        'phone' => 'telefon',
        'title' => 'başlık',
        'description' => 'açıklama',
        'price' => 'fiyat',
        'location' => 'konum',
        'category_id' => 'kategori',
        'image' => 'resim',
        'images' => 'ilan görselleri',
        'images.*' => 'ilan görseli',
    ],
];

