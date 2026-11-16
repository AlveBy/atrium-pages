<?php

declare(strict_types=1);

namespace Atrium\Pages\Console;

use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Support\Sites;
use Atrium\Pages\Bitrix\StaticPageFile;
use Atrium\Pages\Fields\PageCodeField;
use Atrium\Pages\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Static pages of a Bitrix site, the .php files outside the iblocks, become
 * pages: /about/index.php is the page "about", /about/team.php the page
 * "team" below it, at the same addresses. Run it again after the old site
 * changes: pages are matched by their file and updated.
 */
final class ImportBitrixPagesCommand extends Command
{
    protected $signature = 'atrium:pages:import-bitrix
                            {path : The document root of the Bitrix site (a copy of its files)}
                            {--site= : Code of the Atrium site the pages go to (the default site if empty)}
                            {--language= : Content language of the texts (the site\'s default if empty)}
                            {--encoding= : Encoding of the files (UTF-8, or windows-1251 found by itself)}
                            {--dry-run : Show what would be imported without writing}';

    protected $description = 'Import the static pages of a 1C-Bitrix site (.php files outside iblocks) as pages';

    /** Folders of Bitrix itself and of files, never pages. */
    private const SKIP_FOLDERS = ['bitrix', 'upload', 'local', 'cgi-bin', 'vendor', 'node_modules'];

    /** Files of a folder that are not pages of their own. */
    private const SKIP_FILES = ['404.php', 'urlrewrite.php'];

    /** @var array<string,Page> folder path => its page */
    private array $folders = [];

    /** @var array<int,?Page> page object id => its parent, also on a dry run */
    private array $parents = [];

    /** @var array<int,array{0:string,1:string,2:string}> */
    private array $report = [];

    private string $language;

    private int $created = 0;

    private int $updated = 0;

    public function handle(Sites $sites, ContentLanguages $languages): int
    {
        // The command object outlives one run when called from code.
        $this->folders = $this->parents = $this->report = [];
        $this->created = $this->updated = 0;

        $root = rtrim((string) $this->argument('path'), '/');

        if (! is_dir($root)) {
            $this->error("No such folder: {$root}");

            return self::FAILURE;
        }

        $site = $this->option('site') ? $sites->findByCode((string) $this->option('site')) : $sites->default();

        if (! $site instanceof Site) {
            $this->error('No site with the code '.$this->option('site').'.');

            return self::FAILURE;
        }

        // The pages are this site's: new ones get its id, lookups see only it.
        $sites->setCurrent($site);
        $this->language = (string) ($this->option('language') ?: $languages->default());

        if (! $languages->has($this->language)) {
            $this->error("{$this->language} is not an active content language of the site.");

            return self::FAILURE;
        }

        // The folders of other sites on the same files are theirs (/en/).
        $otherFolders = $sites->all()
            ->reject(fn (Site $other): bool => $other->is($site))
            ->pluck('folder')
            ->filter()
            ->map(fn (string $folder): string => trim($folder, '/'))
            ->all();

        $ownFolder = trim((string) $site->getAttribute('folder'), '/');

        $this->importFolder($root, '', $ownFolder === '' ? $otherFolders : []);

        $this->table(['File', 'Address', 'Result'], $this->report);

        $verb = $this->option('dry-run') ? 'Would create' : 'Created';
        $this->info("{$verb} {$this->created}, ".($this->option('dry-run') ? 'would update' : 'updated')." {$this->updated} pages of the site {$site->code}.");

        return self::SUCCESS;
    }

    /**
     * @param  array<int,string>  $skip  folder names to leave out at this level
     */
    private function importFolder(string $dir, string $relative, array $skip = []): void
    {
        $entries = scandir($dir) ?: [];
        sort($entries);

        $files = [];
        $folders = [];

        foreach ($entries as $entry) {
            if ($entry[0] === '.' || $entry[0] === '_') {
                continue;
            }

            $path = $dir.'/'.$entry;

            if (is_dir($path) && ! is_link($path)) {
                if (! in_array($entry, [...self::SKIP_FOLDERS, ...$skip], true)) {
                    $folders[] = $entry;
                }
            } elseif (str_ends_with($entry, '.php') && ! in_array($entry, self::SKIP_FILES, true)) {
                $files[] = $entry;
            }
        }

        // The folder's own page first: its files and subfolders go below it.
        if (in_array('index.php', $files, true)) {
            $this->importFile(new SplFileInfo($dir.'/index.php'), $relative.'index.php');
        }

        foreach ($files as $file) {
            if ($file !== 'index.php') {
                $this->importFile(new SplFileInfo($dir.'/'.$file), $relative.$file);
            }
        }

        foreach ($folders as $folder) {
            $this->importFolder($dir.'/'.$folder, $relative.$folder.'/');
        }
    }

    private function importFile(SplFileInfo $file, string $relative): void
    {
        if ($relative === 'index.php') {
            $this->report[] = [$relative, '/', 'skipped: the home page is drawn by the theme or the app'];

            return;
        }

        $read = StaticPageFile::read($file->getPathname(), $this->option('encoding') ?: null);

        if (! $read->isPage) {
            return;
        }

        $isIndex = $file->getFilename() === 'index.php';
        $folder = dirname($relative) === '.' ? '' : dirname($relative).'/';
        $code = $isIndex ? basename($folder) : $file->getBasename('.php');
        $parentFolder = $isIndex ? (dirname($folder) === '.' ? '' : dirname($folder).'/') : $folder;
        $title = $read->title !== '' ? $read->title : $this->sectionName(dirname($file->getPathname()), $isIndex, $code);

        if ($read->content === '') {
            $reason = $read->components === []
                ? 'skipped: no text'
                : 'skipped: made of components ('.implode(', ', $read->components).'), move them as modules';
            $this->report[] = [$relative, '/'.($isIndex ? rtrim($folder, '/') : $folder.$code), $reason];

            return;
        }

        $parent = $this->folderPage($parentFolder, dirname($file->getPathname()).($isIndex ? '/..' : ''));
        $page = $this->save($relative, $code, $title, $read->content, $parent, $read->properties);
        $this->parents[spl_object_id($page)] = $parent;

        if ($isIndex) {
            $this->folders[$folder] = $page;
        }

        $notes = [];

        if ($read->components !== []) {
            $notes[] = 'components dropped: '.implode(', ', $read->components);
        }

        $this->remember($relative, $this->address($page), $notes);
    }

    /**
     * The page of a folder. A folder whose index.php is not a page of text
     * (a list drawn by a component) still holds pages: it becomes a page
     * that lists them.
     */
    private function folderPage(string $folder, string $dir): ?Page
    {
        if ($folder === '') {
            return null;
        }

        if (isset($this->folders[$folder])) {
            return $this->folders[$folder];
        }

        $parentFolder = dirname($folder) === '.' ? '' : dirname($folder).'/';
        $parent = $this->folderPage($parentFolder, $dir.'/..');
        $code = basename($folder);
        $page = $this->save($folder, $code, $this->sectionName($dir, true, $code), null, $parent, [], 'children');
        $this->parents[spl_object_id($page)] = $parent;

        $this->remember($folder, $this->address($page), ['a page listing the pages of the folder']);

        return $this->folders[$folder] = $page;
    }

    /**
     * @param  array<string,string>  $properties
     */
    private function save(string $source, string $code, string $title, ?string $content, ?Page $parent, array $properties, ?string $template = null): Page
    {
        if (! preg_match(PageCodeField::PATTERN, $code)) {
            $code = Str::slug($code) ?: 'page';
        }

        $page = Page::query()->where('source', $source)->first() ?? new Page(['source' => $source]);
        $page->exists ? $this->updated++ : $this->created++;

        $page->code = $code;
        $page->parent_id = $parent?->id;
        $page->setTranslation('name', $this->language, $title);

        if ($content !== null) {
            $page->setTranslation('content', $this->language, $content);
        }

        if ($template !== null && ! $page->exists) {
            $page->template = $template;
        }

        if ($this->option('dry-run')) {
            return $page;
        }

        $page->save();

        // seo_meta keeps the title and the keywords in 255 characters.
        $seo = array_filter([
            'title' => mb_substr($properties['title'] ?? '', 0, 255),
            'description' => $properties['description'] ?? '',
            'keywords' => mb_substr($properties['keywords'] ?? '', 0, 255),
        ], static fn (string $value): bool => $value !== '');

        if ($seo !== []) {
            $page->updateSeoMeta($seo);
        }

        return $page;
    }

    /**
     * @param  array<int,string>  $notes
     */
    private function remember(string $source, string $address, array $notes): void
    {
        $this->report[] = [$source, $address, implode('; ', ['ok', ...$notes])];
    }

    /**
     * The page's address on the new site, from the pages of this run.
     */
    private function address(Page $page): string
    {
        $path = [];

        for ($cursor = $page, $depth = 0; $cursor !== null && $depth < 20; $depth++) {
            array_unshift($path, $cursor->code);
            $cursor = $this->parents[spl_object_id($cursor)] ?? null;
        }

        return '/'.implode('/', $path);
    }

    /**
     * A folder's name from its .section.php, else from its index.php, else
     * the code itself.
     */
    private function sectionName(string $dir, bool $ofFolder, string $code): string
    {
        $encoding = $this->option('encoding') ?: null;

        if ($ofFolder && is_file($dir.'/.section.php') && ($name = StaticPageFile::sectionName($dir.'/.section.php', $encoding)) !== '') {
            return $name;
        }

        if ($ofFolder && is_file($dir.'/index.php') && ($title = StaticPageFile::read($dir.'/index.php', $encoding)->title) !== '') {
            return $title;
        }

        return Str::headline($code);
    }
}
