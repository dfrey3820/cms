<?php

namespace Dsc\Cms\Services;

use Dsc\Cms\Contracts\PluginInterface;
use Illuminate\Support\Facades\File;

class PluginManager
{
    protected $plugins = [];

    public function loadPlugins()
    {
        $pluginPath = config('cms.plugins_path', base_path('plugins'));

        if (!File::exists($pluginPath)) return;

        $directories = File::directories($pluginPath);

        foreach ($directories as $dir) {
            $pluginClass = $this->getPluginClass($dir);

            if (class_exists($pluginClass) && in_array(PluginInterface::class, class_implements($pluginClass))) {
                $plugin = new $pluginClass();
                $this->plugins[$plugin->getName()] = $plugin;
                $plugin->register();
            }
        }
    }

    public function bootPlugins()
    {
        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }
    }

    public function enablePlugin($name)
    {
        if (isset($this->plugins[$name])) {
            $this->plugins[$name]->enable();
        }
    }

    public function disablePlugin($name)
    {
        if (isset($this->plugins[$name])) {
            $this->plugins[$name]->disable();
        }
    }

    protected function getPluginClass($path)
    {
        $composer = json_decode(File::get($path . '/composer.json'), true);
        return $composer['autoload']['psr-4'][array_key_first($composer['autoload']['psr-4'])] . 'Plugin';
    }
}