<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Сторінки',
    'create' => 'Додати сторінку',
    'root' => 'Верхній рівень',
    'actions' => [
        'subpage' => 'Підсторінка',
        'show' => 'Показати',
        'hide' => 'Приховати',
        'shown' => 'Показано на сайті :count сторінку.|Показано на сайті :count сторінки.|Показано на сайті :count сторінок.',
        'hidden' => 'Приховано з сайту :count сторінку.|Приховано з сайту :count сторінки.|Приховано з сайту :count сторінок.',
    ],
    'fields' => [
        'name' => 'Назва',
        'code' => 'Адреса',
        'parent' => 'Батьківська сторінка',
        'template' => 'Шаблон',
        'content' => 'Текст',
        'layout' => 'Секції та блоки',
        'updated_at' => 'Змінено',
    ],
    'hints' => [
        'code' => 'Остання частина адреси сторінки. Сторінки всередині однієї батьківської повинні мати різні адреси.',
        'layout' => 'Сторінка із секціями показується з них, а не з тексту. Видаліть усі секції, щоб знову показувався текст.',
    ],
    'templates' => [
        'default' => 'Сторінка',
        'children' => 'Сторінка зі списком вкладених',
        'landing' => 'На всю ширину, без заголовка',
    ],
    'errors' => [
        'code_taken' => 'Сторінка з такою адресою вже є всередині тієї ж батьківської.',
    ],
];
