<?php

namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;

class MakeHCProject extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:hcproject';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleting default laravel project files and creating hc structure';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->removeDefaultStructure();
    }

    /**
     * Removing default structure of application
     */
    private function removeDefaultStructure()
    {
        $confirm = $this->confirm('Are you sure? It will delete some directories with all files in them.)');

        if ($confirm)
        {
            // deleting files and folders
            $this->deleteDirectory('app/http/controllers', true);
            $this->deleteDirectory('app/http/console', true);
            $this->deleteDirectory('app/honeycomb', true);
            $this->deleteDirectory('app/models', true);
            $this->deleteDirectory('app/routes', true);
            $this->deleteDirectory('routes', true);

            $this->file->delete('app/Providers/RouteServiceProvider.php');

            // creating files and folders
            $this->createDirectory('app/http/controllers');
            $this->createDirectory('app/http/console');
            $this->createDirectory('app/models');
            $this->createDirectory('app/routes');
            $this->createDirectory('app/honeycomb');

            $this->createFileFromTemplate([
                "destination"         => 'app/http/console/Kernel.php',
                "templateDestination" => __DIR__ . '/templates/app.console.kernel.template.txt',
            ]);

            $this->createFileFromTemplate([
                "destination"         => 'app/' . MakeHCService::CONFIG_PATH,
                "templateDestination" => __DIR__ . '/templates/config.template.txt',
                "content"             => [
                    "serviceProviderNameSpace" => "",
                ],
            ]);

            $this->createFileFromTemplate([
                "destination"         => "app/providers/RouteServiceProvider.php",
                "templateDestination" => __DIR__ . '/templates/route.serviceprovider.template.txt',
                "content"             =>
                    [
                        "routesBasePath" => GenerateRoutes::ROUTES_PATH,
                    ],
            ]);

            $this->file->put(GenerateRoutes::ROUTES_PATH, '');
        }
    }
}
