<?php

namespace Buni\Cms\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateThemeCommand extends Command
{
    protected $signature = 'cms:create-theme {name}';
    protected $description = 'Create a new CMS theme';

    public function handle()
    {
        $name = $this->argument('name');
        $themePath = base_path('themes/' . $name);

        if (File::exists($themePath)) {
            $this->error('Theme already exists!');
            return;
        }

        File::makeDirectory($themePath, 0755, true);

        // Create composer.json
        $composer = [
            'name' => 'buni/' . strtolower($name) . '-theme',
            'description' => 'A DSC CMS theme',
            'type' => 'library',
            'autoload' => [
                'psr-4' => [
                    'Buni\\Themes\\' . $name . '\\' => 'src/'
                ]
            ]
        ];

        File::put($themePath . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

        // Create src directory and Theme.php
        File::makeDirectory($themePath . '/src', 0755, true);

        $themeClass = "<?php\n\nnamespace Buni\\Themes\\{$name};\n\nuse Buni\\Cms\\Contracts\\ThemeInterface;\n\nclass Theme implements ThemeInterface\n{\n    public function register()\n    {\n        // Register theme\n    }\n\n    public function boot()\n    {\n        // Boot theme\n    }\n\n    public function getName(): string\n    {\n        return '{$name}';\n    }\n\n    public function getVersion(): string\n    {\n        return '1.0.0';\n    }\n\n    public function getDescription(): string\n    {\n        return 'Description of {$name} theme';\n    }\n\n    public function getLayout(): string\n    {\n        return 'layouts.app';\n    }\n}";

        File::put($themePath . '/src/Theme.php', $themeClass);

        // Create resources directory
        File::makeDirectory($themePath . '/resources/js', 0755, true);

        $this->info("Theme {$name} created successfully!");
    }
}