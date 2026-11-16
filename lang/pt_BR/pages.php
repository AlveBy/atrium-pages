<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Páginas',
    'create' => 'Adicionar página',
    'root' => 'Nível superior',
    'actions' => [
        'subpage' => 'Subpágina',
    ],
    'fields' => [
        'name' => 'Nome',
        'code' => 'Endereço',
        'parent' => 'Página pai',
        'template' => 'Modelo',
        'content' => 'Texto',
        'updated_at' => 'Alterada',
    ],
    'hints' => [
        'code' => 'A última parte do endereço da página. Páginas sob o mesmo pai precisam de endereços diferentes.',
    ],
    'templates' => [
        'default' => 'Página',
        'children' => 'Página com suas subpáginas',
        'landing' => 'Largura total, sem título',
    ],
    'errors' => [
        'code_taken' => 'Outra página sob o mesmo pai já tem este endereço.',
    ],
];
