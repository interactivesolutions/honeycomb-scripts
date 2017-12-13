<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands;

use File;
use InteractiveSolutions\HoneycombCore\Console\HCCommand;

/**
 * Class HCNewPackage
 * @package InteractiveSolutions\HoneycombScripts\app\commands
 */
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
    public function handle()
    {
        $configurationPath = __DIR__ . '/templates/config/package.json';

        if (!file_exists($configurationPath)) {
            $this->abort('Missing package configuration file');
        }

        // get create folder list
        $json = json_decode(file_get_contents($configurationPath), true);

        $this->createDirectory('packages');

        $directoryList = [];

        // get packages list without /src/app/honeycomb/config.json file
        foreach (File::directories('packages') as $directory) {
            $vendorPackages = File::directories($directory);

            foreach ($vendorPackages as $vendorPackage) {
                if (!file_exists($vendorPackage . '/src/app/honeycomb/config.json')) {
                    array_push($directoryList, $vendorPackage);
                }
            }
        }

        if ($directoryList == null) {
            $this->abort('You must create your package directory first. .i.e.: interactivesolutions/honeycomb-newpackage');
        }

        $packageDirectory = $this->choice('Please select package directory', $directoryList);
        $packageOfficialName = str_replace('packages/', '', $packageDirectory);
        $nameSpace = $this->getNameSpace($packageDirectory);

        $composerNameSpace = str_replace(['\\'], '\\\\', $nameSpace);
        $composerNameSpace = $composerNameSpace . '\\\\';

        $packageName = $this->ask('Please enter package name');

        foreach ($json['create_folders'] as $location) {
            $this->info('Creating folder: ' . $packageDirectory . '/' . $location);
            $this->createDirectory($packageDirectory . '/' . $location);
        }

        // TODO change from app
        $this->createFileFromTemplate([
            "destination" => $packageDirectory . '/src/app/honeycomb/routes.php',
            "templateDestination" => __DIR__ . '/templates/shared/empty.hctpl',
        ]);

        $this->createFileFromTemplate([
            "destination" => $packageDirectory . '/src/.gitignore',
            "templateDestination" => __DIR__ . '/templates/package/gitignore.hctpl',
        ]);

        $this->createFileFromTemplate([
            "destination" => $packageDirectory . '/src/app/honeycomb/config.json',
            "templateDestination" => __DIR__ . '/templates/shared/hc.config.hctpl',
            "content" => [
                "serviceProviderNameSpace" => $packageName,
            ],
        ]);

        $this->createFileFromTemplate([
            "destination" => $packageDirectory . '/composer.json',
            "templateDestination" => __DIR__ . '/templates/package/composer.hctpl',
            "content" => [
                "packageOfficialName" => $packageOfficialName,
                "packagePath" => $composerNameSpace,
            ],
        ]);

        $this->createFileFromTemplate([
            "destination" => $packageDirectory . '/src/Providers/' . $packageName . 'ServiceProvider.php',
            "templateDestination" => __DIR__ . '/templates/package/service.provider.hctpl',
            "content" => [
                "packageName" => $packageName . 'ServiceProvider',
                "nameSpace" => $nameSpace . '\Providers',
                "nameSpaceGeneral" => $nameSpace . '\Http\Controllers',
                "serviceProviderNameSpace" => $packageName,
            ],
        ]);

        $this->createFileFromTemplate([
            "destination" => $packageDirectory . '/src/database/seeds/HoneyCombDatabaseSeeder.php',
            "templateDestination" => __DIR__ . '/templates/package/database.seeder.hctpl',
            "content" => [
                "nameSpace" => $nameSpace,
                "className" => "HoneyComb",
            ],
        ]);

        $this->comment('');
        $this->comment('********************************************************');

        if (app()->environment() == 'local') {
            $composer = json_decode(file_get_contents('composer.json'));

            if (!isset($composer->autoload->{'psr-4'}->{$composerNameSpace})) {
                $composer->autoload->{'psr-4'}->{str_replace('\\\\', '\\',
                    $composerNameSpace)} = $packageDirectory . '/src';
            }

            file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));
        }

        $this->comment('Please add to config/app.php under "providers":');
        $this->info($nameSpace . '\Providers\\' . $packageName . 'ServiceProvider::class');

        $this->comment('********************************************************');
    }

    /**
     * @param $packageDirectory
     * @return mixed|string
     */
    private function getNameSpace($packageDirectory)
    {
        $nameSpace = str_replace('packages/', '', $packageDirectory);

        if (str_contains($nameSpace, 'interactivesolutions/honeycomb')) {
            $nameSpace = str_replace('interactivesolutions/honeycomb', '', $nameSpace);
            $nameSpace = str_replace('-', '', ucwords($nameSpace, '-'));
            $nameSpace = 'InteractiveSolutions\Honeycomb' . $nameSpace;
        } else {
            if (str_contains($nameSpace, 'interactivesolutions/')) {
                $nameSpace = str_replace('interactivesolutions/', '', $nameSpace);
                $nameSpace = str_replace('-', '', ucwords($nameSpace, '-'));
                $nameSpace = 'InteractiveSolutions\\' . $nameSpace;
            }
        }

        $nameSpace = str_replace('/', '\\', $nameSpace);

        return $nameSpace;
    }
}