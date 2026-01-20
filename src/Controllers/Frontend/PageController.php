<?php

namespace Buni\Cms\Controllers\Frontend;

use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Buni\Cms\Models\Page;
use \App\Models\User;
use Inertia\Inertia;

class PageController extends Controller
{
    public function showInstall()
    {
        // Check if CMS is already installed
        try {
            $hasTable = Schema::hasTable('users');
            $userCount = DB::table('users')->count();
            $isInstalled = $hasTable && $userCount > 0;
            Log::info("Install check: hasTable=$hasTable, userCount=$userCount, isInstalled=$isInstalled");
        } catch (\Exception $e) {
            Log::error("Install check error: " . $e->getMessage());
            $isInstalled = false;
        }

        if ($isInstalled) {
            return redirect('/admin');
        }

        $step = request('step', 1);
        return $this->renderInstallStep($step);
    }

    public function show($slug = null)
    {
        $slug = $slug ?: 'home'; // Default to home page

        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            abort(404);
        }

        // For now, render a simple HTML page since Inertia might not be set up
        return response()->view('cms::page', ['page' => $page])->header('Content-Type', 'text/html');
    }

    public function install(Request $request)
    {
        $step = $request->input('step', 3);

        \Log::info('Install method called with step: ' . $step);
        \Log::info('Request data:', $request->all());
        \Log::info('All input:', $request->input());

        if ($step == 1) {
            // Validate DB settings based on connection type
            $request->validate([
                'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
                'db_database' => 'required|string',
            ]);

            $dbConnection = $request->db_connection;

            // Special validation for SQLite
            if ($dbConnection === 'sqlite') {
                $dbDatabase = $request->db_database;
                if (!str_contains($dbDatabase, '.sqlite') && !str_starts_with($dbDatabase, '/')) {
                    // If it's not a .sqlite file and not an absolute path, append .sqlite
                    $request->merge(['db_database' => $dbDatabase . '.sqlite']);
                }
            }

            // Add conditional validation for non-SQLite databases
            if ($dbConnection !== 'sqlite') {
                $request->validate([
                    'db_host' => 'required|string',
                    'db_port' => 'required|integer',
                    'db_username' => 'required|string',
                    'db_password' => 'nullable|string',
                ]);
            }

            // Test database connection before proceeding
            try {
                $this->testDatabaseConnection($request->all());
            } catch (\Exception $e) {
                return back()->withErrors(['db_connection' => 'Database connection failed: ' . $e->getMessage()])->withInput();
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
            // Validate site settings
            $request->validate([
                'site_name' => 'required|string|max:255',
                'site_url' => 'required|url',
                'timezone' => 'required|string',
            ]);

            // Store in session
            session([
                'install_site_name' => $request->site_name,
                'install_site_url' => $request->site_url,
                'install_timezone' => $request->timezone,
            ]);

            return redirect('/install?step=3');
        }

        if ($step == 3) {
            // Validate admin account
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Store in session
            session([
                'install_admin_name' => $request->name,
                'install_admin_email' => $request->email,
                'install_admin_password' => $request->password,
            ]);

            return redirect('/install?step=4');
        }

        if ($step == 4) {
            \Log::info('Processing step 4');
            // Validate mail settings
            $request->validate([
                'mail_driver' => 'required|string',
                'mail_host' => 'nullable|string',
                'mail_port' => 'nullable|integer',
                'mail_username' => 'nullable|string',
                'mail_password' => 'nullable|string',
                'mail_encryption' => 'nullable|string|in:tls,ssl,',
            ]);

            \Log::info('Step 4 validation passed');

            // Store in session
            session([
                'install_mail_driver' => $request->mail_driver,
                'install_mail_host' => $request->mail_host,
                'install_mail_port' => $request->mail_port,
                'install_mail_username' => $request->mail_username,
                'install_mail_password' => $request->mail_password,
                'install_mail_encryption' => $request->mail_encryption,
            ]);

            \Log::info('Redirecting to step 5');
            return redirect('/install?step=5');
        }

        // Step 5: Final installation
        // Check if users table exists for validation
        $userValidationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];

        // Only add unique validation if users table exists
        try {
            if (\Schema::hasTable('users')) {
                $userValidationRules['email'] .= '|unique:users';
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet, skip unique validation
        }

        $request->validate($userValidationRules);

        \Log::info('Starting final installation step');
        \Log::info('Session data:', [
            'db_connection' => session('install_db_connection'),
            'db_host' => session('install_db_host'),
            'db_database' => session('install_db_database'),
            'site_name' => session('install_site_name'),
            'admin_email' => session('install_admin_email'),
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
            'APP_URL' => session('install_site_url'),
            'APP_TIMEZONE' => session('install_timezone'),
            'SESSION_DRIVER' => 'file', // Use file sessions during installation
        ];

        $mailData = [
            'MAIL_MAILER' => session('install_mail_driver'),
            'MAIL_HOST' => session('install_mail_host'),
            'MAIL_PORT' => session('install_mail_port'),
            'MAIL_USERNAME' => session('install_mail_username'),
            'MAIL_PASSWORD' => session('install_mail_password'),
            'MAIL_ENCRYPTION' => session('install_mail_encryption'),
        ];

        // Update .env first
        $this->updateEnv(array_merge($dbData, $siteData, $mailData));

        // Clear configuration cache
        \Artisan::call('config:clear');

        // Force Laravel to reload the environment
        app()->loadEnvironmentFrom('.env');

        // Manually reload database configuration
        $dbConfig = [
            'default' => session('install_db_connection'),
            'connections' => config('database.connections', []),
        ];

        if (session('install_db_connection') !== 'sqlite') {
            $dbConfig['connections'][session('install_db_connection')] = [
                'driver' => session('install_db_connection'),
                'host' => session('install_db_host'),
                'port' => session('install_db_port'),
                'database' => session('install_db_database'),
                'username' => session('install_db_username'),
                'password' => session('install_db_password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ];
        }

        config(['database' => $dbConfig]);

        // Purge existing connections to force reconnection
        \DB::purge();

        // Now clear application cache (after database is configured)
        \Artisan::call('cache:clear');

        \Log::info('Config updated, current database config:', [
            'default' => config('database.default'),
            'connections' => array_keys(config('database.connections')),
        ]);

        // Set up database connection based on type
        $connection = session('install_db_connection');
        if ($connection === 'sqlite') {
            $dbPath = session('install_db_database');
            if (!str_starts_with($dbPath, '/')) {
                $dbPath = database_path($dbPath);
            }
            if (!File::exists($dbPath)) {
                $dbDir = dirname($dbPath);
                if (!File::exists($dbDir)) {
                    File::makeDirectory($dbDir, 0755, true);
                }
                File::put($dbPath, '');
            }
        } elseif ($connection === 'mysql') {
            // For MySQL, try to create the database if it doesn't exist
            try {
                $pdo = new \PDO(
                    'mysql:host=' . session('install_db_host') . ';port=' . session('install_db_port'),
                    session('install_db_username'),
                    session('install_db_password')
                );
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . session('install_db_database') . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (\Exception $e) {
                // Log the error but continue - database might already exist
                \Log::warning('Failed to create MySQL database: ' . $e->getMessage());
            }
        } elseif ($connection === 'pgsql') {
            // For PostgreSQL, try to create the database if it doesn't exist
            try {
                $pdo = new \PDO(
                    'pgsql:host=' . session('install_db_host') . ';port=' . session('install_db_port'),
                    session('install_db_username'),
                    session('install_db_password')
                );
                $pdo->exec('CREATE DATABASE "' . session('install_db_database') . '" WITH ENCODING \'UTF8\'');
            } catch (\Exception $e) {
                // Log the error but continue - database might already exist
                \Log::warning('Failed to create PostgreSQL database: ' . $e->getMessage());
            }
        } elseif ($connection === 'sqlsrv') {
            // For SQL Server, try to create the database if it doesn't exist
            try {
                $pdo = new \PDO(
                    'sqlsrv:Server=' . session('install_db_host') . ',' . session('install_db_port'),
                    session('install_db_username'),
                    session('install_db_password')
                );
                $pdo->exec('IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = N\'' . session('install_db_database') . '\') CREATE DATABASE [' . session('install_db_database') . ']');
            } catch (\Exception $e) {
                // Log the error but continue - database might already exist
                \Log::warning('Failed to create SQL Server database: ' . $e->getMessage());
            }
        }

        // Test the database connection with the new configuration
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('Failed to connect to database after configuration: ' . $e->getMessage());
        }

        // Run migrations with explicit connection
        \Artisan::call('migrate', [
            '--force' => true,
            '--database' => $connection
        ]);

        // Run the CMS seeder with explicit connection
        \Artisan::call('db:seed', [
            '--class' => 'Buni\\Cms\\Database\\Seeders\\CmsSeeder',
            '--force' => true,
            '--database' => $connection
        ]);

        // Create admin user
        $user = \App\Models\User::create([
            'name' => session('install_admin_name'),
            'email' => session('install_admin_email'),
            'password' => Hash::make(session('install_admin_password')),
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

        $sourceThemePath = __DIR__.'/../../sample-theme';
        $destinationThemePath = $themesPath . '/default';

        if (File::exists($sourceThemePath)) {
            try {
                File::copyDirectory($sourceThemePath, $destinationThemePath);
            } catch (\Exception $e) {
                // Continue installation even if theme copy fails
            }
        }

        // Clear session
        session()->forget([
            'install_db_connection', 'install_db_host', 'install_db_port', 'install_db_database', 'install_db_username', 'install_db_password',
            'install_site_name', 'install_site_url', 'install_timezone',
            'install_admin_name', 'install_admin_email', 'install_admin_password',
            'install_mail_driver', 'install_mail_host', 'install_mail_port', 'install_mail_username', 'install_mail_password', 'install_mail_encryption'
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

    private function testDatabaseConnection($data)
    {
        $connection = $data['db_connection'];

        // Create a temporary database configuration
        $config = [
            'driver' => $connection,
            'database' => $data['db_database'],
        ];

        if ($connection !== 'sqlite') {
            $config = array_merge($config, [
                'host' => $data['db_host'],
                'port' => $data['db_port'],
                'username' => $data['db_username'],
                'password' => $data['db_password'] ?? '',
            ]);
        } else {
            // For SQLite, ensure the path is absolute
            if (!str_starts_with($data['db_database'], '/')) {
                $config['database'] = database_path($data['db_database']);
            }
        }

        // Create a temporary connection to test
        try {
            // Use PDO directly to test the connection
            if ($connection === 'sqlite') {
                $pdo = new \PDO('sqlite:' . $config['database']);
            } else {
                $dsn = $connection . ':host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'];
                $pdo = new \PDO($dsn, $config['username'], $config['password']);
            }

            // Try a simple query to ensure the connection works
            $pdo->query('SELECT 1');
        } catch (\Exception $e) {
            throw new \Exception('Failed to connect to database: ' . $e->getMessage());
        }
    }

    private function updateEnv($data)
    {
        $envFile = base_path('.env');

        \Log::info('Updating .env file with data:', $data);

        if (!File::exists($envFile)) {
            \Log::info('Creating new .env file');
            // Create a basic .env file with default Laravel settings
            $defaultEnv = <<<'EOT'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOT;
            File::put($envFile, $defaultEnv);
        }

        $envContent = File::get($envFile);
        \Log::info('Original .env content length: ' . strlen($envContent));

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$value}";
            if (preg_match($pattern, $envContent)) {
                \Log::info("Updating existing {$key} from " . (preg_match($pattern, $envContent, $matches) ? $matches[0] : 'not found') . " to {$replacement}");
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                \Log::info("Adding new {$key}={$value}");
                $envContent .= "\n{$key}={$value}";
            }
        }

        $result = File::put($envFile, $envContent);
        \Log::info('.env file updated, bytes written: ' . $result);
    }

    private function renderInstallStep($step)
    {
        $steps = [
            1 => 'Database Configuration',
            2 => 'Site Configuration',
            3 => 'Admin Account',
            4 => 'Mail Configuration',
            5 => 'Installation Complete',
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

        /* Skeleton Loading Styles */
        @keyframes skeleton-loading {
            0% {
                background-position: -200px 0;
            }
            100% {
                background-position: calc(200px + 100%) 0;
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200px 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }

        .skeleton-text {
            height: 1rem;
            margin-bottom: 0.5rem;
        }

        .skeleton-input {
            height: 3rem;
            margin-bottom: 1rem;
        }

        .skeleton-button {
            height: 3rem;
            width: 120px;
            margin: 0 auto;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #009cde;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            </div>';

        // Display message if any
        $message = session('message');
        if ($message) {
            $html .= '<div class="mb-8 animate-slide-in-right">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">' . htmlspecialchars($message) . '</p>
                        </div>
                    </div>
                </div>
            </div>';
        }

        $html .= '<!-- Progress Indicator -->
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
        } elseif ($step == 4) {
            $html .= $this->renderMailStep();
        } elseif ($step == 5) {
            $html .= $this->renderInstallCompleteStep();
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

            // Show loading overlay
            const overlay = document.createElement(\'div\');
            overlay.className = \'loading-overlay\';
            overlay.innerHTML = \'<div class="text-center"><div class="loading-spinner mb-4"></div><p class="text-gray-600 font-medium">Processing your request...</p></div>\';
            document.body.appendChild(overlay);
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

        // Database engine selection handler (only if elements exist)
        const dbConnectionSelect = document.getElementById(\'db_connection\');
        if (dbConnectionSelect) {
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

                    // Auto-append .sqlite if not present and not an absolute path
                    if (dbDatabaseInput.value && !dbDatabaseInput.value.includes(\'.sqlite\') && !dbDatabaseInput.value.startsWith(\'/\')) {
                        dbDatabaseInput.value += \'.sqlite\';
                    }
                } else {
                    // Show fields for other databases
                    if (selectedEngine === \'mysql\') {
                        mysqlFields.forEach(field => field.style.display = \'block\');
                        dbHostInput.value = \'127.0.0.1\';
                        dbPortInput.value = \'3306\';
                        dbDatabaseInput.placeholder = \'laravel\';
                    } else if (selectedEngine === \'pgsql\') {
                        pgsqlFields.forEach(field => field.style.display = \'block\');
                        dbHostInput.value = \'127.0.0.1\';
                        dbPortInput.value = \'5432\';
                        dbDatabaseInput.placeholder = \'laravel\';
                    } else if (selectedEngine === \'sqlsrv\') {
                        sqlsrvFields.forEach(field => field.style.display = \'block\');
                        dbHostInput.value = \'127.0.0.1\';
                        dbPortInput.value = \'1433\';
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
        }
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
        // Get all PHP timezones
        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

        // Auto-detect current URL
        $currentUrl = request()->getSchemeAndHttpHost();

        $timezoneOptions = '';
        foreach ($timezones as $timezone) {
            $selected = ($timezone === 'UTC') ? 'selected' : '';
            $timezoneOptions .= '<option value="' . $timezone . '" ' . $selected . '>' . $timezone . '</option>';
        }

        return '<div class="space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Site Configuration</h3>
                <p class="text-gray-600">Configure your website basic settings</p>
            </div>

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
                    <label for="site_url" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        Site URL
                    </label>
                    <input id="site_url" name="site_url" type="url" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-green-500 transition-all duration-200 bg-white shadow-sm"
                           placeholder="https://example.com" value="' . htmlspecialchars($currentUrl) . '" />
                    <p class="mt-1 text-xs text-gray-500">
                        The base URL of your website (auto-detected from current request)
                    </p>
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
                        ' . $timezoneOptions . '
                    </select>
                </div>
            </div>
        </div>';
    }

    private function renderMailStep()
    {
        return '<div class="space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Mail Configuration</h3>
                <p class="text-gray-600">Configure your email settings for notifications and communications</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div class="form-group">
                        <label for="mail_driver" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Mail Driver
                        </label>
                        <select id="mail_driver" name="mail_driver" required
                                class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm">
                            <option value="smtp">SMTP</option>
                            <option value="mailgun">Mailgun</option>
                            <option value="ses">SES</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="log">Log (for development)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mail_host" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            Mail Host
                        </label>
                        <input id="mail_host" name="mail_host" type="text"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm"
                               placeholder="smtp.mailtrap.io" />
                    </div>

                    <div class="form-group">
                        <label for="mail_port" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            Mail Port
                        </label>
                        <input id="mail_port" name="mail_port" type="number"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm"
                               placeholder="587" />
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="form-group">
                        <label for="mail_username" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Mail Username
                        </label>
                        <input id="mail_username" name="mail_username" type="text"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm" />
                    </div>

                    <div class="form-group">
                        <label for="mail_password" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Mail Password
                        </label>
                        <input id="mail_password" name="mail_password" type="password"
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm" />
                    </div>

                    <div class="form-group">
                        <label for="mail_encryption" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Mail Encryption
                        </label>
                        <select id="mail_encryption" name="mail_encryption"
                                class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 brand-ring focus:border-purple-500 transition-all duration-200 bg-white shadow-sm">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">None</option>
                        </select>
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

            <div class="space-y-6">
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
            </div>
        </div>';
    }

    private function renderInstallCompleteStep()
    {
        return '<div class="space-y-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Installation Complete!</h3>
                <p class="text-gray-600">Your Buni CMS has been successfully installed and configured.</p>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="text-lg font-semibold text-green-800">Installation Successful</h4>
                </div>
                <p class="text-green-700 mb-4">
                    Your CMS is now ready to use. You can access the admin panel to start creating content.
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="/admin/login" class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Go to Admin Panel
                    </a>
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 border border-green-600 text-green-600 font-semibold rounded-lg hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        View Homepage
                    </a>
                </div>
            </div>
        </div>';
    }

    public function showLogin()
    {
        return $this->renderLoginPage();
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (\Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        \Illuminate\Support\Facades\Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function renderLoginPage()
    {
        $errors = session('errors') ?: collect();
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="' . csrf_token() . '">
    <title>Admin Login - Buni CMS</title>
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
                transform: translateY(0);
            }
            80% {
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        .bounce-in {
            animation: bounceIn 1s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bounce-in">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Admin Login
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Sign in to access the admin panel
                </p>
            </div>
        </div>

        <div class="fade-in-up mt-8">
            <div class="bg-white py-8 px-6 shadow-xl rounded-lg border border-gray-200">
                <form class="space-y-6" action="' . route('cms.admin.login') . '" method="POST">
                    ' . csrf_field() . '

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                value="' . old('email') . '"
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm transition-all duration-200"
                                placeholder="Enter your email"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                        </div>
                        ' . ($errors->has('email') ? '<p class="mt-1 text-sm text-red-600">' . $errors->first('email') . '</p>' : '') . '
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm transition-all duration-200"
                                placeholder="Enter your password"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        ' . ($errors->has('password') ? '<p class="mt-1 text-sm text-red-600">' . $errors->first('password') . '</p>' : '') . '
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105"
                        >
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                            </span>
                            Sign in
                        </button>
                    </div>
                </form>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <a href="/" class="text-sm text-blue-600 hover:text-blue-500 transition-colors duration-200">
                        ← Back to homepage
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive enhancements
        document.addEventListener("DOMContentLoaded", function() {
            // Focus on email field
            const emailField = document.getElementById("email");
            if (emailField && !emailField.value) {
                emailField.focus();
            }

            // Add loading state to form submission
            const form = document.querySelector("form");
            const submitButton = document.querySelector("button[type=\"submit\"]");

            form.addEventListener("submit", function() {
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in...
                `;
            });
        });
    </script>
</body>
</html>';

        return response($html);
    }
}
