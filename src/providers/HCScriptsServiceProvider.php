<?php

namespace interactivesolutions\honeycombscripts\providers;

use Illuminate\Support\ServiceProvider;
use interactivesolutions\honeycombscripts\commands\HCDocs;
use interactivesolutions\honeycombscripts\commands\HCEnv;
use interactivesolutions\honeycombscripts\commands\HCUpdate;
use interactivesolutions\honeycombscripts\commands\MakeHCPackage;
use interactivesolutions\honeycombscripts\commands\MakeHCService;
use interactivesolutions\honeycombscripts\commands\HCRoutes;
use interactivesolutions\honeycombscripts\commands\MakeHCProject;
use interactivesolutions\honeycombscripts\commands\HCSeed;

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


