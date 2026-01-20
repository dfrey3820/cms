<?php

namespace Buni\Themes\Default;

use Buni\Cms\Contracts\ThemeInterface;

class Theme implements ThemeInterface
{
    public function register()
    {
        // Register theme assets
    }

    public function boot()
    {
        // Boot theme
    }

    public function getName(): string
    {
        return 'Sample Theme';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'A sample theme demonstrating DSC CMS theme structure';
    }

    public function getLayout(): string
    {
        return 'layouts.app';
    }
}