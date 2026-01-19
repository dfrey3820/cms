<?php

namespace Dsc\Cms\Commands;

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
        Artisan::call('vendor:publish', ['--provider' => 'Dsc\Cms\Providers\CmsServiceProvider', '--tag' => 'cms-config']);

        // Run migrations
        Artisan::call('migrate');

        // Seed roles and permissions
        Artisan::call('db:seed', ['--class' => 'Dsc\Cms\Database\Seeders\CmsSeeder']);

        $this->info('DSC CMS installed successfully!');
    }
}