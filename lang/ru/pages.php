<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Страницы',
    'create' => 'Добавить страницу',
    'root' => 'Верхний уровень',
    'actions' => [
        'subpage' => 'Подстраница',
    ],
    'fields' => [
        'name' => 'Название',
        'code' => 'Адрес',
        'parent' => 'Родительская страница',
        'template' => 'Шаблон',
        'content' => 'Текст',
        'updated_at' => 'Изменена',
    ],
    'hints' => [
        'code' => 'Последняя часть адреса страницы. У страниц внутри одной родительской адреса должны различаться.',
    ],
    'templates' => [
        'default' => 'Страница',
        'children' => 'Страница со списком вложенных',
        'landing' => 'Во всю ширину, без заголовка',
    ],
    'errors' => [
        'code_taken' => 'Страница с таким адресом уже есть внутри той же родительской.',
    ],
];
