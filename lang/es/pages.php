<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Páginas',
    'create' => 'Añadir página',
    'root' => 'Nivel superior',
    'fields' => [
        'name' => 'Nombre',
        'code' => 'Dirección',
        'parent' => 'Página padre',
        'template' => 'Plantilla',
        'content' => 'Texto',
        'updated_at' => 'Modificada',
    ],
    'hints' => [
        'code' => 'La última parte de la dirección de la página. Las páginas bajo el mismo padre necesitan direcciones distintas.',
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
