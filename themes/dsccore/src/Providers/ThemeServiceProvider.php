<?php
namespace Buni\Cms\SampleTheme\Dsccore\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Route;

class ThemeServiceProvider
{
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function register()
    {
        // Bind a simple theme accessor
        $this->app->bind('theme.dsccore', function ($app) {
            return (object) ['name' => 'DSC Core'];
        });
    }

    public function boot()
    {
        // ensure controller file is available (no composer autoload guarantee)
        require_once __DIR__.'/../Controllers/ThemeController.php';

        Route::middleware('web')->prefix('admin')->group(function () {
            Route::get('/theme/dsccore', '\\Buni\\Cms\\SampleTheme\\Dsccore\\Controllers\\ThemeController@index')->name('theme.dsccore.index');
        });
    }
}
