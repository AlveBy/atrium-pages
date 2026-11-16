<?php

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.
return [
    'module' => '页面',
    'create' => '添加页面',
    'root' => '顶层',
    'actions' => [
        'subpage' => '子页面',
    ],
    'fields' => [
        'name' => '名称',
        'code' => '地址',
        'parent' => '上级页面',
        'template' => '模板',
        'content' => '正文',
        'updated_at' => '更新时间',
    ],
    'hints' => [
        'code' => '页面地址的最后一部分。同一上级页面下的页面地址必须不同。',
    ],
    'templates' => [
        'default' => '页面',
        'children' => '带子页面列表的页面',
        'landing' => '全宽，无标题',
    ],
    'errors' => [
        'code_taken' => '同一上级页面下已有使用此地址的页面。',
    ],
];
