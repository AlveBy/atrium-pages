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
use Atrium\Pages\Http\Controllers\PageController;
use Atrium\Pages\Models\Page;
use Atrium\Pages\PageTree;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Which pages are visible and at what path, and how a page is drawn from
 * the tree: its language switch, its breadcrumbs and its text.
 */
final class PageTreeTest extends TestCase
{
    public function test_a_path_is_found_with_or_without_its_slashes(): void
    {
        $about = $this->page('about', 'About us');
        $team = $this->page('team', 'Our team', $about);

        $tree = app(PageTree::class);

        $this->assertTrue($team->is($tree->find('/about/team/')));
        $this->assertTrue($about->is($tree->find('about')));
        $this->assertNull($tree->find('about/nobody'));
        $this->assertSame('about/team', $tree->path($team));
    }

    public function test_a_page_below_a_hidden_one_is_hidden_too(): void
    {
        $about = $this->page('about', 'About us', null, ['active' => false]);
        $team = $this->page('team', 'Our team', $about);
        $other = $this->page('other', 'Other');
        $hiddenChild = $this->page('hidden', 'Hidden', $other, ['active' => false]);
        $shownChild = $this->page('shown', 'Shown', $other);

        $tree = app(PageTree::class);

        $this->assertNull($tree->path($team));
        $this->assertNull($tree->find('team'));
        $this->assertNull($tree->url($team));
        $this->assertNull($tree->path($hiddenChild));
        $this->assertSame([$shownChild->id], $tree->children($other)->pluck('id')->all());
        $this->assertSame([$other->id], $tree->ancestors($shownChild)->pluck('id')->all());
    }

    public function test_a_page_under_another_site_page_is_not_on_this_site(): void
    {
        $second = Site::query()->create(['code' => 'second', 'name' => 'Second', 'folder' => 'second']);
        app(Sites::class)->setCurrent($second);
        $foreign = $this->page('foreign', 'Foreign');
        app(Sites::class)->setCurrent(app(Sites::class)->default());

        $orphan = $this->page('orphan', 'Orphan');
        Page::query()->whereKey($orphan->id)->update(['parent_id' => $foreign->id]);
        app(PageTree::class)->flush();

        $this->assertNull(app(PageTree::class)->path($orphan->fresh()));
        $this->assertNull(app(PageTree::class)->find('orphan'));
    }

    public function test_a_path_has_twenty_levels_at_most(): void
    {
        $parent = null;
        $pages = [];

        foreach (range(1, 21) as $level) {
            $pages[$level] = $parent = $this->page("l{$level}", "Level {$level}", $parent);
        }

        $tree = app(PageTree::class);

        $this->assertSame(implode('/', array_map(fn (int $level): string => "l{$level}", range(1, 20))), $tree->path($pages[20]));
        $this->assertNull($tree->path($pages[21]));
        $this->assertCount(19, $tree->ancestors($pages[20]));
    }

    public function test_of_two_pages_on_one_path_the_first_in_order_opens(): void
    {
        $second = $this->page('same', 'Second', null, ['sort' => 20]);
        $first = $this->page('same', 'First', null, ['sort' => 10]);

        $this->assertTrue($first->is(app(PageTree::class)->find('same')));
        $this->assertSame('same', app(PageTree::class)->path($second));
    }

    public function test_the_tree_is_built_once_until_a_page_is_saved(): void
    {
        $about = $this->page('about', 'About us');
        $tree = app(PageTree::class);
        $this->assertNotNull($tree->find('about'));

        // Written past the model: the tree does not know yet.
        DB::table('pages')->insert(['site_id' => $about->site_id, 'code' => 'quiet', 'name' => json_encode(['en' => 'Quiet']), 'active' => true, 'sort' => 500, 'template' => 'default']);
        $this->assertNull($tree->find('quiet'));

        // Saving any page forgets the tree.
        $about->update(['sort' => 1]);
        $this->assertNotNull($tree->find('quiet'));
    }

    public function test_a_page_has_an_address_only_in_the_languages_it_is_written_in(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);
        $about = $this->page('about', 'About us');
        $hidden = $this->page('hidden', 'Hidden', null, ['active' => false]);
        $tree = app(PageTree::class);

        $this->assertSame(url('/about'), $tree->url($about, 'en'));
        $this->assertNull($tree->url($about, 'de'));
        $this->assertNull($tree->url($about, 'xx'));
        $this->assertNull($tree->url($hidden, 'en'));
        $this->assertSame(['en' => url('/about')], $tree->urls($about));

        $about->setTranslation('name', 'de', 'Über uns')->save();
        $this->assertSame(url('/de/about'), $tree->url($about, 'de'));
    }

    public function test_the_page_is_drawn_with_its_language_switch_breadcrumbs_and_text(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);
        $about = $this->page('about', 'About us');
        $team = $this->page('team', 'Our team', $about, ['content' => ['en' => "  <p>Twelve.</p>\n "]]);

        $view = app(PageController::class)->show('/about/team/');

        $this->assertInstanceOf(View::class, $view);
        $data = $view->getData();
        $this->assertSame(['en' => url('/about/team'), 'de' => url('/de')], $data['switch']);
        $this->assertSame([['label' => 'About us', 'url' => url('/about')]], $data['breadcrumbs']);
        $this->assertSame('<p>Twelve.</p>', $data['content']->toHtml());
        $this->assertSame('Our team', $data['title']);
        $this->assertTrue($team->is($data['page']));
    }

    public function test_a_page_of_spaces_has_no_text(): void
    {
        $this->page('blank', 'Blank', null, ['content' => ['en' => "  \n  "]]);

        $this->assertNull(app(PageController::class)->show('blank')->getData()['content']);
    }

    public function test_a_breadcrumb_without_an_address_in_the_language_is_left_without_one(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);
        $about = $this->page('about', 'About us');
        $team = $this->page('team', 'Our team', $about);
        $team->setTranslation('name', 'de', 'Unser Team')->save();

        $this->get('/de/about/team')->assertOk()->assertSee('Unser Team');
        $breadcrumbs = app(PageController::class)->show('de/about/team')->getData()['breadcrumbs'];

        $this->assertSame('', $breadcrumbs[0]['url']);
    }

    public function test_the_default_language_has_no_prefix_and_a_page_may_be_called_like_a_language(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);
        $this->page('about', 'About us');
        $this->page('de', 'Germany');

        $this->get('/en/about')->assertNotFound();
        $this->get('/about')->assertOk()->assertSee('About us');
        // /de itself is the theme's German home; the page is still found by its code.
        $this->assertSame('Germany', app(PageController::class)->show('de')->getData()['title']);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function page(string $code, string $name, ?Page $parent = null, array $attributes = []): Page
    {
        return Page::query()->create(['code' => $code, 'name' => ['en' => $name], 'parent_id' => $parent?->id, ...$attributes]);
    }
}
