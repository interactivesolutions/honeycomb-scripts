<?php

namespace interactivesolutions\honeycombscripts\app\providers;

use interactivesolutions\honeycombcore\providers\HCBaseServiceProvider;
use interactivesolutions\honeycombscripts\app\commands\HCDocs;
use interactivesolutions\honeycombscripts\app\commands\HCEnv;
use interactivesolutions\honeycombscripts\app\commands\HCUpdate;
use interactivesolutions\honeycombscripts\app\commands\MakeHCPackage;
use interactivesolutions\honeycombscripts\app\commands\MakeHCService;
use interactivesolutions\honeycombscripts\app\commands\HCRoutes;
use interactivesolutions\honeycombscripts\app\commands\MakeHCProject;
use interactivesolutions\honeycombscripts\app\commands\HCSeed;
use Way\Generators\GeneratorsServiceProvider;
use Xethron\MigrationsGenerator\MigrationsGeneratorServiceProvider;

class HCScriptsServiceProvider extends HCBaseServiceProvider
{
    protected $homeDirectory = __DIR__;

    protected $commands = [
        HCEnv::class,
        MakeHCService::class,
        MakeHCProject::class,
        MakeHCPackage::class,
        HCRoutes::class,
        HCSeed::class,
        HCUpdate::class,
    ];

    /**
     * Register the application services.
     *
     * @return void
     */
    public function registerProviders()
    {
        if ($this->app->environment() !== 'production')
        {
            $this->app->register(GeneratorsServiceProvider::class);
            $this->app->register(MigrationsGeneratorServiceProvider::class);
        }
    }
}


