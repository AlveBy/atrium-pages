<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\Fields\Field;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Admin\Support\Sites;
use Atrium\Pages\Fields\PageCodeField;
use Atrium\Pages\Models\Page;
use Atrium\Pages\PagesServiceProvider;
use Atrium\Pages\Templates;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * A page as the panel sees it, the templates it may be drawn with, the
 * demo pages and what the module lets a site publish.
 */
final class PageModelTest extends TestCase
{
    public function test_a_page_is_read_back_as_on_or_off(): void
    {
        $this->assertTrue(Page::query()->create(['code' => 'a', 'name' => ['en' => 'A'], 'active' => 1])->fresh()?->active);
        $this->assertFalse(Page::query()->create(['code' => 'b', 'name' => ['en' => 'B'], 'active' => 0])->fresh()?->active);
    }

    public function test_a_page_knows_its_parent_and_its_children(): void
    {
        $about = Page::query()->create(['code' => 'about', 'name' => ['en' => 'About']]);
        $team = Page::query()->create(['code' => 'team', 'name' => ['en' => 'Team'], 'parent_id' => $about->id]);

        $this->assertTrue($about->is($team->parent()->first()));
        $this->assertNull($about->parent()->first());
        $this->assertSame([$team->id], $about->children()->pluck('id')->all());
    }

    public function test_the_form_tabs_actions_and_list_of_the_panel(): void
    {
        $fields = $this->byName(Page::getAdministrationFields());

        $this->assertSame(['active', 'name', 'code', 'parent_id', 'template', 'sort', 'content', 'layout'], array_keys($fields));
        $this->assertTrue($fields['active']->getDefault());
        $this->assertContains('max:255', $fields['name']->getValidationRules());
        $this->assertNotContains('max:254', $fields['name']->getValidationRules());
        $this->assertSame(500, $fields['sort']->getDefault());
        $this->assertSame(['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-]+$/'], array_values(array_filter(
            $fields['code']->getValidationRules(),
            fn ($rule): bool => is_string($rule),
        )));

        $this->assertSame([['name' => 'params', 'label' => 'General'], ['name' => 'seo', 'label' => 'SEO']], Page::getAdminTabs());
        $this->assertSame([['name' => 'subpage', 'label' => 'Subpage', 'fill' => ['parent_id' => 'id']]], array_map(fn ($action): array => $action->toArray(), Page::getAdministrationActions()));
        $this->assertSame(
            ['id', 'name', 'code', 'sort', 'active', 'updated_at'],
            array_map(fn ($column): string => $column->getName(), Page::getAdministrationListColumns()),
        );
    }

    public function test_the_panel_hooks_are_public_and_the_seo_default_is_for_the_subclasses(): void
    {
        $this->assertTrue((new ReflectionMethod(Page::class, 'getAdminTabs'))->isPublic());
        $this->assertTrue((new ReflectionMethod(Page::class, 'getAdministrationActions'))->isPublic());
        // Called from other packages; Eloquent's __callStatic would hide a protected one.
        $this->assertTrue((new ReflectionMethod(Page::class, 'getAdministrationBulkActions'))->isPublic());
        $this->assertCount(2, Page::getAdministrationBulkActions());
        $this->assertTrue((new ReflectionMethod(Page::class, 'getDefaultSeoDescription'))->isProtected());
    }

    public function test_the_code_field_keeps_every_rule_it_was_given(): void
    {
        $field = PageCodeField::make('code')->required()->rules(['filled']);

        $this->assertSame(['required', 'filled', 'string', 'max:255', 'regex:'.PageCodeField::PATTERN], $field->getValidationRules());
    }

    public function test_the_cached_section_paths_follow_the_pages_saved_and_deleted(): void
    {
        $about = Page::query()->create(['code' => 'about', 'name' => ['en' => 'About']]);
        $this->assertSame([$about->id => 'about'], PublicUrl::sectionPaths(Page::class));

        $team = Page::query()->create(['code' => 'team', 'name' => ['en' => 'Team'], 'parent_id' => $about->id]);
        $this->assertSame([$about->id => 'about', $team->id => 'about/team'], PublicUrl::sectionPaths(Page::class));

        $team->delete();
        $this->assertSame([$about->id => 'about'], PublicUrl::sectionPaths(Page::class));
    }

    public function test_a_code_sent_as_null_is_taken_for_an_empty_one(): void
    {
        Page::query()->create(['code' => '', 'name' => ['en' => 'Nameless']]);
        $page = new Page(['name' => ['en' => 'New']]);

        $this->expectException(ValidationException::class);
        $page->transformAdminData(['code' => null]);
    }

    public function test_a_page_without_a_code_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', ['name' => ['en' => 'About us'], 'code' => '', 'template' => 'default'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, Page::query()->count());
    }

    public function test_a_page_moved_to_a_taken_code_is_refused(): void
    {
        Page::query()->create(['code' => 'taken', 'name' => ['en' => 'Taken']]);
        $page = Page::query()->create(['code' => 'mine', 'name' => ['en' => 'Mine']]);

        $this->assertSame(['name' => ['en' => 'Mine']], $page->transformAdminData(['name' => ['en' => 'Mine']]));
        $this->assertSame(['code' => 'free'], $page->transformAdminData(['code' => 'free']));

        $this->expectException(ValidationException::class);
        $page->transformAdminData(['code' => 'taken']);
    }

    public function test_the_description_comes_from_the_site_template_else_from_the_text(): void
    {
        $page = Page::query()->create(['code' => 'about', 'name' => ['en' => 'About us'], 'content' => ['en' => '<p>We make <b>things</b>.</p>']]);

        $this->assertSame('We make things.', $page->getSeoDescription());

        config(['atrium.seo.templates.'.Page::class.'.description' => '{name}: all about us']);
        $this->assertSame('About us: all about us', $page->getSeoDescription());

        config(['atrium.seo.templates.'.Page::class.'.description' => '']);
        $this->assertSame('We make things.', $page->getSeoDescription());
    }

    /**
     * @return array<string,array{0:mixed,1:array<string,string>}>
     */
    public static function templateLists(): array
    {
        return [
            'the configured ones' => [['default' => 'v.default', 'wide' => 'v.wide'], ['default' => 'v.default', 'wide' => 'v.wide', 'full-width' => 'atrium-theme::templates.all.full-width']],
            'not a view: left out' => [['default' => 'v.default', 'broken' => ['v.x'], 'number' => 5], ['default' => 'v.default', 'full-width' => 'atrium-theme::templates.all.full-width']],
            'none: the default one' => [[], ['default' => 'atrium-pages::templates.default', 'full-width' => 'atrium-theme::templates.all.full-width']],
            'nothing usable: the default one' => [['broken' => null], ['default' => 'atrium-pages::templates.default', 'full-width' => 'atrium-theme::templates.all.full-width']],
        ];
    }

    /**
     * @param  array<string,string>  $expected
     */
    #[DataProvider('templateLists')]
    public function test_the_templates_come_from_the_config(mixed $config, array $expected): void
    {
        config(['atrium-pages.templates' => $config]);

        $this->assertSame($expected, Templates::all());
    }

    public function test_an_unknown_template_is_drawn_with_the_default_one(): void
    {
        $views = sys_get_temp_dir().'/atrium-pages-'.uniqid();
        mkdir($views.'/v', 0777, true);
        file_put_contents($views.'/v/wide.blade.php', 'wide');
        file_put_contents($views.'/v/default.blade.php', 'default');
        view()->addLocation($views);
        config(['atrium-pages.templates' => ['wide' => 'v.wide', 'default' => 'v.default']]);
        $this->assertSame('v.wide', Templates::view('wide'));
        $this->assertSame('v.default', Templates::view('removed'));
        $this->assertSame('v.default', Templates::view(null));

        unlink($views.'/v/wide.blade.php');
        view()->getFinder()->flush();
        app(\Atrium\Admin\Templates\Templates::class)->forget();
        $this->assertSame('v.default', Templates::view('wide'), 'a template whose file is gone is the default one');

        (new Filesystem)->deleteDirectory($views);
    }

    public function test_a_template_is_labelled_by_its_translation_else_by_its_key(): void
    {
        config(['atrium-pages.templates' => ['default' => 'v.default', 'my_landing' => 'v.landing']]);

        $this->assertSame(['default' => 'Page', 'my_landing' => 'My Landing', 'full-width' => 'Full width'], Templates::options());
    }

    public function test_the_demo_pages_belong_to_the_default_site_in_the_editors_order(): void
    {
        $second = Site::query()->create(['code' => 'second', 'name' => 'Second', 'folder' => 'second']);
        app(Sites::class)->setCurrent($second);

        app(Modules::class)->seedDemo(app(Modules::class)->find(PagesServiceProvider::MODULE));

        $pages = Page::query()->withoutGlobalScopes()->get()->keyBy('code');
        $this->assertSame([app(Sites::class)->default()->id], $pages->pluck('site_id')->unique()->values()->all());
        $this->assertSame(
            ['about' => 100, 'team' => 100, 'history' => 200, 'delivery' => 200, 'contacts' => 300],
            $pages->map(fn (Page $page): int => $page->sort)->all(),
        );
        $this->assertSame('children', $pages['about']->template);
        $this->assertSame('default', $pages['team']->template);
        $this->assertSame($pages['about']->id, $pages['history']->parent_id);
        $names = $pages['delivery']->getTranslations('name');
        ksort($names);
        $this->assertSame(
            ['de' => 'Lieferung und Bezahlung', 'en' => 'Delivery and payment', 'ru' => 'Доставка и оплата'],
            $names,
        );
    }

    public function test_the_route_of_the_pages_is_a_web_fallback(): void
    {
        $route = Route::getRoutes()->getByName('atrium.pages.show');

        $this->assertNotNull($route);
        $this->assertSame(['web', 'atrium.site'], $route->middleware());
        $this->assertTrue($route->isFallback);
    }

    public function test_the_config_views_and_translations_can_be_published(): void
    {
        $paths = [
            'atrium-pages-config' => [__DIR__.'/../config/atrium-pages.php', config_path('atrium-pages.php')],
            'atrium-pages-views' => [__DIR__.'/../resources/views', resource_path('views/vendor/atrium-pages')],
            'atrium-pages-lang' => [__DIR__.'/../lang', app()->langPath('vendor/atrium-pages')],
        ];

        foreach ($paths as $tag => [$from, $to]) {
            $published = ServiceProvider::pathsToPublish(PagesServiceProvider::class, $tag);

            $this->assertCount(1, $published, $tag);
            $this->assertSame(realpath($from), realpath((string) array_key_first($published)), $tag);
            $this->assertSame($to, $published[array_key_first($published)], $tag);
        }
    }

    /**
     * @param  array<int,Field>  $fields
     * @return array<string,Field>
     */
    private function byName(array $fields): array
    {
        return array_combine(array_map(fn (Field $field): string => $field->getName(), $fields), $fields);
    }
}
