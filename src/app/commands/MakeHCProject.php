<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;
use League\Flysystem\Exception;

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
    public function handle ()
    {
        $this->removeDefaultStructure ();
    }

    /**
     * Removing default structure of application
     *
     * @return this
     */
    private function removeDefaultStructure ()
    {
        $confirm = $this->confirm ('Are you sure? It will delete some directories with all files in them.)');

        if ($confirm) {
            try {
                // deleting files and folders
                $version = substr (app ()::VERSION, 0, strrpos (app ()::VERSION, "."));
                if (!file_exists (__DIR__ . '/templates/config.' . $version . '/project.json'))
                    $this->abort ('Missing project configuration file for laravel version ' . $version);

                $json = json_decode ($this->file->get (__DIR__ . '/templates/config.' . $version . '/project.json'), true);

                foreach ($json['remove_folders'] as $location) {
                    $this->info ('Deleting folder: ' . $location);
                    $this->deleteDirectory ($location, true);
                }

                foreach ($json['remove_files'] as $location) {
                    $this->info ('Deleting file: ' . $location);
                    $this->file->delete ($location);
                }

                foreach ($json['create_folders'] as $location) {
                    $this->info ('Creating folder: ' . $location);
                    $this->createDirectory ($location);
                }

                $this->createFileFromTemplate ([
                    "destination"         => 'app/Console/Kernel.php',
                    "templateDestination" => __DIR__ . '/templates/app.console.kernel.hctpl',
                ]);

                $this->createFileFromTemplate ([
                    "destination"         => 'app/' . MakeHCService::CONFIG_PATH,
                    "templateDestination" => __DIR__ . '/templates/config.hctpl',
                    "content"             => [
                        "serviceProviderNameSpace" => "",
                    ],
                ]);

                $this->createFileFromTemplate ([
                    "destination"         => "app/providers/RouteServiceProvider.php",
                    "templateDestination" => __DIR__ . '/templates/route.serviceprovider.hctpl',
                    "content"             => [
                        "routesBasePath" => HCRoutes::ROUTES_PATH,
                    ],
                ]);
                $this->createFileFromTemplate ([
                    "destination"         => 'database/seeds/DatabaseSeeder.php',
                    "templateDestination" => __DIR__ . '/templates/l.database.seeder.hctpl',
                ]);

                $this->createFileFromTemplate ([
                    "destination"         => "_automate/example.json",
                    "templateDestination" => __DIR__ . '/templates/automate.config.hctpl',
                ]);

                $this->file->put (HCRoutes::ROUTES_PATH, '');
            } catch (Exception $e) {
                $this->info ('Error occurred!');
                $this->info ('Error code: ' . $e->getCode ());
                $this->info ('Error message: ' . $e->getMessage ());
                $this->info ('');
                $this->info ('Rolling back configuration.');
                $this->abort ('');
            }
        }
    }
}
