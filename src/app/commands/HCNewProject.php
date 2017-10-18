<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands;

use Carbon\Carbon;
use Exception;
use File;
use InteractiveSolutions\HoneycombCore\Console\HCCommand;

/**
 * Class HCNewProject
 * @package InteractiveSolutions\HoneycombScripts\app\commands
 */
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
     * Creating backup directory name
     *
     * @var string
     */
    protected $backupDirectory;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->backupDirectory = '_bak_' . $this->stringWithUnderscore(Carbon::now()->toDateTimeString());
        $this->removeDefaultStructure();
    }

    /**
     *
     */
    const CONFIG = __DIR__ . '/templates/project/config.json';

    /**
     * Removing default structure of application
     */
    private function removeDefaultStructure()
    {
        $confirm = $this->confirm('Are you sure? It will delete some directories with all files in them.)');

        if ($confirm) {
            try {

                $this->makeBackup();

                // deleting files and folders
                if (!file_exists(HCNewProject::CONFIG)) {
                    $this->abort('Missing project configuration file for laravel version');
                }

                $json = validateJSONFromPath(HCNewProject::CONFIG);

                foreach ($json['remove_folders'] as $location) {
                    $this->deleteDirectory($location, true);
                }

                foreach ($json['remove_files'] as $location) {
                    $this->info('Deleting file: ' . $location);
                    File::delete($location);
                }

                foreach ($json['create_folders'] as $location) {
                    $this->info('Creating folder: ' . $location);
                    $this->createDirectory($location);
                }

                $this->createFileFromTemplate([
                    "destination" => 'app/' . HCNewService::CONFIG_PATH,
                    "templateDestination" => __DIR__ . '/templates/shared/hc.config.hctpl',
                    "content" => [
                        "serviceProviderNameSpace" => "app",
                    ],
                ]);

                foreach ($json['create_files'] as $source => $destinations) {
                    if (!is_array($destinations)) {
                        $this->createProjectFile($source, $destinations);
                    } else {
                        foreach ($destinations as $destination) {
                            $this->createProjectFile($source, $destination);
                        }
                    }
                }

                foreach ($json['copy_folders'] as $source => $destinations) {
                    if (!is_array($destinations)) {
                        File::copyDirectory(__DIR__ . "/templates/$source", base_path($destinations));
                    } else {
                        foreach ($destinations as $destination) {
                            File::copyDirectory(__DIR__ . "/templates/$source", base_path($destination));
                        }
                    }
                }


                replaceTextInFile('composer.json', ['"App\\\\"' => '"app\\\\"']);
                replaceTextInFile('config/auth.php',
                    ['=> App\User::class' => '=> InteractiveSolutions\HoneycombAcl\Models\HCUsers::class']);
                replaceTextInFile('config/auth.php', ['password_resets' => 'hc_users_password_resets']);
                replaceTextInFile('config/database.php', ['utf8\'' => 'utf8mb4\'', 'utf8_' => 'utf8mb4_']);

            } catch (Exception $e) {
                $this->comment('Error occurred!');
                $this->error('Error code: ' . $e->getCode());
                $this->error('Error message: ' . $e->getMessage());
                $this->info('');
                $this->info('Rolling back configuration.');
                $this->restore();
                $this->abort('');
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
        try {
            $this->createFileFromTemplate([
                "destination" => $destination,
                "templateDestination" => __DIR__ . '/templates/project/' . $source,
            ]);
        } catch (Exception $e) {
        }
    }

    /**
     * Making backup for app and bootstrap folders
     */
    private function makeBackup()
    {
        $this->createDirectory($this->backupDirectory);
        $this->createDirectory($this->backupDirectory . '/config');

        File::copyDirectory('app', $this->backupDirectory . '/app');
        File::copyDirectory('bootstrap', $this->backupDirectory . '/bootstrap');
        File::copyDirectory('routes', $this->backupDirectory . '/routes');

        File::copy('composer.json', $this->backupDirectory . '/composer.json');
        File::copy('config/auth.php', $this->backupDirectory . '/config/auth.php');
        File::copy('config/database.php', $this->backupDirectory . '/config/database.php');
    }

    /**
     * Restoring on fail from backup directory
     * @todo restore default css and js files
     * @todo remove favicon files
     */
    private function restore()
    {
        $this->deleteDirectory('app');
        $this->deleteDirectory('bootstrap');
        $this->deleteDirectory('routes');

        $this->info('Copying back App');
        File::copyDirectory($this->backupDirectory . '/app', 'app');
        $this->info('Copying back bootstrap');
        File::copyDirectory($this->backupDirectory . '/bootstrap', 'bootstrap');
        $this->info('Copying back routes');
        File::copyDirectory($this->backupDirectory . '/routes', 'routes');

        File::copy($this->backupDirectory . '/composer.json', 'composer.json');
        File::copy($this->backupDirectory . '/config/auth.php', 'config/auth.php');
        File::copy($this->backupDirectory . '/config/database.php', 'config/database.php');

        $this->info('Backup successful');
    }
}
