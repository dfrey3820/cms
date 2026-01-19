<?php

namespace Dsc\Cms\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreatePluginCommand extends Command
{
    protected $signature = 'cms:create-plugin {name}';
    protected $description = 'Create a new CMS plugin';

    public function handle()
    {
        $name = $this->argument('name');
        $pluginPath = base_path('plugins/' . $name);

        if (File::exists($pluginPath)) {
            $this->error('Plugin already exists!');
            return;
        }

        File::makeDirectory($pluginPath, 0755, true);

        // Create composer.json
        $composer = [
            'name' => 'dsc/' . strtolower($name),
            'description' => 'A DSC CMS plugin',
            'type' => 'library',
            'autoload' => [
                'psr-4' => [
                    'Dsc\\Plugins\\' . $name . '\\' => 'src/'
                ]
            ]
        ];

        File::put($pluginPath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        // Create src directory and Plugin.php
        File::makeDirectory($pluginPath . '/src', 0755, true);

        $pluginClass = "<?php\n\nnamespace Dsc\\Plugins\\{$name};\n\nuse Dsc\\Cms\\Contracts\\PluginInterface;\n\nclass Plugin implements PluginInterface\n{\n    public function register()\n    {\n        // Register plugin\n    }\n\n    public function boot()\n    {\n        // Boot plugin\n    }\n\n    public function enable()\n    {\n        // Enable plugin\n    }\n\n    public function disable()\n    {\n        // Disable plugin\n    }\n\n    public function getName(): string\n    {\n        return '{$name}';\n    }\n\n    public function getVersion(): string\n    {\n        return '1.0.0';\n    }\n\n    public function getDescription(): string\n    {\n        return 'Description of {$name} plugin';\n    }\n}";

        File::put($pluginPath . '/src/Plugin.php', $pluginClass);

        $this->info("Plugin {$name} created successfully!");
    }
}