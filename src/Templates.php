<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages;

use Atrium\Admin\Templates\Template;
use Atrium\Admin\Templates\Templates as RecordTemplates;
use Atrium\Pages\Models\Page;
use Illuminate\Support\Facades\Lang;

/**
 * The page templates an editor picks from: those in
 * config('atrium-pages.templates') (key => Blade view) and the ones the
 * core finds in the templates folders of the site, as for every module
 * (resources/views/templates/pages/ and templates/all/).
 */
final class Templates
{
    public const DEFAULT = 'default';

    private const DEFAULT_VIEW = 'atrium-pages::templates.default';

    /**
     * What the pages package brings: the config.
     */
    public static function source(): void
    {
        app(RecordTemplates::class)->source(Page::class, static fn (): array => config('atrium-pages.templates', []));
    }

    /**
     * @return array<string,string> key => Blade view, the default one first
     */
    public static function all(): array
    {
        return [self::DEFAULT => self::defaultView()]
            + array_map(static fn (Template $template): string => $template->view, app(RecordTemplates::class)->available(Page::class));
    }

    /**
     * The view of a page without a template, or whose template was removed.
     */
    public static function defaultView(): string
    {
        return app(RecordTemplates::class)->defaultView(Page::class) ?? self::DEFAULT_VIEW;
    }

    /**
     * The view of a template; an unknown key (a template removed from the
     * config, a file deleted) falls back to the default one.
     */
    public static function view(?string $key): string
    {
        return app(RecordTemplates::class)->view(Page::class, $key, self::DEFAULT_VIEW);
    }

    /**
     * @return array<string,string> key => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (app(RecordTemplates::class)->options(Page::class) as $key => $label) {
            $line = 'atrium-pages::pages.templates.'.$key;
            $options[$key] = Lang::has($line) ? __($line) : $label;
        }

        return $options;
    }
}
