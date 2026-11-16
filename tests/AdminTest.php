<?php

declare(strict_types=1);

namespace Atrium\Pages\Tests;

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

        $this->assertNull($team->fresh()->parent_id);
    }

    public function test_the_code_field_shows_the_page_address(): void
    {
        $about = Page::query()->create(['name' => 'About', 'code' => 'about']);
        Page::query()->create(['name' => 'Team', 'code' => 'team', 'parent_id' => $about->id]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/pages/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fields.2.name', 'code')
                ->where('fields.2.publicPrefix', '')
                ->where('fields.2.parentField', 'parent_id')
                ->where("fields.2.parentPaths.{$about->id}", 'about'));
    }
}
