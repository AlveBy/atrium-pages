<?php

declare(strict_types=1);

namespace Atrium\Pages;

use Atrium\Pages\Console\ImportBitrixPagesCommand;
use Atrium\Pages\Http\Controllers\PageController;
use Atrium\Pages\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class PagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-pages.php', 'atrium-pages');
        $this->app->singleton(PageTree::class);

        config()->push('atrium.resources', Page::class);

        // The address under the code field and `public_url` in the API: the
        // path of the page's codes from the root of the site. An empty
        // prefix keeps the theme from drawing pages itself.
        if (! array_key_exists(Page::class, (array) config('atrium.public_urls', []))) {
            config()->set('atrium.public_urls.'.Page::class, ['prefix' => '', 'tree' => Page::class, 'parent' => 'parent_id']);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-pages');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-pages');

        if (config('atrium-pages.routes', true) && ! $this->app->routesAreCached()) {
            $this->registerRoutes();
        }

        if ($this->app->runningInConsole()) {
            $this->commands([ImportBitrixPagesCommand::class]);

            $this->publishes([
                __DIR__.'/../config/atrium-pages.php' => config_path('atrium-pages.php'),
            ], 'atrium-pages-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/atrium-pages'),
            ], 'atrium-pages-views');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-pages'),
            ], 'atrium-pages-lang');
        }
    }

    /**
     * One catch-all route, marked as a fallback: Laravel tries it after
     * every other route, wherever it was registered. The language prefix
     * is read by the controller, because "/de/…" may as well be a page
     * whose code is "de".
     */
    private function registerRoutes(): void
    {
        Route::middleware(['web', 'atrium.site'])->group(function (): void {
            Route::get('{path}', [PageController::class, 'show'])
                ->where('path', '.+')
                ->name('atrium.pages.show')
                ->fallback();
        });
    }
}
