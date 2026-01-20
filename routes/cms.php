<?php

use Illuminate\Support\Facades\Route;
use Buni\Cms\Controllers\Admin\DashboardController;
use Buni\Cms\Controllers\Admin\PageController;
use Buni\Cms\Controllers\Admin\SettingsController;
use Buni\Cms\Controllers\Frontend\PageController as FrontendPageController;

Route::prefix(config('cms.admin_prefix'))->middleware(['web'])->group(function () {
    Route::get('/login', [FrontendPageController::class, 'showLogin'])->name('cms.admin.login');
    Route::post('/login', [FrontendPageController::class, 'login'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('cms.admin.login.post');
});

Route::prefix(config('cms.admin_prefix'))->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('cms.admin.dashboard');
    Route::resource('pages', PageController::class)->names('cms.admin.pages');
    Route::get('/settings', [SettingsController::class, 'index'])->name('cms.admin.settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('cms.admin.settings.update');
    Route::post('/logout', [FrontendPageController::class, 'logout'])->name('cms.admin.logout');
});

Route::middleware(['web'])->group(function () {
    // Installation routes (before CMS is installed)
    Route::get('/install', [FrontendPageController::class, 'showInstall'])->name('cms.install.get');
    Route::post('/install', [FrontendPageController::class, 'install'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('cms.install');
    
    // Frontend routes - protected by installation middleware
    Route::middleware(['cms.installed'])->group(function () {
        Route::get('/{slug?}', [FrontendPageController::class, 'show'])->where('slug', '[a-zA-Z0-9\-_]+');
    });
});