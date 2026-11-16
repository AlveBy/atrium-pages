<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Strony',
    'create' => 'Dodaj stronę',
    'root' => 'Najwyższy poziom',
    'fields' => [
        'name' => 'Nazwa',
        'code' => 'Adres',
        'parent' => 'Strona nadrzędna',
        'template' => 'Szablon',
        'content' => 'Tekst',
        'updated_at' => 'Zmieniona',
    ],
    'hints' => [
        'code' => 'Ostatnia część adresu strony. Strony w obrębie jednej strony nadrzędnej muszą mieć różne adresy.',
    ],
    'templates' => [
        'default' => 'Strona',
        'children' => 'Strona z listą podstron',
        'landing' => 'Pełna szerokość, bez nagłówka',
    ],
    'errors' => [
        'code_taken' => 'Inna strona w obrębie tej samej strony nadrzędnej ma już ten adres.',
    ],
];
