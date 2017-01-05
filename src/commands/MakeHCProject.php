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
            $this->deleteDirectory('app/Http/Controllers', true);
            $this->deleteDirectory('app/Http/Console', true);
            $this->deleteDirectory('app/HoneyComb', true);
            $this->deleteDirectory('app/Models', true);
            $this->deleteDirectory('app/Routes', true);
            $this->deleteDirectory('routes', true);

            $this->file->delete('app/Providers/RouteServiceProvider.php');

            // creating files and folders
            $this->createDirectory('app/Http/Controllers');
            $this->createDirectory('app/Http/Console');
            $this->createDirectory('app/Models');
            $this->createDirectory('app/Routes');
            $this->createDirectory('app/HoneyComb');

            $this->createFileFromTemplate([
                "destination"         => 'app/Http/Console/Kernel.php',
                "templateDestination" => __DIR__ . '/templates/app.console.kernel.template.txt',
            ]);

            $this->createFileFromTemplate([
                "destination"         => MakeHCService::CONFIG_PATH,
                "templateDestination" => __DIR__ . '/templates/config.template.txt',
            ]);

            $this->createFileFromTemplate([
                "destination"         => "app/Providers/RouteServiceProvider.php",
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