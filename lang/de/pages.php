<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Seiten',
    'create' => 'Seite hinzufügen',
    'root' => 'Oberste Ebene',
    'actions' => [
        'subpage' => 'Unterseite',
        'show' => 'Anzeigen',
        'hide' => 'Ausblenden',
        'shown' => ':count Seite wird auf der Website angezeigt.|:count Seiten werden auf der Website angezeigt.',
        'hidden' => ':count Seite ist auf der Website ausgeblendet.|:count Seiten sind auf der Website ausgeblendet.',
    ],
    'fields' => [
        'name' => 'Name',
        'code' => 'Adresse',
        'parent' => 'Übergeordnete Seite',
        'template' => 'Vorlage',
        'content' => 'Text',
        'layout' => 'Abschnitte und Blöcke',
        'updated_at' => 'Geändert',
    ],
    'hints' => [
        'code' => 'Der letzte Teil der Seitenadresse. Seiten unter derselben übergeordneten Seite brauchen verschiedene.',
        'layout' => 'Eine Seite mit Abschnitten wird aus ihnen angezeigt, nicht aus dem Text. Löschen Sie alle Abschnitte, um wieder den Text anzuzeigen.',
    ],
    'templates' => [
        'default' => 'Seite',
        'children' => 'Seite mit ihren Unterseiten',
        'landing' => 'Volle Breite, ohne Überschrift',
    ],
    'errors' => [
        'code_taken' => 'Unter derselben übergeordneten Seite gibt es schon eine Seite mit dieser Adresse.',
    ],
];
