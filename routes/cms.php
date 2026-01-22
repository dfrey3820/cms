<?php

use Illuminate\Support\Facades\Route;
use Buni\Cms\Controllers\Admin\DashboardController;
use Buni\Cms\Controllers\Admin\PageController;
use Buni\Cms\Controllers\Admin\PostController;
use Buni\Cms\Controllers\Admin\SettingsController;
use Buni\Cms\Controllers\Admin\UpdatesController;
use Buni\Cms\Controllers\Admin\PluginsController;
use Buni\Cms\Controllers\Admin\ProfileController;
use Buni\Cms\Controllers\Admin\ThemesController;
use Buni\Cms\Controllers\Frontend\PageController as FrontendPageController;

// Auth routes use a separate prefix so they can be exposed at /auth
Route::prefix(config('cms.auth_prefix', 'auth'))->middleware(['web'])->group(function () {
    Route::get('/login', [FrontendPageController::class, 'showLogin'])->name('cms.admin.login');
    Route::post('/login', [FrontendPageController::class, 'login'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('cms.admin.login.post');
    // Registration and password reset handled by CMS
    Route::get('/register', [FrontendPageController::class, 'showRegister'])->name('cms.admin.register');
    Route::post('/register', [FrontendPageController::class, 'register'])->name('cms.admin.register.post');

    Route::get('/password/forgot', [FrontendPageController::class, 'showForgot'])->name('cms.admin.password.request');
    Route::post('/password/email', [FrontendPageController::class, 'sendResetLink'])->name('cms.admin.password.email');
    Route::get('/password/reset/{token}', [FrontendPageController::class, 'showReset'])->name('cms.admin.password.reset');
    Route::post('/password/reset', [FrontendPageController::class, 'resetPassword'])->name('cms.admin.password.update');
});

Route::prefix(config('cms.admin_prefix'))->middleware(['web', 'auth', 'cms.maintenance'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('cms.admin.dashboard');
    Route::resource('pages', PageController::class)->names('cms.admin.pages');
    Route::delete('pages/bulk-delete', [PageController::class, 'bulkDelete'])->name('cms.admin.pages.bulk-delete');
    Route::resource('posts', PostController::class)->names('cms.admin.posts');
    Route::delete('posts/bulk-delete', [PostController::class, 'bulkDelete'])->name('cms.admin.posts.bulk-delete');
    Route::get('/settings', [SettingsController::class, 'index'])->name('cms.admin.settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('cms.admin.settings.update');
    Route::post('/settings/editor', [SettingsController::class, 'updateEditor'])->name('cms.admin.settings.editor.update');
    Route::get('/profile', [ProfileController::class, 'index'])->name('cms.admin.profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('cms.admin.profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('cms.admin.profile.password');
    Route::get('/updates', [\Buni\Cms\Controllers\Admin\UpdatesController::class, 'index'])->name('cms.admin.updates');
    Route::post('/updates/check', [\Buni\Cms\Controllers\Admin\UpdatesController::class, 'checkForUpdates'])->name('cms.admin.updates.check');
    Route::post('/updates/install', [\Buni\Cms\Controllers\Admin\UpdatesController::class, 'installUpdate'])->name('cms.admin.updates.install');
    Route::post('/updates/migrations', [\Buni\Cms\Controllers\Admin\UpdatesController::class, 'runMigrations'])->name('cms.admin.updates.migrations');
    Route::post('/updates/maintenance', [\Buni\Cms\Controllers\Admin\UpdatesController::class, 'toggleMaintenance'])->name('cms.admin.updates.maintenance');
    Route::resource('plugins', PluginsController::class)->names('cms.admin.plugins');
    Route::post('plugins/{plugin}/activate', [PluginsController::class, 'activate'])->name('cms.admin.plugins.activate');
    Route::post('plugins/{plugin}/deactivate', [PluginsController::class, 'deactivate'])->name('cms.admin.plugins.deactivate');
    Route::resource('users', \Buni\Cms\Controllers\Admin\UserController::class)->names('cms.admin.users');
    Route::post('/users/{user}/send-reset', [\Buni\Cms\Controllers\Admin\UserController::class, 'sendReset'])->name('cms.admin.users.send-reset');
    Route::post('/logout', [FrontendPageController::class, 'logout'])->name('cms.admin.logout');
    // Themes
    Route::get('/themes', [ThemesController::class, 'index'])->name('cms.admin.themes');
    Route::post('/themes/{theme}/activate', [ThemesController::class, 'activate'])->name('cms.admin.themes.activate');
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