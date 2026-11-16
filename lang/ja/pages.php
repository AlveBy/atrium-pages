<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => 'ページ',
    'create' => 'ページを追加',
    'root' => '最上位',
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
