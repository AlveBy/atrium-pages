<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'ページ',
    'create' => 'ページを追加',
    'root' => '最上位',
    'actions' => [
        'subpage' => 'サブページ',
        'show' => '表示',
        'hide' => '非表示',
        'shown' => ':count 件のページをサイトに表示しました。',
        'hidden' => ':count 件のページをサイトから非表示にしました。',
    ],
    'fields' => [
        'name' => '名前',
        'code' => 'アドレス',
        'parent' => '親ページ',
        'template' => 'テンプレート',
        'content' => '本文',
        'updated_at' => '更新日時',
    ],
    'hints' => [
        'code' => 'ページのアドレスの最後の部分です。同じ親ページの下のページは異なるアドレスが必要です。',
    ],
    'templates' => [
        'default' => 'ページ',
        'children' => '子ページ一覧付きのページ',
        'landing' => '全幅、見出しなし',
    ],
    'errors' => [
        'code_taken' => '同じ親ページの下に、このアドレスのページがすでにあります。',
    ],
];
