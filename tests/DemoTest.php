<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Modules\Modules;
use Atrium\Pages\Models\Page;
use Atrium\Pages\PagesServiceProvider;

/**
 * The demo pages of `atrium:install --demo`.
 */
final class DemoTest extends TestCase
{
    public function test_the_demo_fills_the_pages_once(): void
    {
        $modules = app(Modules::class);
        $module = $modules->find(PagesServiceProvider::MODULE);

        $this->assertNotNull($module);
        $this->assertTrue($modules->seedDemo($module));
        $this->assertSame(5, Page::query()->count());

        $this->assertFalse($modules->seedDemo($module));
        $this->assertSame(5, Page::query()->count());
    }

    public function test_the_demo_pages_are_on_the_site_in_every_language(): void
    {
        app(Modules::class)->seedDemo(app(Modules::class)->find(PagesServiceProvider::MODULE));

        $this->get('/about')
            ->assertOk()
            ->assertSee('<title>About us | Demo site</title>', false)
            ->assertSeeInOrder(['About us', 'Our team', 'Our history']);

        $this->get('/contacts')
            ->assertOk()
            ->assertSee('12 Market Street')
            ->assertSee('<main id="content" class="wrap-full">', false);
        $this->get('/about')->assertOk()->assertSee('<main id="content" class="wrap">', false);
        $this->assertSame('full-width', Page::query()->where('code', 'contacts')->value('template'));

        Language::query()->create(['code' => 'ru', 'name' => 'Русский']);

        $this->get('/ru/about/team')
            ->assertOk()
            ->assertSee('<title>Команда | Demo site</title>', false)
            ->assertSee('О компании');
    }
}
