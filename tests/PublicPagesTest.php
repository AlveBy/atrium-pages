<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\Admin\Templates\Templates as RecordTemplates;
use Atrium\Pages\Models\Page;
use Atrium\Pages\Templates;

/**
 * A page opens at the path of its codes from the root of the site, inside
 * the theme's layout.
 */
final class PublicPagesTest extends TestCase
{
    private Page $about;

    private Page $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->about = Page::query()->create([
            'name' => 'About us',
            'code' => 'about',
            'content' => '<p>We make <strong>things</strong>.</p>',
        ]);
        $this->about->updateSeoMeta(['title' => 'About the company', 'description' => 'Who we are and what we do.']);

        $this->team = Page::query()->create([
            'name' => 'Our team',
            'code' => 'team',
            'parent_id' => $this->about->id,
            'content' => '<p>Twelve people.</p>',
        ]);
    }

    public function test_a_page_opens_at_its_code_with_its_seo(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('<title>About the company | Demo site</title>', false)
            ->assertSee('<meta name="description" content="Who we are and what we do.">', false)
            ->assertSee('<link rel="canonical" href="'.$this->root().'/about">', false)
            ->assertSee('<h1>About us</h1>', false)
            ->assertSee('<p>We make <strong>things</strong>.</p>', false);
    }

    public function test_a_page_below_another_opens_at_the_path_with_breadcrumbs(): void
    {
        $this->get('/about/team')
            ->assertOk()
            ->assertSee('<h1>Our team</h1>', false)
            ->assertSee('<title>Our team | Demo site</title>', false)
            // No SEO description: the text makes one.
            ->assertSee('<meta name="description" content="Twelve people.">', false)
            ->assertSee('<li><a href="'.$this->root().'/about">About us</a></li>', false);

        $this->get('/team')->assertNotFound();
        $this->get('/about/nobody')->assertNotFound();
    }

    public function test_a_hidden_page_hides_its_branch(): void
    {
        $this->about->update(['active' => false]);

        $this->get('/about')->assertNotFound();
        $this->get('/about/team')->assertNotFound();
    }

    public function test_the_template_draws_the_page(): void
    {
        $this->about->update(['template' => 'children']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('<h2><a href="'.$this->root().'/about/team">Our team</a></h2>', false)
            ->assertSee('Twelve people.');

        $this->about->update(['template' => 'landing']);

        $this->get('/about')
            ->assertOk()
            ->assertDontSee('<h1>', false)
            ->assertSee('<p>We make <strong>things</strong>.</p>', false);

        // A template gone from the config falls back to the default one.
        $this->about->update(['template' => 'removed']);

        $this->get('/about')->assertOk()->assertSee('<h1>About us</h1>', false);
    }

    public function test_the_themes_full_width_template_draws_a_page_across_the_window(): void
    {
        $this->get('/about')->assertOk()->assertSee('<main id="content" class="wrap">', false);

        $this->about->update(['template' => 'full-width']);

        $this->get('/about')
            ->assertOk()
            ->assertViewIs('atrium-theme::templates.all.full-width')
            ->assertViewHas('defaultView', 'atrium-pages::templates.default')
            ->assertSee('<main id="content" class="wrap-full">', false)
            ->assertSee('<h1>About us</h1>', false);
    }

    public function test_a_template_file_of_the_site_draws_a_page_and_a_deleted_one_the_default(): void
    {
        $views = sys_get_temp_dir().'/atrium-pages-site-'.uniqid();
        mkdir($views.'/templates/pages', 0777, true);
        file_put_contents($views.'/templates/pages/press.blade.php', "{{-- Template: Press --}}\n<p class=\"press\">{{ \$title }}</p>");
        view()->addLocation($views);
        app(RecordTemplates::class)->forget();

        $this->about->update(['template' => 'press']);
        $this->assertSame('Press', Templates::options()['press']);
        $this->get('/about')->assertOk()->assertSee('<p class="press">About us</p>', false);

        unlink($views.'/templates/pages/press.blade.php');
        rmdir($views.'/templates/pages');
        rmdir($views.'/templates');
        rmdir($views);
        view()->getFinder()->flush();
        app(RecordTemplates::class)->forget();

        $this->get('/about')->assertOk()->assertSee('<h1>About us</h1>', false);
    }

    public function test_old_bitrix_addresses_lead_to_the_page(): void
    {
        $this->get('/about/index.php')->assertRedirect($this->root().'/about')->assertStatus(301);
        $this->get('/about/team.php')->assertRedirect($this->root().'/about/team')->assertStatus(301);
        $this->get('/nothing.php')->assertNotFound();
    }

    public function test_a_page_is_written_per_language(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);

        $this->about->setTranslation('name', 'de', 'Über uns')->setTranslation('content', 'de', '<p>Wir machen Dinge.</p>')->save();

        $this->get('/de/about')
            ->assertOk()
            ->assertSee('<html lang="de">', false)
            ->assertSee('<h1>Über uns</h1>', false)
            ->assertSee('<p>Wir machen Dinge.</p>', false)
            ->assertSee('hreflang="en" href="'.$this->root().'/about"', false)
            ->assertSee('hreflang="de" href="'.$this->root().'/de/about"', false);

        // Not written in German: no German page.
        $this->get('/de/about/team')->assertNotFound();

        // A page may be called like a language: /de/… is its path when no
        // German page is there.
        app()->setLocale('en');
        Page::query()->create(['name' => 'Delaware', 'code' => 'de'])
            ->children()->create(['name' => 'Office', 'code' => 'office']);

        $this->get('/de/office')->assertOk()->assertSee('<h1>Office</h1>', false);
    }

    public function test_the_app_the_admin_and_the_theme_keep_their_routes(): void
    {
        Page::query()->create(['name' => 'Admin', 'code' => 'admin']);

        $this->get('/admin/login')->assertOk();
        $this->get('/')->assertOk()->assertSee('<title>Demo site</title>', false);
    }

    public function test_pages_are_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.$this->root().'/about</loc>', false)
            ->assertSee('<loc>'.$this->root().'/about/team</loc>', false);
    }

    public function test_each_site_has_its_own_pages(): void
    {
        $other = Site::query()->create(['code' => 'other', 'name' => 'Other', 'domains' => ['other.test']]);
        app(Sites::class)->flush();
        app(Sites::class)->setCurrent($other);
        Page::query()->create(['name' => 'Other about', 'code' => 'about']);
        app(Sites::class)->forgetCurrent();

        $this->get('/about')->assertOk()->assertSee('<h1>About us</h1>', false);
        $this->get('http://other.test/about')->assertOk()->assertSee('<h1>Other about</h1>', false);
        $this->get('http://other.test/about/team')->assertNotFound();
    }
}
