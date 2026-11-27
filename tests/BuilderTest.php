<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\History\Revisions;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Revision;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Pages\Models\Page;
use Inertia\Testing\AssertableInertia;

/**
 * A page made of blocks: kept on the page, edited in the admin with the rest
 * of it, versioned with it and drawn in place of its text.
 */
final class BuilderTest extends TestCase
{
    /**
     * A tree with one section, one column and a text block.
     *
     * @param  array<string,string>  $html  by language
     * @return array<string,mixed>
     */
    private function tree(array $html = ['en' => '<p>Built of blocks.</p>']): array
    {
        return ['version' => 1, 'sections' => [[
            'id' => 's1',
            'settings' => ['width' => 'default', 'tone' => 'plain'],
            'columns' => [[
                'id' => 'c1',
                'settings' => ['span' => 12],
                'blocks' => [['id' => 'b1', 'type' => 'text', 'settings' => ['html' => $html]]],
            ]],
        ]]];
    }

    private function aPage(array $attributes = []): Page
    {
        return Page::query()->create(['name' => 'About us', 'code' => 'about', 'content' => '<p>The old text.</p>', ...$attributes]);
    }

    /**
     * What the admin form sends.
     *
     * @param  array<string,mixed>  $more
     * @return array<string,mixed>
     */
    private function form(array $more = []): array
    {
        return ['name' => ['en' => 'About us'], 'code' => 'about', 'template' => 'default', 'active' => true, 'sort' => 100, 'content' => ['en' => '<p>The old text.</p>'], ...$more];
    }

    /**
     * The same tree, value for value and type for type, in whatever order the
     * database gives the keys back (MySQL sorts those of a JSON column).
     *
     * @param  array<string,mixed>  $expected
     */
    private function assertTree(array $expected, mixed $stored): void
    {
        $this->assertSame($this->sorted($expected), $this->sorted($stored));
    }

    private function sorted(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        ksort($value);

        return array_map($this->sorted(...), $value);
    }

    public function test_a_page_with_sections_is_drawn_from_them_in_place_of_its_text(): void
    {
        $this->aPage(['layout' => $this->tree()]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('<h1>About us</h1>', false)
            ->assertSee('<div class="atrium-layout">', false)
            ->assertSee('<div class="b-block b-type-text" data-block-id="b1"><div class="content"><p>Built of blocks.</p></div>', false)
            ->assertSee('<main id="content" class="wrap">', false)
            ->assertDontSee('<p>The old text.</p>', false);
    }

    public function test_a_landing_made_of_blocks_takes_the_whole_width(): void
    {
        $this->aPage(['layout' => $this->tree(), 'template' => 'landing']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('<main id="content" class="main-builder">', false)
            ->assertSee('<p>Built of blocks.</p>', false);
    }

    public function test_a_landing_of_text_keeps_its_width(): void
    {
        $this->aPage(['template' => 'landing']);

        $this->get('/about')
            ->assertOk()
            ->assertSee('<main id="content" class="wrap">', false)
            ->assertDontSee('atrium-layout', false);
    }

    public function test_a_template_of_the_theme_draws_the_blocks_too(): void
    {
        $this->aPage(['layout' => $this->tree(), 'template' => 'full-width']);

        $this->get('/about')->assertOk()->assertSee('<p>Built of blocks.</p>', false)->assertSee('<div class="atrium-layout">', false);
    }

    public function test_a_page_without_a_usable_tree_keeps_showing_its_text(): void
    {
        $page = $this->aPage();

        foreach ([null, ['version' => 1, 'sections' => []], ['version' => 2, 'sections' => $this->tree()['sections']]] as $layout) {
            $page->update(['layout' => $layout]);

            $this->get('/about')
                ->assertOk()
                ->assertSee('<p>The old text.</p>', false)
                ->assertDontSee('atrium-layout', false)
                ->assertDontSee('Built of blocks.', false);
        }
    }

    public function test_a_page_with_neither_blocks_nor_text_has_only_its_heading(): void
    {
        $this->aPage(['content' => "  \n "]);

        $this->get('/about')->assertOk()->assertSee('<h1>About us</h1>', false)->assertDontSee('<div class="content">', false);
    }

    public function test_the_blocks_are_shown_in_the_language_of_the_address(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);
        $this->app->make(ContentLanguages::class)->forgetActive();
        $this->aPage([
            'name' => ['en' => 'About us', 'de' => 'Über uns'],
            'code' => 'about',
            'layout' => $this->tree(['en' => '<p>Hello.</p>', 'de' => '<p>Hallo.</p>']),
        ]);

        $this->get('/about')->assertOk()->assertSee('<p>Hello.</p>', false)->assertDontSee('Hallo.', false);
        $this->get('/de/about')->assertOk()->assertSee('<p>Hallo.</p>', false)->assertDontSee('Hello.', false);
    }

    public function test_the_form_of_a_page_edits_the_tree_with_the_blocks_there_are(): void
    {
        $page = $this->aPage(['layout' => $this->tree()]);

        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/pages/{$page->id}/edit")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $inertia): void {
                $field = collect($inertia->toArray()['props']['fields'])->firstWhere('name', 'layout');

                $this->assertSame('layout', $field['type']);
                $this->assertSame('Sections and blocks', $field['label']);
                $this->assertSame(__('atrium-pages::pages.hints.layout'), $field['hint']);
                $this->assertSame(['text', 'picture', 'gallery', 'button', 'map'], array_column($field['types'], 'type'));
                $inertia->where('item.layout.sections.0.columns.0.blocks.0.id', 'b1');
            });
    }

    public function test_an_editor_builds_a_page_of_blocks_and_visitors_see_it(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', $this->form(['layout' => $this->tree()]))
            ->assertSessionHasNoErrors();

        $this->assertTree($this->tree(), Page::query()->sole()->layout);
        $this->get('/about')->assertSee('<p>Built of blocks.</p>', false);
    }

    public function test_a_save_that_does_not_send_the_tree_keeps_it(): void
    {
        $page = $this->aPage(['layout' => $this->tree()]);

        $this->actingAs($this->admin(), 'admin')->put("/admin/pages/{$page->id}", $this->form())->assertSessionHasNoErrors();

        $this->assertTree($this->tree(), $page->fresh()->layout);
    }

    public function test_an_editor_takes_the_blocks_away_and_the_text_returns(): void
    {
        $page = $this->aPage(['layout' => $this->tree()]);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/pages/{$page->id}", $this->form(['layout' => ['version' => 1, 'sections' => []]]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['version' => 1, 'sections' => []], $page->fresh()->layout);
        $this->get('/about')->assertSee('<p>The old text.</p>', false)->assertDontSee('Built of blocks.', false);
    }

    public function test_a_block_that_is_not_filled_in_is_refused_and_the_page_stays(): void
    {
        $page = $this->aPage(['layout' => $this->tree()]);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/pages/{$page->id}", $this->form(['layout' => $this->tree(['en' => ''])]))
            ->assertSessionHasErrors('layout');

        $this->assertStringStartsWith('Section 1, column 1, block 1 (Text): ', session('errors')->first('layout'));
        $this->assertTree($this->tree(), $page->fresh()->layout);
    }

    public function test_a_tree_that_is_not_one_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', $this->form(['layout' => 'blocks']))
            ->assertSessionHasErrors('layout');

        $this->assertSame(0, Page::query()->count());
    }

    public function test_an_older_version_brings_its_blocks_back(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', $this->form(['layout' => $this->tree(['en' => '<p>First.</p>'])]))
            ->assertSessionHasNoErrors();
        $page = Page::query()->sole();
        $this->put("/admin/pages/{$page->id}", $this->form(['layout' => $this->tree(['en' => '<p>Second.</p>'])]))->assertSessionHasNoErrors();

        $first = $this->app->make(Revisions::class)->of($page)->get()->last();
        $this->assertInstanceOf(Revision::class, $first);

        $this->post("/admin/pages/{$page->id}/history/{$first->id}")->assertSessionHasNoErrors();

        $this->assertTree($this->tree(['en' => '<p>First.</p>']), $page->fresh()->layout);
        $this->get('/about')->assertSee('<p>First.</p>', false)->assertDontSee('Second.', false);
    }
}
