<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Pagine',
    'create' => 'Aggiungi pagina',
    'root' => 'Livello superiore',
    'actions' => [
        'subpage' => 'Sottopagina',
    ],
    'fields' => [
        'name' => 'Nome',
        'code' => 'Indirizzo',
        'parent' => 'Pagina padre',
        'template' => 'Modello',
        'content' => 'Testo',
        'updated_at' => 'Modificata',
    ],
    'hints' => [
        'code' => 'L\'ultima parte dell\'indirizzo della pagina. Le pagine sotto lo stesso padre devono averne di diversi.',
    ],
    'templates' => [
        'default' => 'Pagina',
        'children' => 'Pagina con le sue sottopagine',
        'landing' => 'Larghezza piena, senza titolo',
    ],
    'errors' => [
        'code_taken' => 'Un\'altra pagina sotto lo stesso padre ha già questo indirizzo.',
    ],
];
