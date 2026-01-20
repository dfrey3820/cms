<?php

namespace Buni\Cms\Controllers\Frontend;

use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Buni\Cms\Models\Page;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show($slug = null)
    {
        // Check if CMS is installed (has users)
        try {
            $isInstalled = Schema::hasTable('users') && \App\Models\User::count() > 0;
        } catch (\Exception $e) {
            // Database doesn't exist or is not accessible, show installation
            $isInstalled = false;
        }

        if (!$isInstalled) {
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
            // Validate DB settings based on connection type
            $request->validate([
                'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
                'db_database' => 'required|string',
            ]);

            $dbConnection = $request->db_connection;

            // Add conditional validation for non-SQLite databases
            if ($dbConnection !== 'sqlite') {
                $request->validate([
                    'db_host' => 'required|string',
                    'db_port' => 'required|integer',
                    'db_username' => 'required|string',
                    'db_password' => 'nullable|string',
                ]);
            }

            // Store in session
            session([
                'install_db_connection' => $dbConnection,
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
            'DB_CONNECTION' => session('install_db_connection'),
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

        // For SQLite databases, ensure the database file exists
        if (session('install_db_connection') === 'sqlite') {
            $dbPath = session('install_db_database');
            if (!str_starts_with($dbPath, '/')) {
                // If it's not an absolute path, make it relative to the database directory
                $dbPath = database_path($dbPath);
            }
            if (!File::exists($dbPath)) {
                // Create the directory if it doesn't exist
                $dbDir = dirname($dbPath);
                if (!File::exists($dbDir)) {
                    File::makeDirectory($dbDir, 0755, true);
                }
                // Create an empty SQLite database file
                File::put($dbPath, '');
            }
        }

        // Run seeder logic inline to create roles and settings
        $this->runSeederLogic();

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
            'install_db_connection', 'install_db_host', 'install_db_port', 'install_db_database', 'install_db_username', 'install_db_password',
            'install_site_name', 'install_timezone',
            'install_mail_driver', 'install_mail_host', 'install_mail_port', 'install_mail_username', 'install_mail_password'
        ]);

        // Check if this is an AJAX request
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Installation completed successfully',
                'redirect' => '/admin/login'
            ]);
        }

        return redirect('/admin/login');
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

    private function runSeederLogic()
    {
        // Create permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'manage pages']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage users']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage plugins']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage themes']);
        \Spatie\Permission\Models\Permission::create(['name' => 'manage settings']);

        // Create roles
        $admin = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $admin->givePermissionTo(['manage pages', 'manage users', 'manage plugins', 'manage themes', 'manage settings']);

        $editor = \Spatie\Permission\Models\Role::create(['name' => 'editor']);
        $editor->givePermissionTo(['manage pages']);

        // Create super-admin role
        $superAdmin = \Spatie\Permission\Models\Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(['manage pages', 'manage users', 'manage plugins', 'manage themes', 'manage settings']);

        // Seed default settings
        \Buni\Cms\Models\Setting::set('site_name', session('install_site_name', 'My CMS Site'), 'string', 'site');
        \Buni\Cms\Models\Setting::set('site_description', '', 'string', 'site');
        \Buni\Cms\Models\Setting::set('timezone', session('install_timezone', 'UTC'), 'string', 'site');
        \Buni\Cms\Models\Setting::set('language', 'en', 'string', 'site');

        \Buni\Cms\Models\Setting::set('mail_driver', session('install_mail_driver', 'smtp'), 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_host', session('install_mail_host', ''), 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_port', session('install_mail_port', '587'), 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_username', session('install_mail_username', ''), 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_password', session('install_mail_password', ''), 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_encryption', 'tls', 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_from_address', '', 'string', 'mail');
        \Buni\Cms\Models\Setting::set('mail_from_name', session('install_site_name', 'My CMS Site'), 'string', 'mail');

        \Buni\Cms\Models\Setting::set('db_connection', session('install_db_connection', 'mysql'), 'string', 'database');
        \Buni\Cms\Models\Setting::set('db_host', session('install_db_host', '127.0.0.1'), 'string', 'database');
        \Buni\Cms\Models\Setting::set('db_port', session('install_db_port', '3306'), 'string', 'database');
        \Buni\Cms\Models\Setting::set('db_database', session('install_db_database', 'laravel'), 'string', 'database');
        \Buni\Cms\Models\Setting::set('db_username', session('install_db_username', 'root'), 'string', 'database');
        \Buni\Cms\Models\Setting::set('db_password', session('install_db_password', ''), 'string', 'database');
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
    <meta name="csrf-token" content="dummy-token-for-install">
    <title>Install Buni CMS - Step ' . $step . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .animate-slide-in-right {
            animation: slideInRight 0.5s ease-out;
        }

        .animate-pulse-gentle {
            animation: pulse 2s infinite;
        }

        .animate-bounce-in {
            animation: bounceIn 0.8s ease-out;
        }

        .brand-primary {
            background: linear-gradient(135deg, #009cde 0%, #007bb8 100%);
            transition: all 0.3s ease;
        }

        .brand-secondary {
            background-color: #12285f;
        }

        .brand-primary:hover {
            background: linear-gradient(135deg, #007bb8 0%, #005a8f 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 156, 222, 0.3);
        }

        .brand-secondary:hover {
            background-color: #0e1e4a;
        }

        .brand-ring {
            --tw-ring-color: #009cde;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .progress-dot {
            transition: all 0.3s ease;
        }

        .progress-dot.active {
            box-shadow: 0 0 15px rgba(0, 156, 222, 0.6);
        }

        .form-group {
            animation: fadeInUp 0.6s ease-out;
            transition: all 0.3s ease;
        }

        .form-group:hover {
            transform: translateX(5px);
        }

        .input-focus {
            transition: all 0.3s ease;
        }

        .input-focus:focus {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(0, 156, 222, 0.2);
        }

        .step-transition {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, #009cde 2px, transparent 2px), radial-gradient(circle at 75% 75%, #12285f 2px, transparent 2px); background-size: 50px 50px;"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <!-- Header Section -->
            <div class="text-center mb-8 animate-fade-in-up">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full mb-6 shadow-2xl animate-pulse-gentle">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2 bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    Install Buni CMS
                </h1>
                <p class="text-lg text-gray-600 font-medium">
                    Step <span class="text-blue-600 font-bold">' . $step . '</span> of <span class="font-bold">' . count($steps) . '</span>: <span class="text-gray-800">' . $steps[$step] . '</span>
                </p>
            </div>

            <!-- Progress Indicator -->
            <div class="flex justify-center mb-8 animate-slide-in-right">
                <div class="flex space-x-4">';
        for ($i = 1; $i <= count($steps); $i++) {
            $active = $i == $step ? 'brand-primary active' : ($i < $step ? 'bg-green-500' : 'bg-gray-300');
            $scale = $i == $step ? 'scale-125' : 'scale-100';
            $html .= '<div class="progress-dot w-4 h-4 rounded-full ' . $active . ' ' . $scale . ' transition-all duration-300 shadow-lg"></div>';
        }
        $html .= '</div>
            </div>

            <!-- Main Card -->
            <div class="glass-effect rounded-2xl shadow-2xl p-8 animate-bounce-in">
                <form class="space-y-8 step-transition" action="' . ($step < count($steps) ? '/install?step=' . ($step + 1) : '/install') . '" method="POST">
                    <input type="hidden" name="_token" value="' . csrf_token() . '" />
                    <input type="hidden" name="step" value="' . $step . '" />';

        if ($step == 1) {
            $html .= $this->renderDatabaseStep();
        } elseif ($step == 2) {
            $html .= $this->renderSiteStep();
        } elseif ($step == 3) {
            $html .= $this->renderAdminStep();
        }

        $buttonText = $step < count($steps) ? 'Continue' : 'Install CMS';

        $html .= '<!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        ' . ($step > 1 ? '<a href="/install?step=' . ($step - 1) . '" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105 shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Previous
                        </a>' : '<div></div>') . '
                        <button type="submit" class="brand-primary text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105 inline-flex items-center">
                            ' . $buttonText . '
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-500 text-sm animate-fade-in-up" style="animation-delay: 0.8s;">
                <p>© 2026 Buni CMS. <a href="https://www.dsc.co.ke" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">Powered by DSC</a>.</p>
            </div>
        </div>
    </div>

    <script>
        // Add loading animation to button on submit
        document.querySelector(\'form\').addEventListener(\'submit\', function(e) {
            const button = this.querySelector(\'button[type="submit"]\');
            const originalText = button.innerHTML;
            button.innerHTML = \'<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...\';
            button.disabled = true;
        });

        // Animate form groups on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: \'0px 0px -50px 0px\'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = \'1\';
                    entry.target.style.transform = \'translateY(0)\';
                }
            });
        }, observerOptions);

        document.querySelectorAll(\'.form-group\').forEach(group => {
            group.style.opacity = \'0\';
            group.style.transform = \'translateY(20px)\';
            group.style.transition = \'all 0.6s ease-out\';
            observer.observe(group);
        });

        // Database engine selection handler
        const dbConnectionSelect = document.getElementById(\'db_connection\');
        const mysqlFields = document.querySelectorAll(\'.mysql-field\');
        const pgsqlFields = document.querySelectorAll(\'.pgsql-field\');
        const sqlsrvFields = document.querySelectorAll(\'.sqlsrv-field\');
        const dbDatabaseInput = document.getElementById(\'db_database\');
        const dbHostInput = document.getElementById(\'db_host\');
        const dbPortInput = document.getElementById(\'db_port\');
        const dbUsernameInput = document.getElementById(\'db_username\');
        const dbPasswordInput = document.getElementById(\'db_password\');
        const databaseHelpText = document.querySelector(\'.database-help-text\');

        function updateDatabaseFields() {
            const selectedEngine = dbConnectionSelect.value;

            // Hide all engine-specific fields first
            mysqlFields.forEach(field => field.style.display = \'none\');
            pgsqlFields.forEach(field => field.style.display = \'none\');
            sqlsrvFields.forEach(field => field.style.display = \'none\');

            // Remove required attributes
            dbHostInput.removeAttribute(\'required\');
            dbPortInput.removeAttribute(\'required\');
            dbUsernameInput.removeAttribute(\'required\');
            dbPasswordInput.removeAttribute(\'required\');

            if (selectedEngine === \'sqlite\') {
                // SQLite only needs database name (file path)
                dbDatabaseInput.placeholder = \'database/database.sqlite\';
                databaseHelpText.textContent = \'For SQLite: file path like \\\'database.sqlite\\\' or absolute path. The file will be created automatically if it doesn\\\'t exist.\';
            } else {
                // Show fields for other databases
                if (selectedEngine === \'mysql\') {
                    mysqlFields.forEach(field => field.style.display = \'block\');
                    dbHostInput.value = dbHostInput.value || \'127.0.0.1\';
                    dbPortInput.value = dbPortInput.value || \'3306\';
                    dbDatabaseInput.placeholder = \'laravel\';
                } else if (selectedEngine === \'pgsql\') {
                    pgsqlFields.forEach(field => field.style.display = \'block\');
                    dbHostInput.value = dbHostInput.value || \'127.0.0.1\';
                    dbPortInput.value = dbPortInput.value || \'5432\';
                    dbDatabaseInput.placeholder = \'laravel\';
                } else if (selectedEngine === \'sqlsrv\') {
                    sqlsrvFields.forEach(field => field.style.display = \'block\');
                    dbHostInput.value = dbHostInput.value || \'127.0.0.1\';
                    dbPortInput.value = dbPortInput.value || \'1433\';
                    dbDatabaseInput.placeholder = \'laravel\';
                }

                // Add required attributes for non-SQLite databases
                dbHostInput.setAttribute(\'required\', \'required\');
                dbPortInput.setAttribute(\'required\', \'required\');
                dbUsernameInput.setAttribute(\'required\', \'required\');

                databaseHelpText.textContent = \'Enter the name of your database.\';
            }
        }

        // Initialize on page load
        updateDatabaseFields();

        // Listen for changes
        dbConnectionSelect.addEventListener(\'change\', updateDatabaseFields);
    </script>
</body>
</html>';

        return response($html);
    }

    private function renderDatabaseStep()
    {
        return '<div class="space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Database Configuration</h3>
                <p class="text-gray-600">Configure your database connection settings</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group md:col-span-2">
                    <label for="db_connection" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Database Engine
                    </label>
                    <select id="db_connection" name="db_connection" required
                            class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-blue-500 transition-all duration-200 bg-white shadow-sm">
                        <option value="mysql">MySQL</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="sqlite">SQLite</option>
                        <option value="sqlsrv">SQL Server</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Select the database engine you want to use for your CMS.
                    </p>
                </div>

                <div class="form-group mysql-field pgsql-field sqlsrv-field md:col-span-2">
                    <label for="db_host" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                        </svg>
                        Database Host
                    </label>
                    <input id="db_host" name="db_host" type="text"
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-blue-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="127.0.0.1" />
                </div>

                <div class="form-group mysql-field pgsql-field sqlsrv-field md:col-span-2">
                    <label for="db_port" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                        Database Port
                    </label>
                    <input id="db_port" name="db_port" type="number"
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-blue-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="3306" />
                </div>

                <div class="form-group md:col-span-2">
                    <label for="db_database" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Database Name
                    </label>
                    <input id="db_database" name="db_database" type="text" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-blue-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="laravel (MySQL/PostgreSQL/SQL Server) or database/database.sqlite (SQLite)" />
                    <p class="mt-1 text-xs text-gray-500 database-help-text">
                        For MySQL/PostgreSQL/SQL Server: database name. For SQLite: file path like \'database.sqlite\' or absolute path.
                    </p>
                </div>

                <div class="form-group mysql-field pgsql-field sqlsrv-field">
                    <label for="db_username" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Database Username
                    </label>
                    <input id="db_username" name="db_username" type="text"
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-blue-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="root" />
                </div>

                <div class="form-group mysql-field pgsql-field sqlsrv-field">
                    <label for="db_password" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Database Password
                    </label>
                    <input id="db_password" name="db_password" type="password"
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-blue-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="Leave empty if no password" />
                </div>
            </div>
        </div>';
    }

    private function renderSiteStep()
    {
        return '<div class="space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Site Configuration</h3>
                <p class="text-gray-600">Configure your website details and mail settings</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div class="form-group">
                        <label for="site_name" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Site Name
                        </label>
                        <input id="site_name" name="site_name" type="text" required
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm"
                               placeholder="My Awesome Website" />
                    </div>

                    <div class="form-group">
                        <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Timezone
                        </label>
                        <select id="timezone" name="timezone" required
                                class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm">
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="Asia/Tokyo">Asia/Tokyo</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="mail_driver" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Mail Driver
                        </label>
                        <select id="mail_driver" name="mail_driver" required
                                class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm">
                            <option value="smtp">SMTP</option>
                            <option value="mailgun">Mailgun</option>
                            <option value="ses">SES</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mail_host" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            Mail Host
                        </label>
                        <input id="mail_host" name="mail_host" type="text"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm"
                               placeholder="smtp.mailtrap.io" />
                    </div>

                    <div class="form-group">
                        <label for="mail_port" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            Mail Port
                        </label>
                        <input id="mail_port" name="mail_port" type="number"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm"
                               placeholder="2525" />
                    </div>

                    <div class="form-group">
                        <label for="mail_username" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Mail Username
                        </label>
                        <input id="mail_username" name="mail_username" type="text"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm" />
                    </div>

                    <div class="form-group">
                        <label for="mail_password" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Mail Password
                        </label>
                        <input id="mail_password" name="mail_password" type="password"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm" />
                    </div>
                </div>
            </div>
        </div>';
    }

    private function renderAdminStep()
    {
        return '<div class="space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Admin Account</h3>
                <p class="text-gray-600">Create your administrator account</p>
            </div>

            <div id="install-form" class="space-y-6">
                <div class="form-group">
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Full Name
                    </label>
                    <input id="name" name="name" type="text" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="Administrator Name" />
                </div>

                <div class="form-group">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email Address
                    </label>
                    <input id="email" name="email" type="email" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="admin@example.com" />
                </div>

                <div class="form-group">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Password
                    </label>
                    <input id="password" name="password" type="password" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="Enter a secure password" />
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Confirm Password
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="Confirm your password" />
                </div>

                <div class="pt-4">
                    <button type="button" onclick="startInstallation()"
                            class="w-full brand-primary text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Complete Installation
                        </span>
                    </button>
                </div>
            </div>

            <div id="install-progress" class="hidden space-y-6">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mb-4 shadow-lg">
                        <svg class="w-8 h-8 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Installing Buni CMS</h3>
                    <p class="text-gray-600">Please wait while we set up your CMS...</p>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between text-sm font-medium text-gray-700">
                        <span>Installation Progress</span>
                        <span id="progress-percent">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="progress-bar" class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                    <h4 class="font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Installation Steps
                    </h4>
                    <div id="install-steps" class="space-y-2 text-sm">
                        <div class="flex items-center text-gray-500">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                            <span>Updating configuration files...</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                            <span>Creating database tables...</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                            <span>Setting up user roles and permissions...</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                            <span>Creating admin account...</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                            <span>Installing default theme...</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                            <span>Finalizing installation...</span>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                async function startInstallation() {
                    const form = document.getElementById("install-form");
                    const progress = document.getElementById("install-progress");
                    const progressBar = document.getElementById("progress-bar");
                    const progressPercent = document.getElementById("progress-percent");
                    const steps = document.querySelectorAll("#install-steps > div");

                    // Get form data
                    const formData = new FormData();
                    formData.append("step", "3");
                    formData.append("name", document.getElementById("name").value);
                    formData.append("email", document.getElementById("email").value);
                    formData.append("password", document.getElementById("password").value);
                    formData.append("password_confirmation", document.getElementById("password_confirmation").value);

                    // Validate form
                    if (!document.getElementById("name").value || !document.getElementById("email").value ||
                        !document.getElementById("password").value || !document.getElementById("password_confirmation").value) {
                        alert("Please fill in all required fields.");
                        return;
                    }

                    if (document.getElementById("password").value !== document.getElementById("password_confirmation").value) {
                        alert("Passwords do not match.");
                        return;
                    }

                    // Hide form, show progress
                    form.classList.add("hidden");
                    progress.classList.remove("hidden");

                    try {
                        // Simulate progress steps
                        const progressSteps = [
                            { percent: 10, message: "Updating configuration files...", delay: 500 },
                            { percent: 25, message: "Creating database tables...", delay: 1000 },
                            { percent: 45, message: "Setting up user roles and permissions...", delay: 800 },
                            { percent: 65, message: "Creating admin account...", delay: 600 },
                            { percent: 85, message: "Installing default theme...", delay: 700 },
                            { percent: 100, message: "Finalizing installation...", delay: 500 }
                        ];

                        for (let i = 0; i < progressSteps.length; i++) {
                            const step = progressSteps[i];
                            const stepElement = steps[i];

                            // Update progress bar
                            progressBar.style.width = step.percent + "%";
                            progressPercent.textContent = step.percent + "%";

                            // Update step status
                            if (stepElement) {
                                stepElement.querySelector("div").className = "w-2 h-2 bg-blue-500 rounded-full mr-3";
                                stepElement.querySelector("span").className = "text-blue-600 font-medium";
                            }

                            await new Promise(resolve => setTimeout(resolve, step.delay));
                        }

                        // Submit the form
                        const csrfToken = document.querySelector(\'meta[name="csrf-token"]\').getAttribute(\'content\');
                        const response = await fetch(window.location.href, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": csrfToken
                            }
                        });

                        if (response.ok) {
                            // Redirect to admin login
                            window.location.href = "/admin/login";
                        } else {
                            throw new Error("Installation failed");
                        }

                    } catch (error) {
                        console.error("Installation error:", error);
                        alert("Installation failed. Please check your configuration and try again.");
                        // Reset UI
                        form.classList.remove("hidden");
                        progress.classList.add("hidden");
                        progressBar.style.width = "0%";
                        progressPercent.textContent = "0%";
                        steps.forEach(step => {
                            step.querySelector("div").className = "w-2 h-2 bg-gray-300 rounded-full mr-3";
                            step.querySelector("span").className = "text-gray-500";
                        });
                    }
                }
            </script>
        </div>';
    }
}