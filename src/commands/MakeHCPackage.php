<?php

namespace interactivesolutions\honeycombscripts\commands;

use Illuminate\Support\Facades\App;
use interactivesolutions\honeycombcore\commands\HCCommand;

class MakeHCPackage extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:hcpackage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating an empty HC package in projects packages/ directory';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        $version = substr (app ()::VERSION, 0, strrpos (app ()::VERSION, "."));

        if (!file_exists (__DIR__ . '/templates/config.' . $version . '/package.json'))
            $this->abort ('Missing package configuration file for laravel version ' . $version);

        $json = json_decode ($this->file->get (__DIR__ . '/templates/config.' . $version . '/package.json'), true);

        $this->createDirectory ('packages');

        $directoryList = [];

        foreach ($this->file->directories ('packages') as $directory)
            $directoryList = array_merge ($directoryList, $this->file->directories ($directory));

        $packageDirectory = $this->choice ('Please select package directory', $directoryList);
        $packageOfficialName = str_replace ('packages/', '', $packageDirectory);
        $nameSpace = $this->stringOnly (str_replace ('/', '\\', $packageOfficialName));
        $composerNameSpace = str_replace (['\\', '/'], '\\', $packageOfficialName . '\\');
        $composerNameSpace = str_replace ('-', '', $composerNameSpace);

        $packageName = $this->ask ('Please enter package name');

        foreach ($json['create_folders'] as $location) {
            $this->info ('Creating folder: ' . $packageDirectory . '/' . $location);
            $this->createDirectory ($packageDirectory . '/' . $location);
        }

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/app/http/helpers.php',
            "templateDestination" => __DIR__ . '/templates/shared/empty.hctpl',
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/app/honeycomb/routes.php',
            "templateDestination" => __DIR__ . '/templates/shared/empty.hctpl',
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/app/honeycomb/config.json',
            "templateDestination" => __DIR__ . '/templates/config.hctpl',
            "content"             => [
                "serviceProviderNameSpace" => $packageName
            ]
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/composer.json',
            "templateDestination" => __DIR__ . '/templates/composer.hctpl',
            "content"             => [
                "packageOfficialName" => $packageOfficialName,
                "packagePath"         => $composerNameSpace,
            ],
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/app/providers/' . $packageName . 'ServiceProvider.php',
            "templateDestination" => __DIR__ . '/templates/service.provider.hctpl',
            "content"             => [
                "packageName"      => $packageName,
                "nameSpace"        => $nameSpace . '\providers',
                "nameSpaceGeneral" => $nameSpace . '\http\controllers',
            ],
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/database/seeds/HoneyCombDatabaseSeeder.php',
            "templateDestination" => __DIR__ . '/templates/database.seeder.hctpl',
            "content"             => [
                "nameSpace" => $nameSpace,
                "className" => "HoneyComb"
            ],
        ]);

        $this->comment ('');
        $this->comment ('********************************************************');

        if (App::environment () == 'local') {
            $composer = json_decode ($this->file->get ('composer.json'));

            if (!isset($composer->autoload->{'psr-4'}->{$composerNameSpace}))
                $composer->autoload->{'psr-4'}->{$composerNameSpace} = $packageDirectory;

            $this->file->put ('composer.json', json_encode ($composer, JSON_PRETTY_PRINT));
        }

        $this->comment ('Please add to config/app.php under "providers":');
        $this->info ($nameSpace . '\providers\\' . $packageName . 'ServiceProvider::class');

        $this->comment ('********************************************************');


    }
}
