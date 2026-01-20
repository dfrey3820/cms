<?php

namespace Buni\Cms\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use ZipArchive;

class PluginsController extends Controller
{
    public function index()
    {
        $installedPlugins = $this->getInstalledPlugins();
        $marketplacePlugins = $this->getMarketplacePlugins();

        return Inertia::render('Admin/Plugins/Index', [
            'installedPlugins' => $installedPlugins,
            'marketplacePlugins' => $marketplacePlugins,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plugins/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'method' => 'required|in:upload,marketplace',
            'plugin_file' => 'required_if:method,upload|file|mimes:zip',
            'marketplace_plugin' => 'required_if:method,marketplace|string',
        ]);

        if ($request->method === 'upload') {
            return $this->installFromUpload($request);
        } else {
            return $this->installFromMarketplace($request);
        }
    }

    public function activate($pluginName)
    {
        $pluginPath = config('cms.plugins_path') . '/' . $pluginName;
        $pluginJson = $pluginPath . '/plugin.json';

        if (!File::exists($pluginJson)) {
            return response()->json(['error' => 'Plugin configuration not found'], 404);
        }

        $config = json_decode(File::get($pluginJson), true);
        if (!$config) {
            return response()->json(['error' => 'Invalid plugin configuration'], 400);
        }

        // Load the plugin service provider if it exists
        if (isset($config['provider'])) {
            $providerClass = $config['provider'];
            if (class_exists($providerClass)) {
                app()->register($providerClass);
            }
        }

        // Mark as active in some storage (could be database or config)
        // For now, we'll use a simple file-based approach
        $activePlugins = $this->getActivePlugins();
        $activePlugins[] = $pluginName;
        $activePlugins = array_unique($activePlugins);
        File::put(config('cms.plugins_path') . '/active_plugins.json', json_encode($activePlugins));

        Cache::forget('cms_installed_plugins');

        return response()->json(['message' => 'Plugin activated successfully']);
    }

    public function deactivate($pluginName)
    {
        $activePlugins = $this->getActivePlugins();
        $activePlugins = array_filter($activePlugins, fn($p) => $p !== $pluginName);
        File::put(config('cms.plugins_path') . '/active_plugins.json', json_encode(array_values($activePlugins)));

        Cache::forget('cms_installed_plugins');

        return response()->json(['message' => 'Plugin deactivated successfully']);
    }

    public function destroy($pluginName)
    {
        $pluginPath = config('cms.plugins_path') . '/' . $pluginName;

        if (!File::exists($pluginPath)) {
            return response()->json(['error' => 'Plugin not found'], 404);
        }

        // Deactivate first
        $this->deactivate($pluginName);

        // Remove files
        File::deleteDirectory($pluginPath);

        Cache::forget('cms_installed_plugins');

        return response()->json(['message' => 'Plugin uninstalled successfully']);
    }

    private function installFromUpload(Request $request)
    {
        $file = $request->file('plugin_file');
        $tempPath = $file->store('temp_plugins');

        $zip = new ZipArchive;
        if ($zip->open(storage_path('app/' . $tempPath)) === TRUE) {
            $extractPath = config('cms.plugins_path') . '/temp_' . time();
            $zip->extractTo($extractPath);
            $zip->close();

            // Check for plugin.json
            $pluginJson = $extractPath . '/plugin.json';
            if (!File::exists($pluginJson)) {
                File::deleteDirectory($extractPath);
                Storage::delete($tempPath);
                return response()->json(['error' => 'Invalid plugin: plugin.json not found'], 400);
            }

            $config = json_decode(File::get($pluginJson), true);
            if (!$config || !isset($config['name'])) {
                File::deleteDirectory($extractPath);
                Storage::delete($tempPath);
                return response()->json(['error' => 'Invalid plugin configuration'], 400);
            }

            $pluginName = $config['name'];
            $finalPath = config('cms.plugins_path') . '/' . $pluginName;

            // Check if plugin already exists
            if (File::exists($finalPath)) {
                File::deleteDirectory($extractPath);
                Storage::delete($tempPath);
                return response()->json(['error' => 'Plugin already exists'], 400);
            }

            // Move to final location
            File::moveDirectory($extractPath, $finalPath);
            Storage::delete($tempPath);

            Cache::forget('cms_installed_plugins');

            return response()->json([
                'message' => 'Plugin uploaded successfully',
                'plugin' => $config
            ]);
        } else {
            Storage::delete($tempPath);
            return response()->json(['error' => 'Failed to extract plugin archive'], 400);
        }
    }

    private function installFromMarketplace(Request $request)
    {
        $pluginId = $request->marketplace_plugin;

        // In a real implementation, this would download from marketplace
        // For now, we'll simulate
        try {
            $response = Http::timeout(30)->get("https://api.example.com/plugins/{$pluginId}/download");
            if ($response->successful()) {
                $zipContent = $response->body();
                $tempFile = tempnam(sys_get_temp_dir(), 'plugin_');
                file_put_contents($tempFile, $zipContent);

                $zip = new ZipArchive;
                if ($zip->open($tempFile) === TRUE) {
                    $extractPath = config('cms.plugins_path') . '/temp_' . time();
                    $zip->extractTo($extractPath);
                    $zip->close();

                    // Check for plugin.json
                    $pluginJson = $extractPath . '/plugin.json';
                    if (!File::exists($pluginJson)) {
                        File::deleteDirectory($extractPath);
                        unlink($tempFile);
                        return response()->json(['error' => 'Invalid plugin from marketplace'], 400);
                    }

                    $config = json_decode(File::get($pluginJson), true);
                    $pluginName = $config['name'];
                    $finalPath = config('cms.plugins_path') . '/' . $pluginName;

                    if (File::exists($finalPath)) {
                        File::deleteDirectory($extractPath);
                        unlink($tempFile);
                        return response()->json(['error' => 'Plugin already exists'], 400);
                    }

                    File::moveDirectory($extractPath, $finalPath);
                    unlink($tempFile);

                    Cache::forget('cms_installed_plugins');

                    return response()->json([
                        'message' => 'Plugin installed from marketplace successfully',
                        'plugin' => $config
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to install from marketplace: ' . $e->getMessage()], 400);
        }

        return response()->json(['error' => 'Failed to install from marketplace'], 400);
    }

    private function getInstalledPlugins()
    {
        return Cache::remember('cms_installed_plugins', 300, function () {
            $pluginsPath = config('cms.plugins_path');
            $activePlugins = $this->getActivePlugins();

            if (!File::exists($pluginsPath)) {
                return [];
            }

            $plugins = [];
            $directories = File::directories($pluginsPath);

            foreach ($directories as $dir) {
                $pluginJson = $dir . '/plugin.json';
                if (File::exists($pluginJson)) {
                    $config = json_decode(File::get($pluginJson), true);
                    if ($config && isset($config['name'])) {
                        $config['active'] = in_array($config['name'], $activePlugins);
                        $config['path'] = $dir;
                        $plugins[] = $config;
                    }
                }
            }

            return $plugins;
        });
    }

    private function getMarketplacePlugins()
    {
        return Cache::remember('cms_marketplace_plugins', 3600, function () {
            // In a real implementation, this would fetch from marketplace API
            // For now, return some sample plugins
            try {
                $response = Http::timeout(10)->get('https://api.github.com/repos/dfrey3820/cms-plugins/contents/plugins.json');
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['download_url'])) {
                        $pluginsResponse = Http::get($data['download_url']);
                        if ($pluginsResponse->successful()) {
                            return $pluginsResponse->json();
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback to sample data
            }

            return [
                [
                    'id' => 'tinymce-editor',
                    'name' => 'TinyMCE Rich Text Editor',
                    'description' => 'Powerful rich text editor for content creation',
                    'version' => '1.0.0',
                    'author' => 'Buni CMS',
                    'downloads' => 1250,
                    'rating' => 4.5,
                ],
                [
                    'id' => 'seo-optimizer',
                    'name' => 'SEO Optimizer',
                    'description' => 'Advanced SEO tools and meta management',
                    'version' => '1.2.0',
                    'author' => 'Buni CMS',
                    'downloads' => 890,
                    'rating' => 4.2,
                ],
                [
                    'id' => 'social-share',
                    'name' => 'Social Media Share',
                    'description' => 'Easy social media sharing buttons',
                    'version' => '1.0.1',
                    'author' => 'Buni CMS',
                    'downloads' => 650,
                    'rating' => 4.0,
                ],
            ];
        });
    }

    private function getActivePlugins()
    {
        $activeFile = config('cms.plugins_path') . '/active_plugins.json';
        if (File::exists($activeFile)) {
            $active = json_decode(File::get($activeFile), true);
            return is_array($active) ? $active : [];
        }
        return [];
    }
}