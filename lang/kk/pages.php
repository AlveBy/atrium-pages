<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Беттер',
    'create' => 'Бет қосу',
    'root' => 'Жоғарғы деңгей',
    'actions' => [
        'subpage' => 'Ішкі бет',
        'show' => 'Көрсету',
        'hide' => 'Жасыру',
        'shown' => ':count бет сайтта көрсетілді.',
        'hidden' => ':count бет сайттан жасырылды.',
    ],
    'fields' => [
        'name' => 'Атауы',
        'code' => 'Мекенжай',
        'parent' => 'Ата-бет',
        'template' => 'Үлгі',
        'content' => 'Мәтін',
        'updated_at' => 'Өзгертілді',
    ],
    'hints' => [
        'code' => 'Бет мекенжайының соңғы бөлігі. Бір ата-беттің ішіндегі беттердің мекенжайлары әртүрлі болуы керек.',
    ],
    'templates' => [
        'default' => 'Бет',
        'children' => 'Ішкі беттер тізімі бар бет',
        'landing' => 'Толық ені, тақырыпсыз',
    ],
    'errors' => [
        'code_taken' => 'Осы ата-беттің ішінде мұндай мекенжайы бар бет бұрыннан бар.',
    ],
];
