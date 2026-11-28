<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages;

use Atrium\Admin\Builder\Layout;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Pages\Models\Page;
use Atrium\Theme\Builder\LayoutRenderer;
use Atrium\Theme\Pages as ThemePages;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

/**
 * The view of a page as its address shows it: the template its editor
 * picked inside the theme's layout. The site and the page editor's preview
 * both draw a page with it.
 */
final class PageView
{
    public function __construct(
        private readonly PageTree $tree,
        private readonly ContentLanguages $languages,
        private readonly ThemePages $theme,
        private readonly LayoutRenderer $layouts,
    ) {}

    /**
     * The page in $language, which is the application's locale by now. A
     * page that is not shown on the site has no address, and no other
     * languages to switch to.
     */
    public function make(Page $page, string $language): View
    {
        $urls = $this->tree->urls($page);
        $switch = [];

        foreach ($this->languages->codes() as $code) {
            $switch[$code] = $urls[$code] ?? $this->theme->homeUrl($code);
        }

        $breadcrumbs = $this->tree->ancestors($page)
            ->map(fn (Page $ancestor): array => ['label' => $ancestor->name, 'url' => (string) $this->tree->url($ancestor, $language)])
            ->all();

        $children = $this->tree->children($page)
            ->map(fn (Page $child): array => [
                'title' => $child->name,
                'url' => $this->tree->url($child, $language),
                'text' => $child->getSeoDescription(),
            ])
            ->filter(fn (array $child): bool => $child['url'] !== null)
            ->values()
            ->all();

        // A page with sections is drawn from them, any other from its text.
        $sections = Layout::sections($page->layout);
        $text = trim((string) $page->content);
        $content = match (true) {
            $sections !== [] => $this->layouts->render($sections),
            $text !== '' => new HtmlString($text),
            default => null,
        };

        return view(Templates::view($page->template), [
            'page' => $page,
            'defaultView' => Templates::defaultView(),
            'title' => $page->name,
            'content' => $content,
            'built' => $sections !== [],
            'children' => $children,
            'canonical' => $this->tree->url($page, $language),
            'breadcrumbs' => $breadcrumbs,
            'alternates' => $urls,
            'switch' => $switch,
        ]);
    }
}
