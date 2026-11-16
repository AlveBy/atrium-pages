<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * The page templates an editor picks from, read from
 * config('atrium-pages.templates'): key => Blade view.
 */
final class Templates
{
    public const DEFAULT = 'default';

    /**
     * @return array<string,string>
     */
    public static function all(): array
    {
        $templates = array_filter((array) config('atrium-pages.templates', []), 'is_string');

        return $templates === [] ? [self::DEFAULT => 'atrium-pages::templates.default'] : $templates;
    }

    /**
     * The view of a template; an unknown key (a template removed from the
     * config) falls back to the default one.
     */
    public static function view(?string $key): string
    {
        $templates = self::all();

        return $templates[(string) $key] ?? $templates[self::DEFAULT] ?? reset($templates);
    }

    /**
     * @return array<string,string> key => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (array_keys(self::all()) as $key) {
            $line = 'atrium-pages::pages.templates.'.$key;
            $options[$key] = Lang::has($line) ? __($line) : Str::headline($key);
        }

        return $options;
    }
}
