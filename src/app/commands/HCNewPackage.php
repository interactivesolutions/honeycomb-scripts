<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use File;
use Illuminate\Support\Facades\App;
use interactivesolutions\honeycombcore\commands\HCCommand;

class HCNewPackage extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:new-package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating an empty HC package in project packages/ directory';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        $configurationPath = __DIR__ . '/templates/config/package.json';
        if (!file_exists ($configurationPath))
            $this->abort ('Missing package configuration file');

        $json = json_decode (file_get_contents ($configurationPath), true);

        $this->createDirectory ('packages');

        $directoryList = [];

        foreach (File::directories ('packages') as $directory)
            $directoryList = array_merge ($directoryList, File::directories ($directory));

        $packageDirectory = $this->choice ('Please select package directory', $directoryList);
        $packageOfficialName = str_replace ('packages/', '', $packageDirectory);
        $nameSpace = $this->stringOnly (str_replace ('/', '\\', $packageOfficialName));
        $composerNameSpace = str_replace (['\\', '/'], '\\\\', $packageOfficialName . '\\');
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
            "destination"         => $packageDirectory . '/src/.gitignore',
            "templateDestination" => __DIR__ . '/templates/package/gitignore.hctpl',
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/app/honeycomb/config.json',
            "templateDestination" => __DIR__ . '/templates/shared/hc.config.hctpl',
            "content"             => [
                "serviceProviderNameSpace" => $packageName
            ]
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/composer.json',
            "templateDestination" => __DIR__ . '/templates/package/composer.hctpl',
            "content"             => [
                "packageOfficialName" => $packageOfficialName,
                "packagePath"         => $composerNameSpace,
            ],
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/app/providers/' . $packageName . 'ServiceProvider.php',
            "templateDestination" => __DIR__ . '/templates/package/service.provider.hctpl',
            "content"             => [
                "packageName"              => $packageName . 'ServiceProvider',
                "nameSpace"                => $nameSpace . '\app\providers',
                "nameSpaceGeneral"         => $nameSpace . '\app\http\controllers',
                "serviceProviderNameSpace" => $packageName,
            ],
        ]);

        $this->createFileFromTemplate ([
            "destination"         => $packageDirectory . '/src/database/seeds/HoneyCombDatabaseSeeder.php',
            "templateDestination" => __DIR__ . '/templates/package/database.seeder.hctpl',
            "content"             => [
                "nameSpace" => $nameSpace,
                "className" => "HoneyComb"
            ],
        ]);

        $this->comment ('');
        $this->comment ('********************************************************');

        if (App::environment () == 'local') {
            $composer = json_decode (file_get_contents ('composer.json'));

            if (!isset($composer->autoload->{'psr-4'}->{$composerNameSpace}))
                $composer->autoload->{'psr-4'}->{str_replace ('\\\\', '\\', $composerNameSpace)} = $packageDirectory . '/src';

            file_put_contents ('composer.json', json_encode ($composer, JSON_PRETTY_PRINT));
        }

        $this->comment ('Please add to config/app.php under "providers":');
        $this->info ($nameSpace . '\app\providers\\' . $packageName . 'ServiceProvider::class');

        $this->comment ('********************************************************');


    }
}
