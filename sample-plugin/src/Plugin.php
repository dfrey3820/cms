<?php

namespace Buni\Plugins\SamplePlugin;

use Buni\Cms\Contracts\PluginInterface;
use Buni\Cms\Services\HookManager;

class Plugin implements PluginInterface
{
    public function register()
    {
        // Register hooks
        app(HookManager::class)->addAction('admin_menu', function () {
            // Add menu item
        });
    }

    public function boot()
    {
        // Boot logic
    }

    public function enable()
    {
        // Enable logic
    }

    public function disable()
    {
        // Disable logic
    }

    public function getName(): string
    {
        return 'Sample Plugin';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'A sample plugin demonstrating DSC CMS plugin structure';
    }
}