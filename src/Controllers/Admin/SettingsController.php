<?php

namespace Buni\Cms\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Buni\Cms\Models\Setting;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            '*' => 'nullable|string', // Allow any settings
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                // Find the setting to get its group
                $setting = Setting::where('key', $key)->first();
                if ($setting) {
                    Setting::set($key, $value, 'string', $setting->group);
                }
            }
        }

        // Update .env for critical settings
        $envUpdates = [];
        if (isset($validated['site_name'])) {
            $envUpdates['APP_NAME'] = $validated['site_name'];
        }
        if (isset($validated['timezone'])) {
            $envUpdates['APP_TIMEZONE'] = $validated['timezone'];
        }
        // Mail settings
        $mailKeys = ['mail_driver', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'];
        foreach ($mailKeys as $mailKey) {
            if (isset($validated[$mailKey])) {
                $envKey = 'MAIL_' . strtoupper(str_replace('mail_', '', $mailKey));
                $envUpdates[$envKey] = $validated[$mailKey];
            }
        }
        // DB settings
        $dbKeys = ['db_host', 'db_port', 'db_database', 'db_username', 'db_password'];
        foreach ($dbKeys as $dbKey) {
            if (isset($validated[$dbKey])) {
                $envKey = 'DB_' . strtoupper(str_replace('db_', '', $dbKey));
                $envUpdates[$envKey] = $validated[$dbKey];
            }
        }

        if (!empty($envUpdates)) {
            $this->updateEnvMultiple($envUpdates);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    private function updateEnvMultiple($data)
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) return;

        $content = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$value}";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envFile, $content);
    }
}