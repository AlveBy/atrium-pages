<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Беттер',
    'create' => 'Бет қосу',
    'root' => 'Жоғарғы деңгей',
    'actions' => [
        'subpage' => 'Ішкі бет',
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
