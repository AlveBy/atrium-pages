<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Páginas',
    'create' => 'Añadir página',
    'root' => 'Nivel superior',
    'actions' => [
        'subpage' => 'Subpágina',
        'show' => 'Mostrar',
        'hide' => 'Ocultar',
        'shown' => ':count página se muestra en el sitio.|:count páginas se muestran en el sitio.',
        'hidden' => ':count página está oculta en el sitio.|:count páginas están ocultas en el sitio.',
    ],
    'fields' => [
        'name' => 'Nombre',
        'code' => 'Dirección',
        'parent' => 'Página padre',
        'template' => 'Plantilla',
        'content' => 'Texto',
        'layout' => 'Secciones y bloques',
        'updated_at' => 'Modificada',
    ],
    'hints' => [
        'code' => 'La última parte de la dirección de la página. Las páginas bajo el mismo padre necesitan direcciones distintas.',
        'layout' => 'Una página con secciones se muestra a partir de ellas y no del texto. Elimine todas las secciones para volver a mostrar el texto.',
    ],
    'templates' => [
        'default' => 'Página',
        'children' => 'Página con sus subpáginas',
        'landing' => 'Ancho completo, sin título',
    ],
    'errors' => [
        'code_taken' => 'Otra página bajo el mismo padre ya tiene esta dirección.',
    ],
];
