<?php
// This script is executed by composer in the consuming app after package install/update.
// It prepends the package view directory to the app's View Finder in AppServiceProvider.

$appServiceProvider = getcwd() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . 'AppServiceProvider.php';
$backupDir = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'buni' . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR . 'backups';

if (!file_exists($appServiceProvider)) {
    echo "AppServiceProvider not found at {$appServiceProvider}\n";
    exit(0);
}

$content = file_get_contents($appServiceProvider);
if (str_contains($content, "prependLocation('vendor/buni/cms/resources/views')") || str_contains($content, "prependLocation(base_path('vendor/buni/cms/resources/views'))")) {
    echo "AppServiceProvider already configured to prefer package views.\n";
    exit(0);
}

// Backup original
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}
$backupFile = $backupDir . DIRECTORY_SEPARATOR . 'AppServiceProvider.php.' . date('YmdHis') . '.bak';
copy($appServiceProvider, $backupFile);
echo "Backed up AppServiceProvider to {$backupFile}\n";

// Insert use statement for View facade if missing, and prepend code in boot()
$useView = "use Illuminate\\Support\\Facades\\View;";
if (!str_contains($content, $useView)) {
    // Insert after other use statements block
    $content = preg_replace('/(use [^;]+;\s*)+/', "$0\n$useView\n", $content, 1);
}

// Add prepend logic inside boot() method
$needle = 'public function boot()';
$pos = strpos($content, $needle);
if ($pos === false) {
    echo "Couldn't find boot() method in AppServiceProvider.\n";
    exit(0);
}

$insertion = <<<'PHP'

        // Prefers package-provided views (buni/cms) over app views when present.
        $packageViews = base_path('vendor/buni/cms/resources/views');
        if (is_dir($packageViews)) {
            $finder = View::getFinder();
            // Prepend package views so they are searched first by the view resolver.
            $finder->prependLocation($packageViews);
        }

PHP;

// Find the opening brace of boot() and insert after the opening line
$bootPos = strpos($content, '{', $pos);
if ($bootPos === false) {
    echo "Couldn't parse AppServiceProvider boot() body.\n";
    exit(0);
}

$insertPos = $bootPos + 1;
$content = substr_replace($content, $insertion, $insertPos, 0);

file_put_contents($appServiceProvider, $content);
echo "Inserted package view prepend into AppServiceProvider.\n";

exit(0);
