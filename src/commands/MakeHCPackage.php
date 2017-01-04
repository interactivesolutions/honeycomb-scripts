<?php

namespace interactivesolutions\honeycombscripts\commands;

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
    protected $description = 'Creating an empty HC package in projects packages/ diretory';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createDirectory('packages');

        $this->info('An active repository required!');

        $directoryList = [];

        foreach ($this->file->directories('packages') as $directory)
        {
            $directoryList = array_merge($directoryList, $this->file->directories($directory));
        }

        $packageDirectory = $this->choice('Please select package directory', $directoryList);
        $packageOfficialName = str_replace('packages/', '', $packageDirectory);
        $nameSpace = $this->stringOnly(str_replace('/', '\\', $packageOfficialName));
        $composerNameSpace = str_replace('\\', '\\\\', $packageOfficialName . '\\');

        $packageName = $this->ask('Please enter package name');

        $this->createDirectory($packageDirectory . '/src');
        $this->createDirectory($packageDirectory . '/src/app/');
        $this->createDirectory($packageDirectory . '/src/app/Console');
        $this->createDirectory($packageDirectory . '/src/app/Exceptions');
        $this->createDirectory($packageDirectory . '/src/app/HoneyComb');
        $this->createDirectory($packageDirectory . '/src/app/Http');
        $this->createDirectory($packageDirectory . '/src/app/Http/Console');
        $this->createDirectory($packageDirectory . '/src/app/Http/Controllers');
        $this->createDirectory($packageDirectory . '/src/app/Http/Middleware');
        $this->createDirectory($packageDirectory . '/src/app/Models');
        $this->createDirectory($packageDirectory . '/src/app/Providers');
        $this->createDirectory($packageDirectory . '/src/app/Routes');

        $this->createDirectory($packageDirectory . '/src/database');
        $this->createDirectory($packageDirectory . '/src/database/migrations');
        $this->createDirectory($packageDirectory . '/src/database/seeds');
        $this->createDirectory($packageDirectory . '/src/public');
        $this->createDirectory($packageDirectory . '/src/public/css');
        $this->createDirectory($packageDirectory . '/src/public/js');

        $this->createDirectory($packageDirectory . '/src/resources');
        $this->createDirectory($packageDirectory . '/src/resources/lang');
        $this->createDirectory($packageDirectory . '/src/resources/lang/en');
        $this->createDirectory($packageDirectory . '/src/resources/views');
        $this->createDirectory($packageDirectory . '/src/tests');

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/src/app/Http/helpers.php',
            "templateDestination" => __DIR__ . '/templates/empty.template.txt',
        ]);

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/src/app/HoneyComb/routes.php',
            "templateDestination" => __DIR__ . '/templates/empty.template.txt',
        ]);

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/src/app/HoneyComb/config.json',
            "templateDestination" => __DIR__ . '/templates/config.template.txt',
        ]);

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/composer.json',
            "templateDestination" => __DIR__ . '/templates/composer.template.txt',
            "content"             => [
                "packageOfficialName" => $packageOfficialName,
                "packagePath"         => $composerNameSpace,
            ],
        ]);

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/src/app/Providers/' . $packageName . 'ServiceProvider.php',
            "templateDestination" => __DIR__ . '/templates/service.provider.template.txt',
            "content"             => [
                "packageName"      => $packageName,
                "nameSpace"        => $nameSpace . '\Providers',
                "nameSpaceGeneral" => $nameSpace,
            ],
        ]);
    }
}
