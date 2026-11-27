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
        'show' => 'Mostra',
        'hide' => 'Nascondi',
        'shown' => ':count pagina è visibile sul sito.|:count pagine sono visibili sul sito.',
        'hidden' => ':count pagina è nascosta dal sito.|:count pagine sono nascoste dal sito.',
    ],
    'fields' => [
        'name' => 'Nome',
        'code' => 'Indirizzo',
        'parent' => 'Pagina padre',
        'template' => 'Modello',
        'content' => 'Testo',
        'layout' => 'Sezioni e blocchi',
        'updated_at' => 'Modificata',
    ],
    'hints' => [
        'code' => 'L\'ultima parte dell\'indirizzo della pagina. Le pagine sotto lo stesso padre devono averne di diversi.',
        'layout' => 'Una pagina con sezioni viene mostrata a partire da esse e non dal testo. Elimina tutte le sezioni per mostrare di nuovo il testo.',
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
