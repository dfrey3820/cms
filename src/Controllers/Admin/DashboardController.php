<?php

namespace Buni\Cms\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Buni\Cms\Models\Page;
use Buni\Cms\Models\Post;

class DashboardController extends Controller
{
    public function index()
    {
        $currentVersion = config('cms.version', '1.0.0');
        $latestVersion = $this->getLatestVersion();
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        $stats = [
            'pages' => Page::count(),
            'posts' => Post::count(),
            'published_pages' => Page::where('status', 'published')->count(),
            'published_posts' => Post::where('status', 'published')->count(),
            'plugins' => $this->getPluginCount(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'hasUpdate' => $hasUpdate,
            'latestVersion' => $latestVersion,
            'currentVersion' => $currentVersion,
        ]);
    }

    private function getLatestVersion()
    {
        return Cache::remember('cms_latest_version', 3600, function () {
            try {
                $response = Http::timeout(10)->get('https://api.github.com/repos/dfrey3820/cms/releases/latest');
                if ($response->successful()) {
                    return $response->json()['tag_name'] ?? config('cms.version');
                }
            } catch (\Exception $e) {
                // Fallback
            }
            return config('cms.version');
        });
    }

    private function getPluginCount()
    {
        $pluginsPath = config('cms.plugins_path');
        if (!\Illuminate\Support\Facades\File::exists($pluginsPath)) {
            return 0;
        }

        $directories = \Illuminate\Support\Facades\File::directories($pluginsPath);
        $count = 0;

        foreach ($directories as $dir) {
            if (\Illuminate\Support\Facades\File::exists($dir . '/plugin.json')) {
                $count++;
            }
        }

        return $count;
    }
}