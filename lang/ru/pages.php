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
        'show' => 'Показать',
        'hide' => 'Скрыть',
        'shown' => 'Показана на сайте :count страница.|Показаны на сайте :count страницы.|Показаны на сайте :count страниц.',
        'hidden' => 'Скрыта с сайта :count страница.|Скрыты с сайта :count страницы.|Скрыты с сайта :count страниц.',
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
