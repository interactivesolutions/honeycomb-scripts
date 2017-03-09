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
    protected $signature = 'hc:new-project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleting default laravel project files and creating honeycomb cms structure';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        $this->removeDefaultStructure ();
    }

    const CONFIG = __DIR__ . '/templates/project/config.json';

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

                if (!file_exists (MakeHCProject::CONFIG))
                    $this->abort ('Missing project configuration file for laravel version');

                $json = validateJSONFromPath (MakeHCProject::CONFIG);

                foreach ($json['remove_folders'] as $location)
                    $this->deleteDirectory ($location, true);

                foreach ($json['remove_files'] as $location) {
                    $this->info ('Deleting file: ' . $location);
                    $this->file->delete ($location);
                }

                foreach ($json['create_folders'] as $location) {
                    $this->info ('Creating folder: ' . $location);
                    $this->createDirectory ($location);
                }

                $this->createFileFromTemplate ([
                    "destination"         => 'app/' . MakeHCService::CONFIG_PATH,
                    "templateDestination" => __DIR__ . '/templates/config.hctpl',
                    "content"             => [
                        "serviceProviderNameSpace" => "app",
                    ],
                ]);

                foreach ($json['create_files'] as $source => $destination) {
                    $this->info ('Creating file: ' . $destination);
                    $this->createFileFromTemplate ([
                        "destination"         => $destination,
                        "templateDestination" => __DIR__ . '/templates/project/' . $source
                    ]);
                }

                $composer = validateJSONFromPath('composer.json');

                if (isset($composer['autoload']['psr-4']['App\\']))
                {
                    array_forget($composer['autoload']['psr-4'], 'App\\');
                    $composer['autoload']['psr-4'] = array_prepend($composer['autoload']['psr-4'], 'app', 'app\\');

                    file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));
                }

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
