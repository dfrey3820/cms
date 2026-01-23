<?php
$root = dirname(__DIR__, 4); // vendor/buni/cms/scripts -> project root
$from = $root . DIRECTORY_SEPARATOR . 'vite.config.ts';
$to = $root . DIRECTORY_SEPARATOR . 'vite.config.disabled.ts';
if (file_exists($from)) {
    if (!file_exists($to)) {
        rename($from, $to);
        echo "Renamed vite.config.ts to vite.config.disabled.ts\n";
    } else {
        echo "vite.config.disabled.ts already exists; leaving it.\n";
    }
} else {
    echo "No vite.config.ts found to disable.\n";
}
