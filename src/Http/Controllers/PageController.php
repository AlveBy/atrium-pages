<?php

declare(strict_types=1);

namespace Atrium\Pages\Http\Controllers;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Pages\Models\Page;
use Atrium\Pages\PageTree;
use Atrium\Pages\Templates;
use Atrium\Theme\Pages as ThemePages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A page at the path of its codes, drawn with the template its editor
 * picked inside the theme's layout.
 */
final class PageController extends Controller
{
    public function __construct(
        private readonly PageTree $tree,
        private readonly ContentLanguages $languages,
        private readonly ThemePages $theme,
    ) {}

    public function show(string $path): View|RedirectResponse
    {
        [$language, $page, $old] = $this->resolve(trim($path, '/'));

        abort_if($page === null, 404);

        $url = $this->tree->url($page, $language);

        abort_if($url === null, 404);

        // An address of the old site (/about/index.php, /about/team.php):
        // send visitors and search engines to the page's address.
        if ($old) {
            return redirect()->to($url, 301);
        }

        app()->setLocale($language);

        $urls = $this->tree->urls($page);
        $switch = [];

        foreach ($this->languages->codes() as $code) {
            $switch[$code] = $urls[$code] ?? $this->theme->homeUrl($code);
        }

        $breadcrumbs = $this->tree->ancestors($page)
            ->map(fn (Page $ancestor): array => ['label' => (string) $ancestor->name, 'url' => (string) $this->tree->url($ancestor, $language)])
            ->all();

        $children = $this->tree->children($page)
            ->map(fn (Page $child): array => [
                'title' => (string) $child->name,
                'url' => $this->tree->url($child, $language),
                'text' => $child->getSeoDescription(),
            ])
            ->filter(fn (array $child): bool => $child['url'] !== null)
            ->values()
            ->all();

        $content = trim((string) $page->content);

        return view(Templates::view($page->template), [
            'page' => $page,
            'title' => (string) $page->name,
            'content' => $content === '' ? null : new HtmlString($content),
            'children' => $children,
            'canonical' => $url,
            'breadcrumbs' => $breadcrumbs,
            'alternates' => $urls,
            'switch' => $switch,
        ]);
    }

    /**
     * The language, the page and whether the address is an old one. A
     * leading language code is the language when a page is found below it,
     * and part of the path otherwise: a page may have the code "de".
     *
     * @return array{0:string,1:?Page,2:bool}
     */
    private function resolve(string $path): array
    {
        $default = $this->languages->default();
        $first = Str::before($path, '/');

        if ($first !== $path && $first !== $default && $this->languages->has($first)) {
            [$page, $old] = $this->find(Str::after($path, '/'));

            if ($page !== null) {
                return [$first, $page, $old];
            }
        }

        return [$default, ...$this->find($path)];
    }

    /**
     * @return array{0:?Page,1:bool}
     */
    private function find(string $path): array
    {
        if (! str_ends_with($path, '.php')) {
            return [$this->tree->find($path), false];
        }

        // Bitrix served /about/ from /about/index.php and /about/team/ from
        // /about/team.php: both moved to /about and /about/team.
        $path = preg_replace('#(^|/)index\.php$#', '', $path) ?? $path;
        $path = preg_replace('#\.php$#', '', $path) ?? $path;

        return [$path === '' ? null : $this->tree->find($path), true];
    }
}
