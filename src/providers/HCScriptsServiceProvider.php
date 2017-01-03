<?php

namespace interactivesolutions\honeycombscripts\providers;

use Illuminate\Support\ServiceProvider;
use interactivesolutions\honeycombscripts\commands\CreateEnvFile;
use interactivesolutions\honeycombscripts\commands\CreateService;
use interactivesolutions\honeycombscripts\commands\GenerateRoutes;
use interactivesolutions\honeycombscripts\commands\PrepareProject;

class HCScriptsServiceProvider extends ServiceProvider
{
    /**
     * Register commands
     *
     * @var array
     */
    protected $commands = [
        CreateEnvFile::class,
        CreateService::class,
        PrepareProject::class,
        GenerateRoutes::class,
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


