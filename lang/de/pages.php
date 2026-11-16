<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'Seiten',
    'create' => 'Seite hinzufügen',
    'root' => 'Oberste Ebene',
    'actions' => [
        'subpage' => 'Unterseite',
    ],
    'fields' => [
        'name' => 'Name',
        'code' => 'Adresse',
        'parent' => 'Übergeordnete Seite',
        'template' => 'Vorlage',
        'content' => 'Text',
        'updated_at' => 'Geändert',
    ],
    'hints' => [
        'code' => 'Der letzte Teil der Seitenadresse. Seiten unter derselben übergeordneten Seite brauchen verschiedene.',
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
