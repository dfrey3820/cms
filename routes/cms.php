<?php

use Illuminate\Support\Facades\Route;
use Buni\Cms\Controllers\Admin\DashboardController;
use Buni\Cms\Controllers\Admin\PageController;
use Buni\Cms\Controllers\Frontend\PageController as FrontendPageController;

Route::prefix(config('cms.admin_prefix'))->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('cms.admin.dashboard');
    Route::resource('pages', PageController::class)->names('cms.admin.pages');
});

Route::middleware(['web'])->group(function () {
    // Frontend routes
    Route::get('/{slug?}', [FrontendPageController::class, 'show'])->where('slug', '.*')->name('cms.page');
});