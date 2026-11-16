<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Старонкі',
    'create' => 'Дадаць старонку',
    'root' => 'Верхні ўзровень',
    'fields' => [
        'name' => 'Назва',
        'code' => 'Адрас',
        'parent' => 'Бацькоўская старонка',
        'template' => 'Шаблон',
        'content' => 'Тэкст',
        'updated_at' => 'Зменена',
    ],
    'hints' => [
        'code' => 'Апошняя частка адраса старонкі. Старонкі ўнутры адной бацькоўскай павінны мець розныя адрасы.',
    ],
    'templates' => [
        'default' => 'Старонка',
        'children' => 'Старонка са спісам укладзеных',
        'landing' => 'На ўсю шырыню, без загалоўка',
    ],
    'errors' => [
        'code_taken' => 'Старонка з такім адрасам ужо ёсць унутры той жа бацькоўскай.',
    ],
];
