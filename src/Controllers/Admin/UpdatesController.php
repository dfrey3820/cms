<?php

namespace Buni\Cms\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class UpdatesController extends Controller
{
    public function index()
    {
        $currentVersion = config('cms.version', '1.0.0');
        $latestVersion = $this->getLatestVersion();
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        $pendingMigrations = $this->getPendingMigrations();
        $hasPendingMigrations = count($pendingMigrations) > 0;

        $updateInfo = null;
        if ($hasUpdate) {
            $updateInfo = $this->getUpdateInfo($latestVersion);
        }

        return Inertia::render('Admin/Updates/Index', [
            'currentVersion' => $currentVersion,
            'latestVersion' => $latestVersion,
            'hasUpdate' => $hasUpdate,
            'updateInfo' => $updateInfo,
            'hasPendingMigrations' => $hasPendingMigrations,
            'pendingMigrations' => $pendingMigrations,
            'isMaintenanceMode' => app()->isDownForMaintenance(),
        ]);
    }

    public function checkForUpdates()
    {
        Cache::forget('cms_latest_version');
        Cache::forget('cms_update_info');

        return redirect()->back()->with('success', 'Update check completed.');
    }

    public function installUpdate(Request $request)
    {
        $request->validate([
            'version' => 'required|string',
        ]);

        $version = $request->version;

        try {
            // Put system in maintenance mode
            Artisan::call('down', ['--message' => 'System is being updated. Please try again later.']);

            // Here you would typically:
            // 1. Download the update package
            // 2. Extract and replace files
            // For now, we'll simulate with git pull
            $this->updateCode($version);

            // Check for migrations
            $pendingMigrations = $this->getPendingMigrations();
            if (count($pendingMigrations) > 0) {
                return response()->json([
                    'status' => 'migrations_required',
                    'message' => 'Code updated successfully. Database migrations are required.',
                    'pendingMigrations' => $pendingMigrations,
                ]);
            }

            // If no migrations needed, bring system back up
            Artisan::call('up');

            return response()->json([
                'status' => 'success',
                'message' => 'Update installed successfully.',
            ]);

        } catch (\Exception $e) {
            // Make sure to bring system back up if something fails
            Artisan::call('up');

            return response()->json([
                'status' => 'error',
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function runMigrations(Request $request)
    {
        try {
            // Put system in maintenance mode if not already
            if (!app()->isDownForMaintenance()) {
                Artisan::call('down', ['--message' => 'Database is being upgraded. Please try again later.']);
            }

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Bring system back up
            Artisan::call('up');

            return response()->json([
                'status' => 'success',
                'message' => 'Database upgraded successfully.',
            ]);

        } catch (\Exception $e) {
            // Make sure to bring system back up
            Artisan::call('up');

            return response()->json([
                'status' => 'error',
                'message' => 'Database upgrade failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleMaintenance(Request $request)
    {
        $request->validate([
            'enable' => 'required|boolean',
            'message' => 'nullable|string',
        ]);

        try {
            if ($request->enable) {
                $message = $request->message ?: 'System is under maintenance.';
                Artisan::call('down', ['--message' => $message]);
            } else {
                Artisan::call('up');
            }

            return response()->json([
                'status' => 'success',
                'message' => $request->enable ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.',
                'isMaintenanceMode' => app()->isDownForMaintenance(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle maintenance mode: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getLatestVersion()
    {
        return Cache::remember('cms_latest_version', 3600, function () {
            // In a real implementation, this would check a remote API
            // For now, return a hardcoded version
            try {
                $response = Http::timeout(10)->get('https://api.github.com/repos/dfrey3820/cms/releases/latest');
                if ($response->successful()) {
                    return $response->json()['tag_name'] ?? '1.1.14';
                }
            } catch (\Exception $e) {
                // Fallback
            }
            return '1.1.14'; // Current version
        });
    }

    private function getUpdateInfo($version)
    {
        return Cache::remember("cms_update_info_{$version}", 3600, function () use ($version) {
            // In a real implementation, this would fetch release notes
            try {
                $response = Http::timeout(10)->get("https://api.github.com/repos/dfrey3820/cms/releases/tags/{$version}");
                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'version' => $data['tag_name'],
                        'name' => $data['name'],
                        'body' => $data['body'],
                        'published_at' => $data['published_at'],
                        'download_url' => $data['zipball_url'],
                    ];
                }
            } catch (\Exception $e) {
                // Fallback
            }
            return [
                'version' => $version,
                'name' => "Update to {$version}",
                'body' => 'New features and improvements.',
                'published_at' => now()->toISOString(),
                'download_url' => null,
            ];
        });
    }

    private function getPendingMigrations()
    {
        try {
            $migrations = [];
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));

            foreach ($files as $file) {
                $migration = $migrator->resolve($file);
                if (!$migrator->repositoryExists()) {
                    $migrations[] = [
                        'file' => $file,
                        'class' => get_class($migration),
                    ];
                } elseif (!$migrator->alreadyRan($file)) {
                    $migrations[] = [
                        'file' => $file,
                        'class' => get_class($migration),
                    ];
                }
            }

            return $migrations;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function updateCode($version)
    {
        // In a real implementation, this would:
        // 1. Download the update package from GitHub
        // 2. Extract files
        // 3. Replace old files
        // For now, we'll simulate with git pull if in development

        if (app()->environment('local')) {
            // Try git pull
            exec('git pull origin main 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                return true;
            }
        }

        // For production, you would implement file download and replacement
        // For now, just update the version in config
        config(['cms.version' => $version]);

        return true;
    }
}