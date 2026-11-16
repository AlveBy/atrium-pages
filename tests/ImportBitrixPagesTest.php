<?php

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\Models\Site;
use Atrium\Pages\Bitrix\StaticPageFile;
use Atrium\Pages\Models\Page;
use Illuminate\Support\Facades\File;

/**
 * The static pages of a made-up Bitrix site move over at their addresses.
 */
final class ImportBitrixPagesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        // The English site sits in /en/ of the same files: not this site's pages.
        Site::query()->create(['code' => 'en', 'name' => 'English', 'folder' => 'en']);

        $this->root = sys_get_temp_dir().'/atrium-pages-'.bin2hex(random_bytes(4));

        $this->write('index.php', $this->page('Home', '<p>Welcome.</p>'));
        $this->write('about/.section.php', '<? $sSectionName = "About the company"; ?>');
        $this->write('about/index.php', $this->page('About us', '<p>We make things.</p>', [
            'description' => 'Who we are',
            'keywords' => 'company, about',
        ]));
        $this->write('about/team.php', $this->page('Our team', '<p>Twelve "great" people.</p>'.
            '<?$APPLICATION->IncludeComponent("bitrix:main.include", "", ["PATH" => "/include/phone.php"]);?>'));
        $this->write('about/history/index.php', $this->page('History', '<p>Since 1999.</p>'));
        $this->write('services/.section.php', "<?\n\$sSectionName = 'Services';\n?>");
        $this->write('services/index.php', $this->page('Services', '<?$APPLICATION->IncludeComponent("bitrix:news.list", "services", []);?>'));
        $this->write('services/design.php', $this->page('Design', '<p>Logos and sites.</p>'));
        $this->write('price_list.php', $this->page('Prices', '<table><tr><td>1</td></tr></table>'));
        $this->write('contacts.php', (string) mb_convert_encoding($this->page('Контакты', '<p>Минск</p>'), 'Windows-1251', 'UTF-8'));
        $this->write('include/phone.php', '+375 00 000-00-00');
        $this->write('ajax.php', '<?php echo json_encode([]);');
        $this->write('404.php', $this->page('Not found', '<p>Nothing here.</p>'));
        $this->write('bitrix/admin/index.php', $this->page('Admin', '<p>Bitrix itself.</p>'));
        $this->write('upload/file.php', $this->page('Upload', '<p>A file.</p>'));
        $this->write('en/about.php', $this->page('About (English site)', '<p>Another site.</p>'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_a_page_file_is_read_without_running_it(): void
    {
        $file = StaticPageFile::parse($this->page('About "us"', "<p>Text</p>\n<?=\$x?>\n<p>More</p>", ['title' => "It's us"]));

        $this->assertTrue($file->isPage);
        $this->assertSame('About "us"', $file->title);
        $this->assertSame(['title' => "It's us"], $file->properties);
        $this->assertSame("<p>Text</p>\n\n<p>More</p>", $file->content);

        $this->assertFalse(StaticPageFile::parse('<?php echo 1;')->isPage);
    }

    public function test_static_pages_move_with_their_addresses_titles_and_seo(): void
    {
        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root])
            ->expectsOutputToContain('made of components (bitrix:news.list)')
            ->expectsOutputToContain('Created 7, updated 0 pages')
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['about', 'about/team', 'about/history', 'services', 'services/design', 'price_list', 'contacts'],
            Page::query()->get()->map(fn (Page $page): string => $this->path($page))->all(),
        );

        $about = Page::query()->where('code', 'about')->sole();
        $this->assertSame('About us', $about->name);
        $this->assertSame('<p>We make things.</p>', $about->content);
        $this->assertSame('Who we are', $about->getSeoDescription());
        $this->assertSame('company, about', $about->getSeoKeywords());
        $this->assertSame('about/index.php', $about->source);

        // The include area is dropped, the text stays.
        $this->assertSame('<p>Twelve "great" people.</p>', Page::query()->where('code', 'team')->sole()->content);

        // services/index.php is a component: the folder becomes a page that
        // lists its pages, named from .section.php.
        $services = Page::query()->where('code', 'services')->sole();
        $this->assertSame('Services', $services->name);
        $this->assertSame('children', $services->template);
        $this->assertNull($services->content);

        $this->assertSame('Контакты', Page::query()->where('code', 'contacts')->sole()->name);

        $this->get('/about/history')->assertOk()->assertSee('<p>Since 1999.</p>', false);
        $this->get('/price_list')->assertOk();
        $this->get('/services')->assertOk()->assertSee('Design');
        $this->get('/about/team.php')->assertRedirect($this->root().'/about/team');
    }

    public function test_a_second_run_updates_the_pages_it_moved(): void
    {
        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root])->assertSuccessful();

        Page::query()->where('code', 'about')->sole()->update(['sort' => 10]);
        $this->write('about/index.php', $this->page('About', '<p>We make better things.</p>'));

        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root])
            ->expectsOutputToContain('Created 0, updated 7 pages')
            ->assertSuccessful();

        $about = Page::query()->where('code', 'about')->sole();
        $this->assertSame('<p>We make better things.</p>', $about->content);
        // What editors changed in Atrium and the old site does not have stays.
        $this->assertSame(10, $about->sort);
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root, '--dry-run' => true])
            ->expectsOutputToContain('/about/team')
            ->expectsOutputToContain('Would create 7')
            ->assertSuccessful();

        $this->assertSame(0, Page::query()->count());
    }

    public function test_pages_go_to_the_site_given(): void
    {
        $site = Site::query()->create(['code' => 's2', 'name' => 'Second', 'domains' => ['second.test']]);

        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root.'/en', '--site' => 's2'])->assertSuccessful();

        $this->assertSame([$site->id], Page::query()->withoutGlobalScopes()->pluck('site_id')->unique()->values()->all());

        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root, '--site' => 'nope'])->assertFailed();
        $this->artisan('atrium:pages:import-bitrix', ['path' => $this->root.'/missing'])->assertFailed();
    }

    /**
     * @param  array<string,string>  $properties
     */
    private function page(string $title, string $body, array $properties = []): string
    {
        $head = "<?\nrequire(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/header.php\");\n";

        foreach ($properties as $name => $value) {
            $head .= '$APPLICATION->SetPageProperty("'.$name.'", "'.addcslashes($value, '"\\$').'");'."\n";
        }

        $head .= '$APPLICATION->SetTitle("'.addcslashes($title, '"\\$').'");'."\n?>\n";

        return $head.$body."\n<?require(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/footer.php\");?>\n";
    }

    private function write(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($this->root.'/'.$path));
        File::put($this->root.'/'.$path, $contents);
    }

    private function path(Page $page): string
    {
        $parts = [$page->code];

        for ($parent = $page->parent; $parent !== null; $parent = $parent->parent) {
            array_unshift($parts, $parent->code);
        }

        return implode('/', $parts);
    }
}
