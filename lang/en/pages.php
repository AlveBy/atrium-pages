<?php

declare(strict_types=1);

return [
    'module' => 'Pages',
    'create' => 'Add page',
    'root' => 'Top level',
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
