<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\providers;

use InteractiveSolutions\HoneycombCore\Providers\HCBaseServiceProvider;
use InteractiveSolutions\HoneycombScripts\app\commands\HCEnv;
use InteractiveSolutions\HoneycombScripts\app\commands\HCLanguages;
use InteractiveSolutions\HoneycombScripts\app\commands\HCUpdate;
use InteractiveSolutions\HoneycombScripts\app\commands\HCNewPackage;
use InteractiveSolutions\HoneycombScripts\app\commands\HCNewService;
use InteractiveSolutions\HoneycombScripts\app\commands\HCRoutes;
use InteractiveSolutions\HoneycombScripts\app\commands\HCNewProject;
use InteractiveSolutions\HoneycombScripts\app\commands\HCSeed;
use InteractiveSolutions\HoneycombScripts\app\commands\HCUpdateComposerDependencies;
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
        if ($this->app->environment() !== 'production') {
            $this->app->register(GeneratorsServiceProvider::class);
            $this->app->register(MigrationsGeneratorServiceProvider::class);
        }
    }
}


