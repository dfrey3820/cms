<?php

namespace Dsc\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Dsc\Cms\Services\PluginManager;
use Dsc\Cms\Services\ThemeManager;
use Dsc\Cms\Services\HookManager;
use Dsc\Cms\Services\PageBuilder;

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

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Dsc\Cms\Commands\InstallCommand::class,
                \Dsc\Cms\Commands\CreatePluginCommand::class,
                \Dsc\Cms\Commands\CreateThemeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../../config/cms.php' => config_path('cms.php'),
            __DIR__.'/../../resources/js' => resource_path('js/vendor/cms'),
        ], 'cms-config');
    }
}