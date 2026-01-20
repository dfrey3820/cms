<?php

namespace Buni\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

        // Auto-run migrations if tables don't exist and database is accessible
        try {
            // Skip database checks during installation or if database is not properly configured
            $dbConnection = config('database.default');
            $dbConfig = config('database.connections.' . $dbConnection);

            // For SQLite, ensure the database path is valid and file exists
            if ($dbConnection === 'sqlite') {
                $dbPath = $dbConfig['database'] ?? '';
                if (empty($dbPath)) {
                    // Skip migration for missing database config
                    return;
                }

                // Convert relative paths to absolute
                if (!str_starts_with($dbPath, '/')) {
                    $dbPath = database_path($dbPath);
                }

                // Create database directory and file if they don't exist
                $dbDir = dirname($dbPath);
                if (!File::exists($dbDir)) {
                    File::makeDirectory($dbDir, 0755, true);
                }
                if (!File::exists($dbPath)) {
                    File::put($dbPath, '');
                }
            }

            // First check if we can connect to the database
            DB::connection()->getPdo();

            if (!Schema::hasTable('pages')) {
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Exception $e) {
            // Database might not be set up yet, skip migration for now
            // This can happen during installation or if database config is invalid
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