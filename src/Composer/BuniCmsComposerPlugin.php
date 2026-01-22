<?php
namespace Buni\Cms\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;

class BuniCmsComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostPackageUninstall',
        ];
    }

    public function onPostPackageInstall(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if (!($operation instanceof InstallOperation)) {
            return;
        }

        $pkg = $operation->getPackage();
        if ($pkg->getName() !== 'buni/cms') {
            return;
        }

        $rootComposerPath = $this->getRootComposerJsonPath();
        if (!file_exists($rootComposerPath)) {
            return;
        }

        $data = json_decode((string) file_get_contents($rootComposerPath), true);
        if (!is_array($data)) {
            return;
        }

        $sections = ['require', 'require-dev'];
        $found = [];
        foreach ($sections as $section) {
            if (!empty($data[$section]) && isset($data[$section]['laravel/fortify'])) {
                $found[$section] = $data[$section]['laravel/fortify'];
                unset($data[$section]['laravel/fortify']);
            }
        }

        if (empty($found)) {
            $this->io->write('<info>buni/cms install: no laravel/fortify dependency found in root composer.json.</info>');
            return;
        }

        // Save backup inside vendor/buni/cms so we can restore on uninstall
        $backupDir = $this->composer->getConfig()->get('vendor-dir') . '/buni/cms';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $backupFile = $backupDir . '/.fortify-backup.json';
        file_put_contents($backupFile, json_encode($found, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Write modified composer.json
        file_put_contents($rootComposerPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->io->write('<comment>buni/cms plugin: removed laravel/fortify from root composer.json to avoid conflicts.</comment>');

        $rootDir = dirname($rootComposerPath);

        // Ensure /login routes point to the CMS and remove other login mappings
        $this->ensureLoginRoutes($rootDir);

        // Copy package themes into the application's themes directory
        $this->copyPackageThemesToApp();

        // Attempt to build core Tailwind CSS into public/vendor/cms/tailwind.css
        $this->buildCoreTailwind($rootDir, $packageDir ?? null);

        $this->io->write('<comment>Attempting to run composer update to apply changes...</comment>');
        $result = $this->runCommand('composer update --no-interaction', $rootDir);
        if ($result['exit'] !== 0) {
            $this->io->write('<error>Automatic composer update failed. Please run `composer update` in your project root.</error>');
            $this->io->write($result['output']);
        } else {
            $this->io->write('<info>composer update completed successfully.</info>');
        }
    }

    public function onPostPackageUninstall(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if (!($operation instanceof UninstallOperation)) {
            return;
        }

        $pkg = $operation->getPackage();
        if ($pkg->getName() !== 'buni/cms') {
            return;
        }

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $backupFile = $vendorDir . '/buni/cms/.fortify-backup.json';
        $rootComposerPath = $this->getRootComposerJsonPath();

        if (!file_exists($backupFile) || !file_exists($rootComposerPath)) {
            $this->io->write('<info>buni/cms uninstall: no backup found to restore laravel/fortify.</info>');
            return;
        }

        $backup = json_decode((string) file_get_contents($backupFile), true);
        if (!is_array($backup)) {
            return;
        }

        $data = json_decode((string) file_get_contents($rootComposerPath), true);
        if (!is_array($data)) {
            return;
        }

        foreach ($backup as $section => $version) {
            if (!isset($data[$section]) || !is_array($data[$section])) {
                $data[$section] = [];
            }
            $data[$section]['laravel/fortify'] = $version;
        }

        file_put_contents($rootComposerPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        @unlink($backupFile);

        $this->io->write('<comment>buni/cms plugin: restored laravel/fortify entry to root composer.json.</comment>');

        $rootDir = dirname($rootComposerPath);
        // After uninstall, attempt to restore any original login routes is out of scope.
        $this->io->write('<comment>Attempting to run composer update to install restored dependencies...</comment>');
        $result = $this->runCommand('composer update --no-interaction', $rootDir);
        if ($result['exit'] !== 0) {
            $this->io->write('<error>Automatic composer update failed. Please run `composer update` in your project root.</error>');
            $this->io->write($result['output']);
        } else {
            $this->io->write('<info>composer update completed successfully.</info>');
        }
    }

    public function onPostPackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();
        // Operation may be an UpdateOperation; ignore when not relevant
        $package = null;
        if (method_exists($operation, 'getTargetPackage')) {
            $package = $operation->getTargetPackage();
        } elseif (method_exists($operation, 'getPackage')) {
            $package = $operation->getPackage();
        }

        if (!$package || $package->getName() !== 'buni/cms') {
            return;
        }

        $rootComposerPath = $this->getRootComposerJsonPath();
        $rootDir = dirname($rootComposerPath);

        // Ensure /login routes point to the CMS and remove other login mappings
        $this->ensureLoginRoutes($rootDir);

        // Copy/update package themes into the application's themes directory
        $this->copyPackageThemesToApp();

        // Attempt to build core Tailwind CSS into public/vendor/cms/tailwind.css
        $this->buildCoreTailwind($rootDir, $this->composer->getConfig()->get('vendor-dir') . '/buni/cms');
    }

    private function buildCoreTailwind(string $rootDir, ?string $packageDir = null)
    {
        if (empty($packageDir)) {
            $packageDir = $this->composer->getConfig()->get('vendor-dir') . '/buni/cms';
        }

        $input = $packageDir . '/resources/css/tailwind.css';
        if (!file_exists($input)) {
            $this->io->write('<info>buni/cms plugin: core Tailwind input not found; skipping build.</info>');
            return;
        }

        $destDir = $rootDir . '/public/vendor/cms';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $output = $destDir . '/tailwind.css';

        // Prefer npx tailwindcss if available in the environment
        $cmd = 'npx tailwindcss -i ' . escapeshellarg($input) . ' -o ' . escapeshellarg($output) . ' --minify';
        $this->io->write('<comment>buni/cms plugin: attempting to build core Tailwind CSS...</comment>');
        $result = $this->runCommand($cmd, $rootDir);
        if ($result['exit'] !== 0) {
            $this->io->write('<comment>buni/cms plugin: core Tailwind build failed or npx not available. Skipping. Output:</comment>');
            $this->io->write($result['output']);
        } else {
            $this->io->write('<info>buni/cms plugin: built core Tailwind CSS to public/vendor/cms/tailwind.css</info>');
        }
    }

    private function ensureLoginRoutes(string $rootDir)
    {
        $routesFile = $rootDir . '/routes/web.php';
        if (!file_exists($routesFile)) {
            $this->io->write('<info>buni/cms plugin: no routes/web.php found in project root; skipping login route adjustments.</info>');
            return;
        }

        // Check root composer.json extra to see if safe route editing is enabled.
        $rootComposer = $this->getRootComposerJsonPath();
        $extraConfig = [];
        if (file_exists($rootComposer)) {
            $rootData = json_decode((string) file_get_contents($rootComposer), true);
            if (is_array($rootData) && isset($rootData['extra']) && is_array($rootData['extra'])) {
                $extraConfig = $rootData['extra'];
            }
        }

        $enabled = false;
        if (isset($extraConfig['buni-cms']) && is_array($extraConfig['buni-cms']) && !empty($extraConfig['buni-cms']['safe_route_edit'])) {
            $enabled = (bool) $extraConfig['buni-cms']['safe_route_edit'];
        }

        if (!$enabled) {
            $this->io->write('<comment>buni/cms plugin: safe_route_edit not enabled in composer.json extra; skipping route edits.</comment>');
            return;
        }

        // Back up existing routes/web.php before making changes
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $backupDir = $vendorDir . '/buni/cms/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $timestamp = date('YmdHis');
        $backupFile = $backupDir . '/routes.web.php.' . $timestamp . '.bak';
        copy($routesFile, $backupFile);
        $this->io->write('<info>buni/cms plugin: backed up routes/web.php to ' . $backupFile . '.</info>');

        $content = file_get_contents($routesFile);

        // Remove existing login/password/two-factor route definitions (simple approach)
        $pattern = '/^\s*Route::(?:get|post)\(\s*["\'](?:\\/)?(?:login|password\\/reset|password\\/email|password\\/confirm|two-factor|two-factor\\/challenge)["\'][\s\S]*?;\s*$/m';
        $new = preg_replace($pattern, '', $content);

        // Remove previous Buni CMS auth block if exists
        $new = preg_replace('/\/\/ Buni CMS auth routes START[\s\S]*?\/\/ Buni CMS auth routes END\n?/m', '', $new);

        // Append our canonical CMS login routes
        $block = "\n// Buni CMS auth routes START\nRoute::get('login', ['Buni\\\\Cms\\\\Controllers\\\\Frontend\\\\PageController', 'showLogin'])->name('login');\nRoute::post('login', ['Buni\\\\Cms\\\\Controllers\\\\Frontend\\\\PageController', 'login'])->name('login.store');\nRoute::post('logout', ['Buni\\\\Cms\\\\Controllers\\\\Frontend\\\\PageController', 'logout'])->name('logout');\n// Buni CMS auth routes END\n";

        $new = rtrim($new, "\n") . "\n" . $block;

        file_put_contents($routesFile, $new);
        $this->io->write('<info>buni/cms plugin: ensured /login routes point to the CMS (safe edit).</info>');
    }

    private function copyPackageThemesToApp()
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $packageDir = $vendorDir . '/buni/cms';
        $rootDir = dirname($vendorDir);
        $appThemesDir = $rootDir . '/themes';

        if (!is_dir($packageDir)) {
            $this->io->write('<info>buni/cms plugin: package directory not found; skipping theme copy.</info>');
            return;
        }

        if (!is_dir($appThemesDir)) {
            @mkdir($appThemesDir, 0755, true);
        }

        $entries = scandir($packageDir);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $candidate = $packageDir . '/' . $entry;
                if (is_dir($candidate) && file_exists($candidate . '/theme.json')) {
                // If the theme declares a package.json, try to build it (npm)
                if (file_exists($candidate . '/package.json')) {
                    $this->io->write('<comment>buni/cms plugin: found package.json for theme ' . $entry . ', attempting npm install and build.</comment>');
                    $npmResult = $this->runCommand('npm ci --no-audit --prefer-offline', $candidate);
                    $this->io->write($npmResult['output']);
                    $buildResult = $this->runCommand('npm run build --silent', $candidate);
                    $this->io->write($buildResult['output']);
                    if ($npmResult['exit'] !== 0 || $buildResult['exit'] !== 0) {
                        $this->io->write('<comment>buni/cms plugin: theme build failed for ' . $entry . '. Continuing and copying available files.</comment>');
                    } else {
                        $this->io->write('<info>buni/cms plugin: theme build succeeded for ' . $entry . '.</info>');
                    }
                }

                $dest = $appThemesDir . '/' . $entry;
                $this->recursiveCopy($candidate, $dest);
                $this->io->write('<info>buni/cms plugin: copied theme ' . $entry . ' to themes/' . $entry . '.</info>');
            }
        }
    }

    private function recursiveCopy(string $src, string $dst)
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            @mkdir($dst, 0755, true);
        }
        while(false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;
                if (is_dir($srcPath)) {
                    $this->recursiveCopy($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Run a shell command and return exit code and output.
     *
     * @param string $cmd
     * @param string|null $cwd
     * @return array{exit:int,output:string}
     */
    private function runCommand(string $cmd, ?string $cwd = null): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd);
        if (!is_resource($process)) {
            return ['exit' => 1, 'output' => 'Failed to start process'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exit = proc_close($process);
        $output = trim($stdout . PHP_EOL . $stderr);

        return ['exit' => $exit, 'output' => $output];
    }

    private function getRootComposerJsonPath()
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $rootDir = dirname($vendorDir);
        return $rootDir . '/composer.json';
    }
}
