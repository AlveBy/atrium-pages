<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Illuminate\Support\Arr;

/**
 * Every admin language has every line English has, and no other.
 */
final class TranslationsTest extends TestCase
{
    public function test_every_language_has_the_english_keys(): void
    {
        $dir = dirname(__DIR__).'/lang';
        $english = array_keys(Arr::dot(require $dir.'/en/pages.php'));
        $languages = array_diff(scandir($dir) ?: [], ['.', '..']);

        $this->assertCount(15, $languages);

        foreach ($languages as $language) {
            $lines = Arr::dot(require $dir.'/'.$language.'/pages.php');

            $this->assertSame($english, array_keys($lines), $language);
            $this->assertNotContains('', $lines, $language);
        }
    }
}
