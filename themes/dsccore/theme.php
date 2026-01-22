<?php
// DSCCore theme bootstrap for Buni CMS - registers provider and boots theme features

// load provider class (file-based include to avoid relying on composer autoload)
require_once __DIR__.'/src/Providers/ThemeServiceProvider.php';

$provider = new \Buni\Cms\SampleTheme\Dsccore\Providers\ThemeServiceProvider(app());
$provider->register();
$provider->boot();
