<?php

namespace app\providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'app\http\controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot ()
    {
        parent::boot ();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map ()
    {
        $this->mapHCRoutes ();
    }

    /**
     * Including HoneyComb routes
     * @return void
     */
    protected function mapHCRoutes ()
    {
        $routes = base_path ("app/honeycomb/routes.php");

        if (file_exists ($routes))
            Route::group (['namespace' => $this->namespace], function ($router) use ($routes) {
                require $routes;
            });

        $routes = base_path ("app/honeycomb/routes-custom.php");

        if (file_exists ($routes))
            Route::group ([], function ($router) use ($routes) {
                require $routes;
            });
    }
}
