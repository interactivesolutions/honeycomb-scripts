<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use File;
use interactivesolutions\honeycombcore\commands\HCCommand;
use League\Flysystem\Exception;

class HCNewProject extends HCCommand
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
     */
    private function removeDefaultStructure ()
    {
        $confirm = $this->confirm ('Are you sure? It will delete some directories with all files in them.)');

        if ($confirm) {
            try {
                // deleting files and folders

                if (!file_exists (HCNewProject::CONFIG))
                    $this->abort ('Missing project configuration file for laravel version');

                $json = validateJSONFromPath (HCNewProject::CONFIG);

                foreach ($json['remove_folders'] as $location)
                    $this->deleteDirectory ($location, true);

                foreach ($json['remove_files'] as $location) {
                    $this->info ('Deleting file: ' . $location);
                    File::delete ($location);
                }

                foreach ($json['create_folders'] as $location) {
                    $this->info ('Creating folder: ' . $location);
                    $this->createDirectory ($location);
                }

                $this->createFileFromTemplate ([
                    "destination"         => 'app/' . HCNewService::CONFIG_PATH,
                    "templateDestination" => __DIR__ . '/templates/project/config.hctpl',
                    "content"             => [
                        "serviceProviderNameSpace" => "app",
                    ],
                ]);

                foreach ($json['create_files'] as $source => $destinations) {

                    if (!is_array($destinations))
                        $this->createProjectFile($source, $destinations);
                    else
                        foreach ($destinations as $destination)
                            $this->createProjectFile($source, $destination);
                }

                replaceTextInFile('composer.json', ['"App\\\\"' => '"app\\\\"']);
                replaceTextInFile('config/auth.php', ['=> App\User::class' => '=> interactivesolutions\honeycombacl\app\models\HCUsers::class']);
                replaceTextInFile('config/database.php', ['utf8\'' => 'utf8mb4\'', 'utf8_' => 'utf8mb4_']);

                $this->comment('Please run composer dump');

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

    /**
     * Creating project file
     *
     * @param string $source
     * @param string $destination
     */
    private function createProjectFile(string $source, string $destination)
    {
        $this->createFileFromTemplate ([
            "destination"         => $destination,
            "templateDestination" => __DIR__ . '/templates/project/' . $source
        ]);
    }
}
