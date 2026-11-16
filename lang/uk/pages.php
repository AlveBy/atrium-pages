<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Сторінки',
    'create' => 'Додати сторінку',
    'root' => 'Верхній рівень',
    'fields' => [
        'name' => 'Назва',
        'code' => 'Адреса',
        'parent' => 'Батьківська сторінка',
        'template' => 'Шаблон',
        'content' => 'Текст',
        'updated_at' => 'Змінено',
    ],
    'hints' => [
        'code' => 'Остання частина адреси сторінки. Сторінки всередині однієї батьківської повинні мати різні адреси.',
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
