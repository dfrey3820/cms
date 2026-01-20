<?php

namespace Buni\Cms\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsIsInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if .env file exists and is valid
        $envFile = base_path('.env');
        if (!file_exists($envFile) || !is_readable($envFile) || trim(file_get_contents($envFile)) === '') {
            return redirect('/install?step=1')->with('message', 'CMS is not installed. Please complete the installation first.');
        }

        // Check if CMS is installed (has users table and at least one user)
        try {
            $isInstalled = Schema::hasTable('users') && \App\Models\User::count() > 0;
        } catch (\Exception $e) {
            // Database doesn't exist or is not accessible, redirect to install
            $isInstalled = false;
        }

        if (!$isInstalled) {
            // Allow access to installation routes and admin login
            if ($request->is('install*') || $request->is('admin/login*')) {
                return $next($request);
            }

            return redirect('/install?step=1')->with('message', 'CMS is not installed. Please complete the installation first.');
        }

        return $next($request);
    }
}