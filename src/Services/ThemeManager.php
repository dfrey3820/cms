<?php

namespace Buni\Cms\Services;

use Buni\Cms\Contracts\ThemeInterface;
use Illuminate\Support\Facades\File;

class ThemeManager
{
    protected $themes = [];
    protected $activeTheme;

    public function loadThemes()
    {
        $themePath = config('cms.themes_path', base_path('themes'));

        if (!File::exists($themePath)) return;

        $directories = File::directories($themePath);

        foreach ($directories as $dir) {
            $themeClass = $this->getThemeClass($dir);

            if (class_exists($themeClass) && in_array(ThemeInterface::class, class_implements($themeClass))) {
                $theme = new $themeClass();
                $this->themes[$theme->getName()] = $theme;
                $theme->register();
            }
        }
    }

    public function bootThemes()
    {
        foreach ($this->themes as $theme) {
            $theme->boot();
        }
    }

    public function setActiveTheme($name)
    {
        if (isset($this->themes[$name])) {
            $this->activeTheme = $this->themes[$name];
        }
    }

    public function getActiveTheme()
    {
        return $this->activeTheme;
    }

    protected function getThemeClass($path)
    {
        $themeFile = $path . '/src/Theme.php';
        if (File::exists($themeFile)) {
            require_once $themeFile;
            $dirName = basename($path);
            return 'Buni\\Themes\\' . ucfirst($dirName) . '\\Theme';
        }
        return null;
    }
}