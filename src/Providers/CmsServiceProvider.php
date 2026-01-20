<?php

namespace Buni\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Buni\Cms\Services\PluginManager;
use Buni\Cms\Services\ThemeManager;
use Buni\Cms\Services\HookManager;
use Buni\Cms\Services\PageBuilder;

class CmsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/cms.php', 'cms');

        $this->app->singleton(PluginManager::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(HookManager::class);
        $this->app->singleton(PageBuilder::class);
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/cms.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'cms');

        // Auto-run migrations if tables don't exist
        if (!Schema::hasTable('pages')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Buni\Cms\Commands\InstallCommand::class,
                \Buni\Cms\Commands\CreatePluginCommand::class,
                \Buni\Cms\Commands\CreateThemeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../../config/cms.php' => config_path('cms.php'),
            __DIR__.'/../../resources/js' => resource_path('js/vendor/cms'),
        ], 'cms-config');
    }
}