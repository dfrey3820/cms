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
        $this->io->write('<comment>Attempting to run composer update to install restored dependencies...</comment>');
        $result = $this->runCommand('composer update --no-interaction', $rootDir);
        if ($result['exit'] !== 0) {
            $this->io->write('<error>Automatic composer update failed. Please run `composer update` in your project root.</error>');
            $this->io->write($result['output']);
        } else {
            $this->io->write('<info>composer update completed successfully.</info>');
        }
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
