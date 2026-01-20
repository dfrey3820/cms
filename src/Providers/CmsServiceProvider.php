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
        // Check if .env file exists and create minimal one if needed
        $envFile = base_path('.env');
        if (!file_exists($envFile) || !is_readable($envFile) || trim(file_get_contents($envFile)) === '') {
            // Create minimal .env file with APP_KEY for Laravel to boot
            $minimalEnv = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\nAPP_URL=http://localhost\n";
            File::put($envFile, $minimalEnv);

            // Generate APP_KEY immediately
            try {
                Artisan::call('key:generate', ['--force' => true]);
            } catch (\Exception $e) {
                // If key generation fails, set a fallback key
                $fallbackKey = 'base64:' . base64_encode(random_bytes(32));
                $envContent = File::get($envFile);
                $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $fallbackKey, $envContent);
                File::put($envFile, $envContent);
            }
        }

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