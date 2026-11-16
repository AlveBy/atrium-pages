<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Pages\Models\Page;
use Illuminate\Support\Facades\Route;

/**
 * Required with Composer but not installed with atrium:module:install: the
 * module is neither in the admin nor on the site.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_the_module_stays_out_until_it_is_installed(): void
    {
        Page::query()->create(['name' => 'About us', 'code' => 'about']);

        $this->assertFalse(Route::has('atrium.pages.show'));
        $this->get('/about')->assertNotFound();

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/pages')
            ->assertNotFound();
    }
}
