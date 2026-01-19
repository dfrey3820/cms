<?php

use Illuminate\Support\Facades\Route;
use Dsc\Cms\Controllers\Admin\DashboardController;
use Dsc\Cms\Controllers\Admin\PageController;

Route::prefix(config('cms.admin_prefix'))->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('cms.admin.dashboard');
    Route::resource('pages', PageController::class)->names('cms.admin.pages');
});

Route::middleware(['web'])->group(function () {
    // Frontend routes
    Route::get('/{slug}', function ($slug) {
        // Resolve page by slug
    })->where('slug', '.*');
});