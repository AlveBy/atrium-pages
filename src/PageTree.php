<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Support\Sites;
use Atrium\Pages\Models\Page;
use Illuminate\Support\Collection;

/**
 * The pages of the current site as a tree: which ones a visitor may open,
 * at what path, and in which languages.
 *
 * A page is visible when it and every page above it are active: hiding a
 * page hides its branch. The tree is read once (one query, no texts) and
 * kept until a page is saved or deleted.
 */
final class PageTree
{
    /** How many levels a path may have; also stops a loop in broken data. */
    private const MAX_DEPTH = 20;

    /** @var array<string,array{paths:array<int,string>,ids:array<string,int>,parents:array<int,int|null>,children:array<int,array<int,int>>}> */
    private array $trees = [];

    public function __construct(
        private readonly Sites $sites,
        private readonly ContentLanguages $languages,
    ) {}

    /**
     * The visible page at a path below the site root ("about/team").
     */
    public function find(string $path): ?Page
    {
        $id = $this->tree()['ids'][trim($path, '/')] ?? null;

        return $id === null ? null : Page::query()->find($id);
    }

    /**
     * The path of a visible page, or null for a hidden one.
     */
    public function path(Page $page): ?string
    {
        return $this->tree()['paths'][$page->getKey()] ?? null;
    }

    /**
     * The page's address in a language, or null when it has none there: a
     * hidden page, or one with no name written in that language.
     */
    public function url(Page $page, ?string $language = null): ?string
    {
        $language ??= app()->getLocale();
        $path = $this->path($page);

        if ($path === null || ! $this->languages->has($language) || (string) $page->getTranslation('name', $language, false) === '') {
            return null;
        }

        $prefix = $language === $this->languages->default() ? '' : $language.'/';

        return route('atrium.pages.show', ['path' => $prefix.$path]);
    }

    /**
     * @return array<string,string> language => address, for every language the page is written in
     */
    public function urls(Page $page): array
    {
        $urls = [];

        foreach ($this->languages->codes() as $code) {
            if (($url = $this->url($page, $code)) !== null) {
                $urls[$code] = $url;
            }
        }

        return $urls;
    }

    /**
     * The visible pages above this one, from the top.
     *
     * @return Collection<int,Page>
     */
    public function ancestors(Page $page): Collection
    {
        $tree = $this->tree();
        $ids = [];
        $id = $tree['parents'][$page->getKey()] ?? null;

        for ($depth = 0; $id !== null && $depth < self::MAX_DEPTH; $depth++) {
            array_unshift($ids, $id);
            $id = $tree['parents'][$id] ?? null;
        }

        return $this->load($ids);
    }

    /**
     * The visible pages right below this one, in the editors' order.
     *
     * @return Collection<int,Page>
     */
    public function children(Page $page): Collection
    {
        return $this->load($this->tree()['children'][$page->getKey()] ?? []);
    }

    public function flush(): void
    {
        $this->trees = [];
    }

    /**
     * @param  array<int,int>  $ids
     * @return Collection<int,Page>
     */
    private function load(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        $pages = Page::query()->whereKey($ids)->get()->keyBy('id');

        return collect($ids)->map(fn (int $id): ?Page => $pages->get($id))->filter()->values();
    }

    /**
     * @return array{paths:array<int,string>,ids:array<string,int>,parents:array<int,int|null>,children:array<int,array<int,int>>}
     */
    private function tree(): array
    {
        $key = $this->sites->isResolved() ? (string) $this->sites->current()->getKey() : '*';

        return $this->trees[$key] ??= $this->build();
    }

    /**
     * @return array{paths:array<int,string>,ids:array<string,int>,parents:array<int,int|null>,children:array<int,array<int,int>>}
     */
    private function build(): array
    {
        $rows = Page::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'code', 'active'])
            ->keyBy('id');

        $paths = [];
        $parents = [];
        $children = [];

        foreach ($rows as $id => $row) {
            $parts = [];
            $cursor = $row;
            $visible = true;

            for ($depth = 0; $cursor !== null; $depth++) {
                if ($depth >= self::MAX_DEPTH || ! $cursor->active || $cursor->code === '') {
                    $visible = false;

                    break;
                }

                array_unshift($parts, $cursor->code);
                $parentId = $cursor->parent_id;

                if ($parentId !== null && ! $rows->has($parentId)) {
                    // The parent is gone or belongs to another site.
                    $visible = false;

                    break;
                }

                $cursor = $parentId === null ? null : $rows->get($parentId);
            }

            if ($visible) {
                $paths[$id] = implode('/', $parts);
                $parents[$id] = $row->parent_id;
            }
        }

        foreach ($rows as $id => $row) {
            if (isset($paths[$id]) && $row->parent_id !== null) {
                $children[$row->parent_id][] = $id;
            }
        }

        // Two visible pages on one path cannot both open; the first in the
        // editors' order wins, as it does in the list.
        $ids = [];

        foreach ($paths as $id => $path) {
            $ids[$path] ??= $id;
        }

        return ['paths' => $paths, 'ids' => $ids, 'parents' => $parents, 'children' => $children];
    }
}
