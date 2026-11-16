<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Public pages
    |--------------------------------------------------------------------------
    |
    | A page opens at the path of its codes from the root of the site:
    | /about/team, /de/about/team. The route is a fallback, so every route
    | of the app, the admin and the theme is matched first. Set to false to
    | draw the pages with routes of your own.
    |
    */
    'routes' => (bool) env('ATRIUM_PAGES_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Key => Blade view; the editor picks one in the page form. A view gets
    | $page (the Page), $title, $content, $children (visible subpages) and
    | the theme layout's variables. The label is the translation
    | atrium-pages::pages.templates.{key}, or the key itself.
    |
    */
    'templates' => [
        'default' => 'atrium-pages::templates.default',
        'children' => 'atrium-pages::templates.children',
        'landing' => 'atrium-pages::templates.landing',
    ],
];
