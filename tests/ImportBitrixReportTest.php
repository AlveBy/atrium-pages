<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Pages\Tests;

use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\Pages\Models\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * What the import reports for each file, how it names the folders it has
 * to make pages of, and the SEO it carries over.
 */
final class ImportBitrixReportTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/atrium-pages-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->root);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_every_file_is_reported_with_its_address_and_what_became_of_it(): void
    {
        $this->write('index.php', $this->page('Home', '<p>Welcome.</p>'));
        $this->write('x_y.php', $this->page('Underscored', '<p>Kept.</p>'));
        $this->write('_hidden.php', $this->page('Hidden', '<p>Not a page.</p>'));
        $this->write('.hidden.php', $this->page('Hidden', '<p>Not a page.</p>'));
        $this->write('empty.php', $this->page('Empty', ''));
        $this->write('ajax.php', '<?php echo json_encode([]);');
        $this->write('team.php', $this->page('Team', '<p>Us.</p>'.$this->includeComponent('bitrix:main.include').$this->includeComponent('bitrix:menu')));
        $this->write('docs/empty.php', $this->page('Empty too', ''));
        $this->write('promo/index.php', $this->page('Promo', $this->includeComponent('bitrix:news.list')));
        $this->write('news/.section.php', '<? $sSectionName = "Latest news"; ?>');
        $this->write('news/index.php', $this->page('News', $this->includeComponent('bitrix:news.list')));
        $this->write('news/item.php', $this->page('Item', '<p>An item.</p>'));

        $output = $this->import();

        $this->assertMatchesRegularExpression('#\|\s*File\s*\|\s*Address\s*\|\s*Result\s*\|#', $output);
        $this->assertRow($output, 'index.php', '/', 'skipped: the home page is drawn by the theme or the app');
        $this->assertRow($output, 'x_y.php', '/x_y', 'ok');
        $this->assertRow($output, 'empty.php', '/empty', 'skipped: no text');
        $this->assertRow($output, 'docs/empty.php', '/docs/empty', 'skipped: no text');
        $this->assertRow($output, 'promo/index.php', '/promo', 'skipped: made of components (bitrix:news.list), move them as modules');
        $this->assertRow($output, 'team.php', '/team', 'ok; components dropped: bitrix:main.include, bitrix:menu');
        $this->assertRow($output, 'news/', '/news', 'ok; a page listing the pages of the folder');
        $this->assertRow($output, 'news/item.php', '/news/item', 'ok');
        $this->assertStringNotContainsString('ajax.php', $output);
        $this->assertStringNotContainsString('hidden', $output);
        $this->assertStringContainsString('Created 4, updated 0 pages of the site main.', $output);

        $this->assertEqualsCanonicalizing(['x_y', 'team', 'news', 'item'], Page::query()->pluck('code')->all());
        $this->assertSame('Latest news', Page::query()->where('code', 'news')->sole()->name);
    }

    public function test_a_folder_without_a_page_is_named_by_its_section_file_else_its_index_else_its_code(): void
    {
        // a/ has no index.php; the page of a/b/ is its index.php.
        $this->write('a/.section.php', '<? $sSectionName = "Section A"; ?>');
        $this->write('a/b/.section.php', '<? $sSectionName = "Section B"; ?>');
        $this->write('a/b/index.php', $this->page('Page B', '<p>B.</p>'));
        // x/y/ and x/ have no pages of their own.
        $this->write('x/.section.php', '<? $sSectionName = "Folder X"; ?>');
        $this->write('x/y/.section.php', '<? $sSectionName = "Folder Y"; ?>');
        $this->write('x/y/z.php', $this->page('Z', '<p>Z.</p>'));
        // A list drawn by a component, without a section file: named by its title.
        $this->write('events/index.php', $this->page('Our events', $this->includeComponent('bitrix:news.list')));
        $this->write('events/party.php', $this->page('Party', '<p>Party.</p>'));
        // Neither: named by its code.
        $this->write('old-stuff/thing.php', $this->page('Thing', '<p>Thing.</p>'));
        // A section file without a name does not name the folder.
        $this->write('misc/.section.php', '<? $arDirProperties = []; ?>');
        $this->write('misc/note.php', $this->page('Note', '<p>Note.</p>'));
        // A file whose title is empty is not named from its folder.
        $this->write('more/.section.php', '<? $sSectionName = "More"; ?>');
        $this->write('more/untitled-page.php', $this->page('', '<p>No title.</p>'));

        $output = $this->import();

        $names = Page::query()->get()->mapWithKeys(fn (Page $page): array => [$page->code => $page->name])->all();
        $this->assertSame('Section A', $names['a']);
        $this->assertSame('Page B', $names['b']);
        $this->assertSame('Folder X', $names['x']);
        $this->assertSame('Folder Y', $names['y']);
        $this->assertSame('Our events', $names['events']);
        $this->assertSame('Old Stuff', $names['old-stuff']);
        $this->assertSame('Misc', $names['misc']);
        $this->assertSame('Untitled Page', $names['untitled-page']);

        $this->assertSame('x', Page::query()->where('code', 'y')->sole()->parent?->code);
        $this->assertNull(Page::query()->where('code', 'x')->sole()->parent_id);
        $this->assertSame('a', Page::query()->where('code', 'b')->sole()->parent?->code);
        $this->assertRow($output, 'x/y/z.php', '/x/y/z', 'ok');
        $this->assertRow($output, 'x/y/', '/x/y', 'ok; a page listing the pages of the folder');
        $this->assertRow($output, 'x/', '/x', 'ok; a page listing the pages of the folder');
        $this->assertRow($output, 'a/b/index.php', '/a/b', 'ok');
        $this->assertRow($output, 'a/', '/a', 'ok; a page listing the pages of the folder');
    }

    public function test_numbered_folders_are_imported_in_numeric_order(): void
    {
        $this->write('10/index.php', $this->page('Ten', '<p>Ten.</p>'));
        $this->write('9/index.php', $this->page('Nine', '<p>Nine.</p>'));

        $this->import();

        $this->assertSame(['9', '10'], Page::query()->orderBy('id')->pluck('code')->all());
    }

    public function test_a_file_name_that_is_not_an_address_is_made_one(): void
    {
        $this->write('Über uns.php', $this->page('About', '<p>About.</p>'));
        $this->write('!!!.php', $this->page('Bangs', '<p>Bangs.</p>'));

        $this->import();

        $this->assertEqualsCanonicalizing(['uber-uns', 'page'], Page::query()->pluck('code')->all());
    }

    public function test_an_address_is_shown_twenty_levels_deep_at_most(): void
    {
        $folders = implode('/', array_map(fn (int $level): string => "f{$level}", range(1, 21)));
        $this->write($folders.'/p.php', $this->page('Deep', '<p>Deep.</p>'));

        $output = $this->import();

        $shown = '/'.implode('/', array_map(fn (int $level): string => "f{$level}", range(3, 21))).'/p';
        $this->assertRow($output, $folders.'/p.php', $shown, 'ok');
    }

    public function test_the_seo_of_a_page_is_carried_over_within_its_limits(): void
    {
        $long = str_repeat('Ж', 256);
        $this->write('long.php', $this->page('Long', '<p>Long.</p>', ['title' => $long, 'keywords' => $long, 'description' => 'Описание']));
        $this->write('short.php', $this->page('Short', '<p>Short.</p>', ['title' => 'Exactly this']));
        $this->write('plain.php', $this->page('Plain', '<p>Plain.</p>'));

        $this->import();

        $long = Page::query()->where('code', 'long')->sole()->seoMeta;
        $this->assertSame(str_repeat('Ж', 255), $long?->title);
        $this->assertSame(str_repeat('Ж', 255), $long?->keywords);
        $this->assertSame('Описание', $long?->description);

        $short = Page::query()->where('code', 'short')->sole()->seoMeta;
        $this->assertSame('Exactly this', $short?->title);
        $this->assertNull($short?->description);
        $this->assertNull($short?->keywords);

        $this->assertNull(Page::query()->where('code', 'plain')->sole()->seoMeta);
    }

    public function test_files_in_another_encoding_are_read_in_it(): void
    {
        $iso = fn (string $text): string => (string) mb_convert_encoding($text, 'ISO-8859-5', 'UTF-8');
        $this->write('contacts.php', $iso($this->page('Контакты', '<p>Минск</p>')));
        $this->write('club/.section.php', $iso('<? $sSectionName = "Клуб"; ?>'));
        $this->write('club/rules.php', $iso($this->page('Правила', '<p>Правила клуба</p>')));

        $this->import(['--encoding' => 'ISO-8859-5']);

        $this->assertSame('Контакты', Page::query()->where('code', 'contacts')->sole()->name);
        $this->assertSame('<p>Минск</p>', Page::query()->where('code', 'contacts')->sole()->content);
        $this->assertSame('Клуб', Page::query()->where('code', 'club')->sole()->name);
    }

    public function test_another_site_folder_written_with_slashes_is_still_left_out(): void
    {
        $other = Site::query()->create(['code' => 'en', 'name' => 'English', 'folder' => 'en']);
        // Written past the model, which would tidy it.
        Site::query()->whereKey($other->id)->update(['folder' => '/en/']);
        app(Sites::class)->flush();
        $this->write('about.php', $this->page('About', '<p>About.</p>'));
        $this->write('en/about.php', $this->page('About (English site)', '<p>Another site.</p>'));

        $this->import();

        $this->assertSame(['about'], Page::query()->pluck('code')->all());
    }

    public function test_the_root_site_written_with_a_slash_leaves_the_other_sites_out(): void
    {
        Site::query()->create(['code' => 'en', 'name' => 'English', 'folder' => 'en']);
        Site::query()->whereKey(app(Sites::class)->default()->id)->update(['folder' => '/']);
        app(Sites::class)->flush();
        $this->write('about.php', $this->page('About', '<p>About.</p>'));
        $this->write('en/about.php', $this->page('About (English site)', '<p>Another site.</p>'));

        $this->import();

        $this->assertSame(['about'], Page::query()->withoutGlobalScopes()->pluck('code')->all());
    }

    public function test_a_site_in_a_folder_takes_every_folder_of_its_files(): void
    {
        Site::query()->create(['code' => 'en', 'name' => 'English', 'folder' => 'en']);
        Site::query()->create(['code' => 'ru', 'name' => 'Russian', 'folder' => 'ru']);
        $this->write('en/about.php', $this->page('About', '<p>About.</p>'));

        $this->import(['--site' => 'ru']);

        $this->assertSame(['en', 'about'], Page::query()->withoutGlobalScopes()->orderBy('id')->pluck('code')->all());
    }

    public function test_what_is_wrong_with_the_command_is_said(): void
    {
        $this->assertSame(1, Artisan::call('atrium:pages:import-bitrix', ['path' => $this->root.'/missing/']));
        $this->assertSame("No such folder: {$this->root}/missing", trim(Artisan::output()));

        $this->assertSame(1, Artisan::call('atrium:pages:import-bitrix', ['path' => $this->root, '--site' => 'nope']));
        $this->assertSame('No site with the code nope.', trim(Artisan::output()));

        $this->assertSame(1, Artisan::call('atrium:pages:import-bitrix', ['path' => $this->root, '--language' => 'xx']));
        $this->assertSame('xx is not an active content language of the site.', trim(Artisan::output()));

        $this->assertSame(0, Page::query()->count());
    }

    public function test_the_trailing_slash_of_the_path_does_not_matter(): void
    {
        $this->write('about.php', $this->page('About', '<p>About.</p>'));

        $this->import(['path' => $this->root.'/']);

        $this->assertSame(['about'], Page::query()->pluck('code')->all());
    }

    /**
     * @param  array<string,mixed>  $options
     */
    private function import(array $options = []): string
    {
        $this->assertSame(0, Artisan::call('atrium:pages:import-bitrix', ['path' => $this->root, ...$options]));

        return Artisan::output();
    }

    private function assertRow(string $output, string $file, string $address, string $result): void
    {
        $this->assertMatchesRegularExpression('#\|\s*'.preg_quote($file, '#').'\s*\|\s*'.preg_quote($address, '#').'\s*\|\s*'.preg_quote($result, '#').'\s*\|#', $output);
    }

    private function includeComponent(string $name): string
    {
        return "<?\$APPLICATION->IncludeComponent(\"{$name}\", \"\", []);?>";
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
}
