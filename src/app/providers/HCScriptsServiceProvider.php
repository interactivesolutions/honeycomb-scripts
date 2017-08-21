<?php

namespace interactivesolutions\honeycombscripts\app\providers;

use interactivesolutions\honeycombcore\providers\HCBaseServiceProvider;
use interactivesolutions\honeycombscripts\app\commands\HCEnv;
use interactivesolutions\honeycombscripts\app\commands\HCLanguages;
use interactivesolutions\honeycombscripts\app\commands\HCUpdate;
use interactivesolutions\honeycombscripts\app\commands\HCNewPackage;
use interactivesolutions\honeycombscripts\app\commands\HCNewService;
use interactivesolutions\honeycombscripts\app\commands\HCRoutes;
use interactivesolutions\honeycombscripts\app\commands\HCNewProject;
use interactivesolutions\honeycombscripts\app\commands\HCSeed;
use interactivesolutions\honeycombscripts\app\commands\HCUpdateComposerDependencies;
use Way\Generators\GeneratorsServiceProvider;
use Xethron\MigrationsGenerator\MigrationsGeneratorServiceProvider;

class HCScriptsServiceProvider extends HCBaseServiceProvider
{
    protected $homeDirectory = __DIR__;

    protected $commands = [
        HCEnv::class,
        HCNewService::class,
        HCNewProject::class,
        HCNewPackage::class,
        HCRoutes::class,
        HCSeed::class,
        HCUpdate::class,
        HCUpdateComposerDependencies::class,
        HCLanguages::class,
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


