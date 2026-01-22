<?php

namespace Buni\Cms\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'cms:install';
    protected $description = 'Install the DSC CMS package';

    public function handle()
    {
        $this->info('Installing DSC CMS...');

        // Publish config
        Artisan::call('vendor:publish', ['--provider' => 'Buni\Cms\Providers\CmsServiceProvider', '--tag' => 'cms-config']);

        // Run migrations
        Artisan::call('migrate');

        // Seed roles and permissions
        Artisan::call('db:seed', ['--class' => 'Buni\Cms\Database\Seeders\CmsSeeder']);

        // Attempt to build Tailwind CSS for the core (will use fallback if npx unavailable)
        try {
            Artisan::call('cms:build-tailwind');
        } catch (\Exception $e) {
            $this->warn('Automatic Tailwind build failed: ' . $e->getMessage());
        }

        $this->info('DSC CMS installed successfully!');
    }
}