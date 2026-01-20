<?php

namespace Buni\Themes\Default;

use Buni\Cms\Contracts\ThemeInterface;
use Illuminate\Support\Facades\File;

class Theme implements ThemeInterface
{
    protected $metadata = [];

    public function __construct()
    {
        $metadataFile = dirname(__DIR__) . '/theme.json';
        if (File::exists($metadataFile)) {
            $this->metadata = json_decode(File::get($metadataFile), true);
        }
    }

    public function register()
    {
        // Register theme assets
    }

    public function boot()
    {
        // Include theme.php like WordPress functions.php
        $themeFile = dirname(__DIR__) . '/theme.php';
        if (file_exists($themeFile)) {
            include $themeFile;
        }
    }

    public function getName(): string
    {
        return $this->metadata['name'] ?? 'Default';
    }

    public function getDescription(): string
    {
        return $this->metadata['description'] ?? 'The default theme for Buni CMS';
    }

    public function getVersion(): string
    {
        return $this->metadata['version'] ?? '1.0.0';
    }

    public function getAuthor(): string
    {
        return $this->metadata['author'] ?? '';
    }

    public function getAuthorUrl(): string
    {
        return $this->metadata['author_url'] ?? '';
    }

    public function getType(): string
    {
        return $this->metadata['type'] ?? 'client';
    }
}