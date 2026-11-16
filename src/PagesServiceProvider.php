<?php

declare(strict_types=1);

namespace Atrium\Pages;

use Atrium\Admin\Modules\Modules;
use Atrium\Pages\Console\ImportBitrixPagesCommand;
use Atrium\Pages\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The model, its pages' addresses and the migrations are in composer.json
 * (extra.atrium) and come in with `atrium:module:install`.
 */
final class PagesServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-pages';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-pages.php', 'atrium-pages');
        $this->app->singleton(PageTree::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-pages');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-pages');

        // Not installed, the module has no table: every unknown address
        // would fail instead of being a 404.
        $installed = $this->app->make(Modules::class)->isInstalled(self::MODULE);

        if ($installed && config('atrium-pages.routes', true) && ! $this->app->routesAreCached()) {
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
