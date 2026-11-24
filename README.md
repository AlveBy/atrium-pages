# Atrium Pages

Static pages for [Atrium](https://github.com/AlveBy/atrium): about us, contacts, delivery terms. Pages make a tree and open at the path of their codes from the root of the site (`/about/team`), in every content language, per site, with SEO fields and a choice of templates. Static pages of a 1C-Bitrix site move over at their old addresses.

## Install

```bash
composer require alveby/atrium-pages
php artisan atrium:module:install alveby/atrium-pages
```

Add `--demo` to fill it with a shop's pages (about us with two subpages, delivery and payment, contacts) in English, German and Russian; the German and Russian texts show once those languages are on.

"Pages" appears in the admin menu. A page has a name, a code (the last part of its address), a parent page, a template, a sort order, an active flag, a text (a `ContentField`: visual editor, HTML or plain text) and an SEO tab (title, description, keywords, picture for sharing).

- The name and the text are written per content language; only the default language is required. The code is one for all languages: `/about` and `/de/about`.
- Pages belong to a site: each site has its own tree.
- Pages under one parent need different codes. A code may have capitals and underscores, so moved pages keep addresses like `/price_list`.
- "Subpage", in a page's row and on its form, opens a new page with that page as its parent.
- Deleting a page moves its subpages up to its parent.

## On the site

The pages are drawn inside the layout of the public theme (`alveby/atrium-theme`, installed with this package):

| Address | Page |
|---|---|
| `/about` | a top-level page |
| `/about/team` | a page below it, with breadcrumbs |
| `/de/about/team` | the same in another content language, when its name is written in it |
| `/about/index.php`, `/about/team.php` | a 301 to the page: old Bitrix addresses |

A page is shown when it and every page above it are active; hiding a page hides its branch. Visible pages go into `sitemap.xml` in every language they are written in, and the slug field in the admin and `public_url` in the API show the page's address.

The route is a fallback: every route of your app, the admin and the theme is matched first, and an address no page has is a 404. `ATRIUM_PAGES_ROUTES=false` turns it off, to draw the pages with routes of your own (`Atrium\Pages\PageTree` finds a page by its path and builds its addresses).

## Templates

The editor picks one in the page form:

| Key | Page |
|---|---|
| `default` | heading and text |
| `children` | heading, text and a card for each subpage |
| `landing` | the text across the whole width, no heading, no breadcrumbs |

Add your own in `config/atrium-pages.php` (`php artisan vendor:publish --tag=atrium-pages-config`), key => Blade view:

```php
'templates' => [
    'default' => 'atrium-pages::templates.default',
    'children' => 'atrium-pages::templates.children',
    'landing' => 'atrium-pages::templates.landing',
    'contacts' => 'pages.contacts',
],
```

Templates are also found as files, the same way as for every module: a Blade file in `resources/views/templates/pages/` (or `templates/all/` for all modules) whose first line is `{{-- Template: Press page --}}` is offered in the form as it is, and the theme's **Full width** (`templates/all/full-width`) is offered for pages too. The demo's contacts page uses it. How: [Page templates](../../docs/modules.md#page-templates). The choice stays in the page's `template` column.

A view gets `$page` (the model), `$title`, `$content`, `$children` (`title`, `url` and `text` of each visible subpage) and the layout's `canonical`, `breadcrumbs`, `alternates` and `switch`; it usually `@extends('atrium-theme::layout')`. The label in the form is the translation `atrium-pages::pages.templates.{key}`, or the key itself. A page whose template is gone from the config is drawn with `default`.

## Change the look

```bash
php artisan vendor:publish --tag=atrium-pages-views
php artisan vendor:publish --tag=atrium-pages-lang
```

The admin labels are in 15 languages; English and Russian are written by hand, the others are machine translations.

## Moving from 1C-Bitrix

Static pages of a Bitrix site are `.php` files outside the iblocks. With a copy of the site's files:

```bash
php artisan atrium:pages:import-bitrix /path/to/bitrix/site --dry-run
php artisan atrium:pages:import-bitrix /path/to/bitrix/site
```

- `/about/index.php` becomes the page `about`, `/about/team.php` the page `team` below it, `/about/history/index.php` the page `history` below `about`: the same addresses.
- The name is the `SetTitle()` of the file, the text is what lies between the header and the footer. `SetPageProperty()` `title`, `description` and `keywords` go to the SEO tab. Files in windows-1251 are found and converted (`--encoding` to name another one).
- Components and include areas inside the text are dropped, and the report names them. A file made only of components (a news list, a catalog) is skipped: that content moves with the iblocks (`alveby/atrium-bitrix`). A folder of such a file that still holds pages becomes a page listing them, named from its `.section.php`.
- Skipped: the home page `/index.php`, files without the Bitrix header (include areas, AJAX handlers), `404.php`, `urlrewrite.php`, files and folders starting with `.` or `_`, and the folders `bitrix`, `upload` and `local`.
- `--site=code` puts the pages into that Atrium site (the default site otherwise), `--language=de` writes the texts in that content language. Folders of other sites on the same files (a site in `/en/`) are left to those sites: import them with `--site=en` from `/path/to/bitrix/site/en`.
- Run it again after the old site changes: pages are matched by their file and updated; their sort order, flags and template stay as editors set them in Atrium.

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/pages/tests
```

The tests cover the admin forms, the pages in two languages and on two sites, the templates, the sitemap and the Bitrix import, on SQLite, MySQL and PostgreSQL.

## Contributing

This repository is a read-only copy of `packages/pages` in [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
