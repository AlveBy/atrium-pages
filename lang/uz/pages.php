<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Sahifalar',
    'create' => 'Sahifa qo\'shish',
    'root' => 'Yuqori daraja',
    'actions' => [
        'subpage' => 'Ichki sahifa',
    ],
    'fields' => [
        'name' => 'Nomi',
        'code' => 'Manzil',
        'parent' => 'Ota sahifa',
        'template' => 'Shablon',
        'content' => 'Matn',
        'updated_at' => 'O\'zgartirilgan',
    ],
    'hints' => [
        'code' => 'Sahifa manzilining oxirgi qismi. Bitta ota sahifa ichidagi sahifalarning manzillari har xil bo\'lishi kerak.',
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
