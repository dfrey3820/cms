<?php

use Illuminate\Support\Facades\Route;
use Buni\Cms\Controllers\Admin\DashboardController;
use Buni\Cms\Controllers\Admin\PageController;
use Buni\Cms\Controllers\Admin\SettingsController;
use Buni\Cms\Controllers\Frontend\PageController as FrontendPageController;

Route::prefix(config('cms.admin_prefix'))->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('cms.admin.dashboard');
    Route::resource('pages', PageController::class)->names('cms.admin.pages');
    Route::get('/settings', [SettingsController::class, 'index'])->name('cms.admin.settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('cms.admin.settings.update');
});

Route::middleware(['web'])->group(function () {
    // Frontend routes
    Route::get('/{slug?}', [FrontendPageController::class, 'show'])->where('slug', '.*')->name('cms.page');
    Route::post('/install', [FrontendPageController::class, 'install'])->name('cms.install');
});