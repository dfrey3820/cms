<?php

namespace Buni\Cms\Controllers\Frontend;

use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Buni\Cms\Models\Page;
use Buni\Cms\Database\Seeders\CmsSeeder;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show($slug = null)
    {
        // Check if CMS is installed (has users)
        if (!Schema::hasTable('users') || \App\Models\User::count() == 0) {
            $step = request('step', 1);
            return $this->renderInstallStep($step);
        }

        $slug = $slug ?: 'home'; // Default to home page

        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            abort(404);
        }

        return Inertia::render('Page', ['page' => $page]);
    }

    public function install(Request $request)
    {
        $step = $request->input('step', 3);

        if ($step == 1) {
            // Validate DB settings
            $request->validate([
                'db_host' => 'required|string',
                'db_port' => 'required|integer',
                'db_database' => 'required|string',
                'db_username' => 'required|string',
                'db_password' => 'nullable|string',
            ]);

            // Store in session
            session([
                'install_db_host' => $request->db_host,
                'install_db_port' => $request->db_port,
                'install_db_database' => $request->db_database,
                'install_db_username' => $request->db_username,
                'install_db_password' => $request->db_password,
            ]);

            return redirect('/install?step=2');
        }

        if ($step == 2) {
            // Validate site and mail settings
            $request->validate([
                'site_name' => 'required|string|max:255',
                'timezone' => 'required|string',
                'mail_driver' => 'required|string',
                'mail_host' => 'nullable|string',
                'mail_port' => 'nullable|integer',
                'mail_username' => 'nullable|string',
                'mail_password' => 'nullable|string',
            ]);

            // Store in session
            session([
                'install_site_name' => $request->site_name,
                'install_timezone' => $request->timezone,
                'install_mail_driver' => $request->mail_driver,
                'install_mail_host' => $request->mail_host,
                'install_mail_port' => $request->mail_port,
                'install_mail_username' => $request->mail_username,
                'install_mail_password' => $request->mail_password,
            ]);

            return redirect('/install?step=3');
        }

        // Step 3: Admin account and final install
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Get all data from session
        $dbData = [
            'DB_HOST' => session('install_db_host'),
            'DB_PORT' => session('install_db_port'),
            'DB_DATABASE' => session('install_db_database'),
            'DB_USERNAME' => session('install_db_username'),
            'DB_PASSWORD' => session('install_db_password'),
        ];

        $siteData = [
            'APP_NAME' => session('install_site_name'),
            'APP_TIMEZONE' => session('install_timezone'),
        ];

        $mailData = [
            'MAIL_MAILER' => session('install_mail_driver'),
            'MAIL_HOST' => session('install_mail_host'),
            'MAIL_PORT' => session('install_mail_port'),
            'MAIL_USERNAME' => session('install_mail_username'),
            'MAIL_PASSWORD' => session('install_mail_password'),
        ];

        // Update .env first
        $this->updateEnv(array_merge($dbData, $siteData, $mailData));

        // Run seeder to create roles and settings
        $seeder = new CmsSeeder();
        $seeder->run();

        // Create admin user
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign super-admin role
        $user->assignRole('super-admin');

        // Create a default home page
        Page::create([
            'title' => 'Welcome to ' . session('install_site_name'),
            'slug' => 'home',
            'content' => '<p>Welcome to your new CMS powered by Buni.</p>',
            'status' => 'published',
        ]);

        // Install default theme
        $themesPath = config('cms.themes_path');
        if (!File::exists($themesPath)) {
            File::makeDirectory($themesPath, 0755, true);
        }
        File::copyDirectory(__DIR__.'/../../sample-theme', $themesPath . '/default');

        // Clear session
        session()->forget([
            'install_db_host', 'install_db_port', 'install_db_database', 'install_db_username', 'install_db_password',
            'install_site_name', 'install_timezone',
            'install_mail_driver', 'install_mail_host', 'install_mail_port', 'install_mail_username', 'install_mail_password'
        ]);

        return redirect('/admin');
    }

    private function updateEnv($data)
    {
        $envFile = base_path('.env');
        if (!File::exists($envFile)) return;

        $envContent = File::get($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$value}";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envFile, $envContent);
    }

    private function renderInstallStep($step)
    {
        $steps = [
            1 => 'Database Configuration',
            2 => 'Site Configuration',
            3 => 'Admin Account',
        ];

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Buni CMS - Step ' . $step . '</title>
    <style>
        .brand-primary { background-color: #009cde; }
        .brand-secondary { background-color: #12285f; }
        .brand-primary:hover { background-color: #007bb8; }
        .brand-secondary:hover { background-color: #0e1e4a; }
        .brand-ring { --tw-ring-color: #009cde; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Install Buni CMS
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Step ' . $step . ' of ' . count($steps) . ': ' . $steps[$step] . '
                </p>
                <div class="mt-4 flex justify-center">
                    <div class="flex space-x-2">';
        for ($i = 1; $i <= count($steps); $i++) {
            $active = $i == $step ? 'brand-primary' : 'bg-gray-300';
            $html .= '<div class="w-3 h-3 rounded-full ' . $active . '"></div>';
        }
        $html .= '</div>
                </div>
            </div>';

        $action = $step < count($steps) ? '/install?step=' . ($step + 1) : '/install';
        $buttonText = $step < count($steps) ? 'Next' : 'Install CMS';

        $html .= '<form class="mt-8 space-y-6" action="' . $action . '" method="POST">
                <input type="hidden" name="_token" value="' . csrf_token() . '" />
                <input type="hidden" name="step" value="' . $step . '" />';

        if ($step == 1) {
            $html .= $this->renderDatabaseStep();
        } elseif ($step == 2) {
            $html .= $this->renderSiteStep();
        } elseif ($step == 3) {
            $html .= $this->renderAdminStep();
        }

        $html .= '<div class="flex justify-between">
                    ' . ($step > 1 ? '<a href="/install?step=' . ($step - 1) . '" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Previous</a>' : '<div></div>') . '
                    <button type="submit" class="brand-primary hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">' . $buttonText . '</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>';

        return response($html);
    }

    private function renderDatabaseStep()
    {
        return '<div>
            <h3 class="text-lg font-medium text-gray-900">Database Configuration</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="db_host" class="block text-sm font-medium text-gray-700">Database Host</label>
                    <input id="db_host" name="db_host" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="127.0.0.1" />
                </div>
                <div>
                    <label for="db_port" class="block text-sm font-medium text-gray-700">Database Port</label>
                    <input id="db_port" name="db_port" type="number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="3306" />
                </div>
                <div>
                    <label for="db_database" class="block text-sm font-medium text-gray-700">Database Name</label>
                    <input id="db_database" name="db_database" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="laravel" />
                </div>
                <div>
                    <label for="db_username" class="block text-sm font-medium text-gray-700">Database Username</label>
                    <input id="db_username" name="db_username" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="root" />
                </div>
                <div>
                    <label for="db_password" class="block text-sm font-medium text-gray-700">Database Password</label>
                    <input id="db_password" name="db_password" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" />
                </div>
            </div>
        </div>';
    }

    private function renderSiteStep()
    {
        return '<div>
            <h3 class="text-lg font-medium text-gray-900">Site Configuration</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700">Site Name</label>
                    <input id="site_name" name="site_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="My Awesome Site" />
                </div>
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                    <select id="timezone" name="timezone" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm">
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">America/New_York</option>
                        <option value="Europe/London">Europe/London</option>
                        <option value="Asia/Tokyo">Asia/Tokyo</option>
                    </select>
                </div>
            </div>
        </div>
        <div>
            <h3 class="text-lg font-medium text-gray-900">Mailer Configuration</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="mail_driver" class="block text-sm font-medium text-gray-700">Mail Driver</label>
                    <select id="mail_driver" name="mail_driver" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm">
                        <option value="smtp">SMTP</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="ses">SES</option>
                    </select>
                </div>
                <div>
                    <label for="mail_host" class="block text-sm font-medium text-gray-700">Mail Host</label>
                    <input id="mail_host" name="mail_host" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="smtp.mailtrap.io" />
                </div>
                <div>
                    <label for="mail_port" class="block text-sm font-medium text-gray-700">Mail Port</label>
                    <input id="mail_port" name="mail_port" type="number" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" placeholder="2525" />
                </div>
                <div>
                    <label for="mail_username" class="block text-sm font-medium text-gray-700">Mail Username</label>
                    <input id="mail_username" name="mail_username" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" />
                </div>
                <div>
                    <label for="mail_password" class="block text-sm font-medium text-gray-700">Mail Password</label>
                    <input id="mail_password" name="mail_password" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 sm:text-sm" />
                </div>
            </div>
        </div>';
    }

    private function renderAdminStep()
    {
        return '<div>
            <h3 class="text-lg font-medium text-gray-900">Admin Account</h3>
            <div class="mt-4 rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="name" class="sr-only">Name</label>
                    <input id="name" name="name" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Admin Name" />
                </div>
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" name="email" type="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Email address" />
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Password" />
                </div>
                <div>
                    <label for="password_confirmation" class="sr-only">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-2 brand-ring focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Confirm Password" />
                </div>
            </div>
        </div>';
    }
}