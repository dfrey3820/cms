<?php
namespace Buni\Cms\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Buni\Cms\Models\Setting;
use Illuminate\Support\Facades\File;

class ThemesController extends Controller
{
    public function index()
    {
        $themesPath = config('cms.themes_path', base_path('themes'));
        $dirs = File::directories($themesPath);

        $themes = [];
        foreach ($dirs as $dir) {
            $metaFile = $dir . '/theme.json';
            if (File::exists($metaFile)) {
                $json = json_decode(File::get($metaFile), true);
                $name = basename($dir);
                $themes[] = [
                    'id' => $name,
                    'name' => $json['name'] ?? $name,
                    'description' => $json['description'] ?? '',
                    'version' => $json['version'] ?? '',
                    'author' => $json['author'] ?? '',
                ];
            }
        }

        $active = Setting::where('key', 'active_theme')->value('value') ?? 'default';

        return Inertia::render('Admin/Themes/Index', [
            'themes' => $themes,
            'active' => $active,
        ]);
    }

    public function activate(Request $request, $theme)
    {
        // Basic validation: ensure theme folder exists
        $themesPath = config('cms.themes_path', base_path('themes'));
        if (!File::exists($themesPath . '/' . $theme . '/theme.json')) {
            return back()->with('error', 'Theme not found');
        }

        Setting::set('active_theme', $theme, 'string', 'themes');

        return back()->with('success', 'Theme activated');
    }
}
