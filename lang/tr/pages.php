<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Sayfalar',
    'create' => 'Sayfa ekle',
    'root' => 'Üst düzey',
    'fields' => [
        'name' => 'Ad',
        'code' => 'Adres',
        'parent' => 'Üst sayfa',
        'template' => 'Şablon',
        'content' => 'Metin',
        'updated_at' => 'Güncellendi',
    ],
    'hints' => [
        'code' => 'Sayfa adresinin son kısmı. Aynı üst sayfanın altındaki sayfaların adresleri farklı olmalıdır.',
    ],
    'templates' => [
        'default' => 'Sayfa',
        'children' => 'Alt sayfalarıyla birlikte sayfa',
        'landing' => 'Tam genişlik, başlıksız',
    ],
    'errors' => [
        'code_taken' => 'Aynı üst sayfanın altında bu adrese sahip başka bir sayfa zaten var.',
    ],
];
