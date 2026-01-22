<?php

namespace Buni\Cms\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class BuildTailwindCommand extends Command
{
    protected $signature = 'cms:build-tailwind';
    protected $description = 'Build the core Tailwind CSS for Buni CMS (uses npx tailwindcss)';

    public function handle()
    {
        $this->info('Building core Tailwind CSS for Buni CMS...');

        $base = base_path();
        $input = $base . '/vendor/buni/cms/resources/css/tailwind.css';
        $fallback = $base . '/vendor/buni/cms/resources/css/tailwind.compiled.css';
        $destDir = public_path('vendor/cms');
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }
        $output = $destDir . '/tailwind.css';

        if (!file_exists($input)) {
            $this->error('Tailwind input not found in vendor/buni/cms; nothing to build.');
            return 1;
        }

        // Try to run npx tailwindcss
        $this->line('Running npx tailwindcss...');
        $process = new Process(['npx', 'tailwindcss', '-i', $input, '-o', $output, '--minify'], $base);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->warn('npx build failed: ' . $process->getExitCodeText());
            $this->line($process->getErrorOutput());

            if (file_exists($fallback)) {
                copy($fallback, $output);
                $this->info('Copied bundled fallback CSS to ' . $output);
                return 0;
            }

            $this->error('No fallback CSS available; core styles may be missing.');
            return 1;
        }

        $this->info('Built core Tailwind CSS to ' . $output);
        return 0;
    }
}
