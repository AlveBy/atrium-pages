<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Pages',
    'create' => 'Add page',
    'root' => 'Top level',
    'actions' => [
        'subpage' => 'Subpage',
        'show' => 'Show',
        'hide' => 'Hide',
        'shown' => ':count page is shown on the site.|:count pages are shown on the site.',
        'hidden' => ':count page is hidden from the site.|:count pages are hidden from the site.',
    ],
    'fields' => [
        'name' => 'Name',
        'code' => 'Address',
        'parent' => 'Parent page',
        'template' => 'Template',
        'content' => 'Text',
        'updated_at' => 'Updated',
    ],
    'hints' => [
        'code' => 'The last part of the page address. Pages under one parent need different ones.',
    ],
    'templates' => [
        'default' => 'Page',
        'children' => 'Page with its subpages',
        'landing' => 'Full width, no heading',
    ],
    'errors' => [
        'code_taken' => 'Another page under the same parent already has this address.',
    ],
];
