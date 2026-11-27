<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Sahifalar',
    'create' => 'Sahifa qo\'shish',
    'root' => 'Yuqori daraja',
    'actions' => [
        'subpage' => 'Ichki sahifa',
        'show' => 'Ko\'rsatish',
        'hide' => 'Yashirish',
        'shown' => ':count ta sahifa saytda ko\'rsatildi.',
        'hidden' => ':count ta sahifa saytdan yashirildi.',
    ],
    'fields' => [
        'name' => 'Nomi',
        'code' => 'Manzil',
        'parent' => 'Ota sahifa',
        'template' => 'Shablon',
        'content' => 'Matn',
        'layout' => 'Seksiyalar va bloklar',
        'updated_at' => 'O\'zgartirilgan',
    ],
    'hints' => [
        'code' => 'Sahifa manzilining oxirgi qismi. Bitta ota sahifa ichidagi sahifalarning manzillari har xil bo\'lishi kerak.',
        'layout' => 'Seksiyalari bor sahifa matn oʻrniga ulardan koʻrsatiladi. Matnni yana koʻrsatish uchun barcha seksiyalarni oʻchiring.',
    ],
    'templates' => [
        'default' => 'Sahifa',
        'children' => 'Ichki sahifalar ro\'yxati bilan sahifa',
        'landing' => 'To\'liq kenglikda, sarlavhasiz',
    ],
    'errors' => [
        'code_taken' => 'Shu ota sahifa ichida bunday manzilli sahifa allaqachon bor.',
    ],
];
