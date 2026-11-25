<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\History\Trash;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Models\TrashedRecord;
use Atrium\Admin\Templates\RecordTemplate;
use Atrium\Pages\Actions\ChangeVisibility;
use Atrium\Pages\Models\Page;
use Inertia\Testing\AssertableInertia;

/**
 * Editors keep the pages in the admin like any other module.
 */
final class AdminTest extends TestCase
{
    public function test_the_module_is_in_the_admin_with_its_form(): void
    {
        Page::query()->create(['name' => 'About us', 'code' => 'about']);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/pages')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Resource/Index')
                ->where('title', 'Pages')
                ->where('items.total', 1)
                ->where('items.data.0.code', 'about'));

        $this->get('/admin/pages/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Resource/Create'));
    }

    public function test_the_list_shows_only_the_shown_or_only_the_hidden_pages(): void
    {
        Page::query()->create(['name' => 'About us', 'code' => 'about', 'active' => true]);
        Page::query()->create(['name' => 'Draft', 'code' => 'draft', 'active' => false]);
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/pages')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filterControls', [['name' => 'active', 'type' => 'boolean', 'label' => 'Active', 'options' => [['value' => '1', 'label' => 'Yes'], ['value' => '0', 'label' => 'No']]]])
            ->where('items.total', 2));
        $this->get('/admin/pages?active=0')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('items.total', 1)
            ->where('items.data.0.code', 'draft'));
    }

    public function test_an_editor_creates_edits_and_deletes_a_page(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', [
                'name' => ['en' => 'About us'],
                'code' => 'about',
                'template' => 'default',
                'active' => true,
                'sort' => 100,
                'content' => ['en' => '<p>Who we are.</p>'],
                'seo_title' => 'About the company',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.pages.index'));

        $page = Page::query()->sole();
        $this->assertSame('About us', $page->name);
        $this->assertSame('<p>Who we are.</p>', $page->content);
        $this->assertSame('About the company', $page->getSeoTitle());
        $this->assertNotNull($page->site_id);

        $this->get("/admin/pages/{$page->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Admin/Resource/Edit')
                ->where('item.code', 'about'));

        $this->put("/admin/pages/{$page->id}", [
            'name' => ['en' => 'About'],
            'code' => 'about',
            'template' => 'landing',
            'active' => true,
            'sort' => 100,
        ])->assertSessionHasNoErrors();

        $this->assertSame('About', $page->fresh()->name);
        $this->assertSame('landing', $page->fresh()->template);

        $this->delete("/admin/pages/{$page->id}")->assertRedirect(route('admin.pages.index'));
        $this->assertSame(0, Page::query()->count());
    }

    public function test_pages_under_one_parent_need_different_codes(): void
    {
        $about = Page::query()->create(['name' => 'About', 'code' => 'about']);
        $services = Page::query()->create(['name' => 'Services', 'code' => 'services']);
        Page::query()->create(['name' => 'Team', 'code' => 'team', 'parent_id' => $about->id]);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', ['name' => ['en' => 'Team'], 'code' => 'team', 'parent_id' => $about->id, 'template' => 'default'])
            ->assertSessionHasErrors('code');

        $this->post('/admin/pages', ['name' => ['en' => 'About again'], 'code' => 'about', 'template' => 'default'])
            ->assertSessionHasErrors('code');

        // The same code under another parent is another address.
        $this->post('/admin/pages', ['name' => ['en' => 'Team'], 'code' => 'team', 'parent_id' => $services->id, 'template' => 'default'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Page::query()->where('code', 'team')->count());

        // A page keeps its own code when saved again.
        $this->put("/admin/pages/{$about->id}", ['name' => ['en' => 'About'], 'code' => 'about', 'template' => 'default'])
            ->assertSessionHasNoErrors();
    }

    public function test_the_code_keeps_old_bitrix_addresses_but_nothing_that_breaks_a_path(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', ['name' => ['en' => 'Prices'], 'code' => 'Price_List', 'template' => 'default'])
            ->assertSessionHasNoErrors();

        foreach (['a/b', 'a b', 'list.php', ''] as $code) {
            $this->post('/admin/pages', ['name' => ['en' => 'Bad'], 'code' => $code, 'template' => 'default'])
                ->assertSessionHasErrors('code');
        }
    }

    public function test_a_template_must_be_one_of_the_configured_ones(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/pages', ['name' => ['en' => 'About'], 'code' => 'about', 'template' => 'nope'])
            ->assertSessionHasErrors('template');
    }

    public function test_the_form_offers_the_templates_of_the_theme_once_and_saves_the_choice_in_the_page(): void
    {
        $page = Page::query()->create(['code' => 'about', 'name' => ['en' => 'About']]);
        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/pages/{$page->id}/edit")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $inertia): void {
                $fields = collect($inertia->toArray()['props']['fields']);
                $this->assertSame(1, $fields->where('name', 'template')->count());
                $this->assertContains('full-width', array_column($fields->firstWhere('name', 'template')['options'], 'value'));
            });

        $this->put("/admin/pages/{$page->id}", ['name' => ['en' => 'About'], 'code' => 'about', 'template' => 'full-width', 'active' => true, 'sort' => 100])
            ->assertSessionHasNoErrors();

        $this->assertSame('full-width', $page->fresh()->template);
        $this->assertSame(0, RecordTemplate::query()->count());
    }

    public function test_a_page_cannot_be_its_own_parent(): void
    {
        $about = Page::query()->create(['name' => 'About', 'code' => 'about']);
        $team = Page::query()->create(['name' => 'Team', 'code' => 'team', 'parent_id' => $about->id]);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/pages/{$about->id}", ['name' => ['en' => 'About'], 'code' => 'about', 'parent_id' => $team->id, 'template' => 'default'])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_children_of_a_deleted_page_move_up(): void
    {
        $about = Page::query()->create(['name' => 'About', 'code' => 'about']);
        $team = Page::query()->create(['name' => 'Team', 'code' => 'team', 'parent_id' => $about->id]);

        $this->actingAs($this->admin(), 'admin')->delete("/admin/pages/{$about->id}");
        $this->assertSame($about->id, $team->fresh()->parent_id, 'A page in the trash keeps its children.');

        app(Trash::class)->purge(TrashedRecord::query()->sole());
        $this->assertNull($team->fresh()->parent_id);
    }

    public function test_the_code_field_shows_the_page_address(): void
    {
        $about = Page::query()->create(['name' => 'About', 'code' => 'about']);
        Page::query()->create(['name' => 'Team', 'code' => 'team', 'parent_id' => $about->id]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/pages/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fields.4.name', 'code')
                ->where('fields.4.publicPrefix', '')
                ->where('fields.4.parentField', 'parent_id')
                ->where("fields.4.parentPaths.{$about->id}", 'about'));
    }

    public function test_a_subpage_is_added_from_its_parent(): void
    {
        $about = Page::query()->create(['name' => 'About us', 'code' => 'about']);
        $subpage = ['type' => 'create', 'name' => 'subpage', 'label' => 'Subpage', 'fill' => ['parent_id' => 'id']];

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/pages')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('actions', [$subpage]));

        $this->get("/admin/pages/{$about->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('actions', [$subpage]));

        // The button's link: the parent is chosen; nothing else comes from
        // the address.
        $this->get("/admin/pages/create?parent_id={$about->id}&code=hacked")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fields', fn ($fields): bool => collect($fields)->firstWhere('name', 'parent_id')['default'] === $about->id
                    && collect($fields)->firstWhere('name', 'code')['default'] === null));
    }

    public function test_without_the_right_to_create_there_is_no_subpage_button(): void
    {
        $about = Page::query()->create(['name' => 'About us', 'code' => 'about']);
        $editor = AdminUser::query()->create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => 'secret-password', 'is_active' => true]);
        $editor->roles()->attach(AdminRole::query()->create(['name' => 'Editor', 'permissions' => ['pages.view', 'pages.update']]));

        $this->actingAs($editor, 'admin')
            ->get("/admin/pages/{$about->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('actions', []));
    }

    public function test_selected_pages_are_hidden_and_shown_together(): void
    {
        [$about, $contacts, $news] = array_map(fn (string $code) => Page::query()->create(['name' => ucfirst($code), 'code' => $code, 'active' => true]), ['about', 'contacts', 'news']);
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/pages')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('bulkActions.1', ['type' => 'bulk', 'name' => 'show', 'label' => 'Show', 'confirm' => null])
            ->where('bulkActions.2', ['type' => 'bulk', 'name' => 'hide', 'label' => 'Hide', 'confirm' => null]));

        $this->post('/admin/pages/bulk/hide', ['ids' => [$about->id, $contacts->id]])
            ->assertSessionHas('success', '2 pages are hidden from the site.');
        $this->assertSame([false, false, true], [$about->fresh()->active, $contacts->fresh()->active, $news->fresh()->active]);

        $this->post('/admin/pages/bulk/show', ['ids' => [$contacts->id]])
            ->assertSessionHas('success', '1 page is shown on the site.');
        $this->assertSame([false, true, true], [$about->fresh()->active, $contacts->fresh()->active, $news->fresh()->active]);
    }

    public function test_hiding_pages_needs_the_right_to_edit_them(): void
    {
        $page = Page::query()->create(['name' => 'About', 'code' => 'about', 'active' => true]);
        $editor = AdminUser::query()->create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'secret-password', 'is_active' => true]);
        $editor->roles()->attach(AdminRole::query()->create(['name' => 'Reader', 'permissions' => ['pages.view']]));

        $this->actingAs($editor, 'admin')->get('/admin/pages')->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->where('bulkActions', []));
        $this->post('/admin/pages/bulk/hide', ['ids' => [$page->id]])->assertForbidden();

        $this->assertTrue($page->fresh()->active);
    }

    public function test_the_visibility_action_shows_unless_told_otherwise(): void
    {
        $page = Page::query()->create(['name' => 'About', 'code' => 'about', 'active' => false]);

        $result = ChangeVisibility::make('show')->handle(Page::query()->get());

        $this->assertSame('1 page is shown on the site.', $result->message());
        $this->assertTrue($page->fresh()->active);
    }
}
