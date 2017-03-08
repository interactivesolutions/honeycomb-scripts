<?php

namespace interactivesolutions\honeycombscripts\app\providers;

use Illuminate\Support\ServiceProvider;
use interactivesolutions\honeycombscripts\app\commands\HCDocs;
use interactivesolutions\honeycombscripts\app\commands\HCEnv;
use interactivesolutions\honeycombscripts\app\commands\HCUpdate;
use interactivesolutions\honeycombscripts\app\commands\MakeHCPackage;
use interactivesolutions\honeycombscripts\app\commands\MakeHCService;
use interactivesolutions\honeycombscripts\app\commands\HCRoutes;
use interactivesolutions\honeycombscripts\app\commands\MakeHCProject;
use interactivesolutions\honeycombscripts\app\commands\HCSeed;

class HCScriptsServiceProvider extends ServiceProvider
{
    /**
     * Register commands
     *
     * @var array
     */
    protected $commands = [
        HCDocs::class,
        HCEnv::class,
        MakeHCService::class,
        MakeHCProject::class,
        MakeHCPackage::class,
        HCRoutes::class,
        HCSeed::class,
        HCUpdate::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // register artisan commands
        $this->commands($this->commands);

        if ($this->app->environment() !== 'production')
        {
            $this->app->register(\Way\Generators\GeneratorsServiceProvider::class);
            $this->app->register(\Xethron\MigrationsGenerator\MigrationsGeneratorServiceProvider::class);
        }

        $this->registerHelpers();
    }

    /**
     * Register helper function
     */
    private function registerHelpers()
    {
        $filePath = __DIR__ . '/../helpers/helpers.php';

        if (\File::isFile($filePath))
            require_once $filePath;
    }
}


