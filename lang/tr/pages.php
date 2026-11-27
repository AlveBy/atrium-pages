<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Sayfalar',
    'create' => 'Sayfa ekle',
    'root' => 'Üst düzey',
    'actions' => [
        'subpage' => 'Alt sayfa',
        'show' => 'Göster',
        'hide' => 'Gizle',
        'shown' => ':count sayfa sitede gösteriliyor.',
        'hidden' => ':count sayfa siteden gizlendi.',
    ],
    'fields' => [
        'name' => 'Ad',
        'code' => 'Adres',
        'parent' => 'Üst sayfa',
        'template' => 'Şablon',
        'content' => 'Metin',
        'layout' => 'Bölümler ve bloklar',
        'updated_at' => 'Güncellendi',
    ],
    'hints' => [
        'code' => 'Sayfa adresinin son kısmı. Aynı üst sayfanın altındaki sayfaların adresleri farklı olmalıdır.',
        'layout' => 'Bölümleri olan sayfa metinden değil, bölümlerden gösterilir. Metni yeniden göstermek için tüm bölümleri silin.',
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
