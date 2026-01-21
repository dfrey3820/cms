<?php

namespace Buni\Cms\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Buni\Cms\Models\Setting;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'general');

        $settings = Setting::all()->groupBy('group');

        $data = [
            'settings' => $settings,
            'activeTab' => $activeTab,
        ];

        if ($activeTab === 'editors') {
            $data['editors'] = $this->getAvailableEditors();
        }

        return Inertia::render('Admin/Settings/Index', $data);
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
                    $type = $key === 'two_factor_enabled' ? 'boolean' : 'string';
                    Setting::set($key, $value, $type, $setting->group);
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

    public function updateEditor(Request $request)
    {
        $request->validate([
            'editor_id' => 'required|string',
            'active' => 'boolean',
            'assignment' => 'in:pages,posts,both',
        ]);

        // In a real implementation, this would update a database table
        // For now, we'll use settings
        $key = "editor_{$request->editor_id}_active";
        Setting::set($key, $request->active ? '1' : '0', 'boolean', 'editors');

        $key = "editor_{$request->editor_id}_assignment";
        Setting::set($key, $request->assignment, 'string', 'editors');

        return response()->json(['message' => 'Editor updated successfully']);
    }

    private function getAvailableEditors()
    {
        // In a real implementation, this would scan plugins or config
        // For now, return some default editors
        $editors = [
            [
                'id' => 'default',
                'name' => 'Default Editor',
                'description' => 'Basic HTML editor',
                'active' => true,
                'assignment' => 'both', // pages, posts, both
                'type' => 'basic',
            ],
            [
                'id' => 'tinymce',
                'name' => 'TinyMCE',
                'description' => 'Rich text editor with advanced features',
                'active' => false,
                'assignment' => 'pages',
                'type' => 'richtext',
            ],
            [
                'id' => 'ckeditor',
                'name' => 'CKEditor',
                'description' => 'Powerful rich text editor',
                'active' => false,
                'assignment' => 'posts',
                'type' => 'richtext',
            ],
        ];

        // Load settings
        foreach ($editors as &$editor) {
            $activeKey = "editor_{$editor['id']}_active";
            $assignmentKey = "editor_{$editor['id']}_assignment";

            $activeSetting = Setting::where('key', $activeKey)->first();
            if ($activeSetting) {
                $editor['active'] = $activeSetting->value === '1';
            }

            $assignmentSetting = Setting::where('key', $assignmentKey)->first();
            if ($assignmentSetting) {
                $editor['assignment'] = $assignmentSetting->value;
            }
        }

        return $editors;
    }
}