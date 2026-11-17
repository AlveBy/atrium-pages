<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages;

use Atrium\Admin\Cache\Card;
use Atrium\Admin\Cache\RecordCache;
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
 * kept in the record cache until a page is saved or deleted; the pages
 * themselves come from their cards.
 */
final class PageTree
{
    /** How many levels a path may have; also stops a loop in broken data. */
    private const MAX_DEPTH = 20;

    /** @var array<string,array{paths:array<int,string>,ids:array<string,int>,parents:array<int,int|null>,children:array<int,array<int,int>>,languages:array<int,array<int,string>>}> */
    private array $trees = [];

    public function __construct(
        private readonly Sites $sites,
        private readonly ContentLanguages $languages,
    ) {}

    /**
     * The visible page at a path below the site root ("about/team"), read
     * in a language (the current one by default).
     */
    public function find(string $path, ?string $language = null): ?Page
    {
        $id = $this->tree()['ids'][trim($path, '/')] ?? null;

        return $id === null ? null : $this->load([$id], $language)->first();
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

        if ($path === null || ! $this->languages->has($language) || ! in_array($language, $this->tree()['languages'][$page->getKey()] ?? [], true)) {
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

        while ($id !== null) {
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
    private function load(array $ids, ?string $language = null): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        /** @var Collection<int,Page> */
        return collect(Page::cachedMany($ids, $language))->map(fn (Card $card): Page => $card->model(Page::class))->values();
    }

    /**
     * @return array{paths:array<int,string>,ids:array<string,int>,parents:array<int,int|null>,children:array<int,array<int,int>>,languages:array<int,array<int,string>>}
     */
    private function tree(): array
    {
        $key = $this->sites->isResolved() ? $this->sites->current()->getKey() : '*';

        return $this->trees[$key] ??= app(RecordCache::class)->remember(Page::class, 'page-tree', fn (): array => $this->build());
    }

    /**
     * @return array{paths:array<int,string>,ids:array<string,int>,parents:array<int,int|null>,children:array<int,array<int,int>>,languages:array<int,array<int,string>>}
     */
    private function build(): array
    {
        $rows = Page::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'code', 'active', 'name'])
            ->keyBy('id');

        $paths = [];
        $parents = [];
        $children = [];
        $languages = [];

        foreach ($rows as $id => $row) {
            $path = $this->pathOf($row, $rows);

            if ($path !== null) {
                $paths[$id] = $path;
                $parents[$id] = $row->parent_id;
                // The languages the page has a name in: it has an address in those.
                $languages[$id] = array_keys(array_filter((array) $row->getTranslations('name'), static fn (mixed $name): bool => (string) $name !== ''));
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

        return ['paths' => $paths, 'ids' => $ids, 'parents' => $parents, 'children' => $children, 'languages' => $languages];
    }

    /**
     * The address of a page, or null when the page is hidden: off, without
     * a code, too deep, or standing on a parent that is gone or belongs to
     * another site.
     *
     * @param  Collection<int,Page>  $rows
     */
    private function pathOf(Page $row, Collection $rows): ?string
    {
        $parts = [];
        $cursor = $row;

        for ($depth = 0; $cursor !== null; $depth++) {
            if ($depth >= self::MAX_DEPTH || ! $cursor->active || $cursor->code === '') {
                return null;
            }

            array_unshift($parts, $cursor->code);
            $parentId = $cursor->parent_id;

            if ($parentId !== null && ! $rows->has($parentId)) {
                return null;
            }

            $cursor = $parentId === null ? null : $rows->get($parentId);
        }

        return implode('/', $parts);
    }
}
