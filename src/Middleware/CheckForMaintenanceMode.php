<?php

namespace Buni\Cms\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isDownForMaintenance()) {
            // Allow access to updates page during maintenance
            if ($request->is(config('cms.admin_prefix') . '/updates*')) {
                return $next($request);
            }

            // Allow access to login page
            if ($request->is(config('cms.admin_prefix') . '/login*')) {
                return $next($request);
            }

            // For other admin routes, show maintenance message
            if ($request->is(config('cms.admin_prefix') . '/*')) {
                return response()->view('errors.maintenance', [], 503);
            }
        }

        return $next($request);
    }
}