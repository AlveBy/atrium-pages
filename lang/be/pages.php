<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Старонкі',
    'create' => 'Дадаць старонку',
    'root' => 'Верхні ўзровень',
    'actions' => [
        'subpage' => 'Падстаронка',
        'show' => 'Паказаць',
        'hide' => 'Схаваць',
        'shown' => 'Паказана на сайце :count старонка.|Паказаны на сайце :count старонкі.|Паказана на сайце :count старонак.',
        'hidden' => 'Схавана з сайта :count старонка.|Схаваны з сайта :count старонкі.|Схавана з сайта :count старонак.',
    ],
    'fields' => [
        'name' => 'Назва',
        'code' => 'Адрас',
        'parent' => 'Бацькоўская старонка',
        'template' => 'Шаблон',
        'content' => 'Тэкст',
        'layout' => 'Секцыі і блокі',
        'updated_at' => 'Зменена',
    ],
    'hints' => [
        'code' => 'Апошняя частка адраса старонкі. Старонкі ўнутры адной бацькоўскай павінны мець розныя адрасы.',
        'layout' => 'Старонка з секцыямі паказваецца з іх, а не з тэксту. Выдаліце ўсе секцыі, каб зноў паказваўся тэкст.',
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
