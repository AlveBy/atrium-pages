<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Pages',
    'create' => 'Ajouter une page',
    'root' => 'Niveau supérieur',
    'actions' => [
        'subpage' => 'Sous-page',
    ],
    'fields' => [
        'name' => 'Nom',
        'code' => 'Adresse',
        'parent' => 'Page parente',
        'template' => 'Modèle',
        'content' => 'Texte',
        'updated_at' => 'Modifiée',
    ],
    'hints' => [
        'code' => 'La dernière partie de l\'adresse de la page. Les pages d\'un même parent doivent en avoir des différentes.',
    ],
    'templates' => [
        'default' => 'Page',
        'children' => 'Page avec ses sous-pages',
        'landing' => 'Pleine largeur, sans titre',
    ],
    'errors' => [
        'code_taken' => 'Une autre page du même parent a déjà cette adresse.',
    ],
];
