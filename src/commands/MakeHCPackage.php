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
    public function handle()
    {
        $this->createDirectory('packages');

        $this->info('An active repository required!');

        $directoryList = [];

        foreach ($this->file->directories('packages') as $directory)
            $directoryList = array_merge($directoryList, $this->file->directories($directory));

        $packageDirectory = $this->choice('Please select package directory', $directoryList);
        $packageOfficialName = str_replace('packages/', '', $packageDirectory);
        $nameSpace = $this->stringOnly(str_replace('/', '\\', $packageOfficialName));
        $composerNameSpace = str_replace(['\\', '/'], '\\', $packageOfficialName . '\\');
        $composerNameSpace = str_replace('-', '', $composerNameSpace);

        $packageName = $this->ask('Please enter package name');

        $this->createDirectory($packageDirectory . '/src');
        $this->createDirectory($packageDirectory . '/src/app/');
        $this->createDirectory($packageDirectory . '/src/app/console');
        $this->createDirectory($packageDirectory . '/src/app/console/commands');
        $this->createDirectory($packageDirectory . '/src/app/exceptions');
        $this->createDirectory($packageDirectory . '/src/app/honeycomb');
        $this->createDirectory($packageDirectory . '/src/app/http');
        $this->createDirectory($packageDirectory . '/src/app/http/controllers');
        $this->createDirectory($packageDirectory . '/src/app/http/middleware');
        $this->createDirectory($packageDirectory . '/src/app/models');
        $this->createDirectory($packageDirectory . '/src/app/providers');
        $this->createDirectory($packageDirectory . '/src/app/routes');

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
            "destination"         => $packageDirectory . '/src/app/http/helpers.php',
            "templateDestination" => __DIR__ . '/templates/shared/empty.template.txt',
        ]);

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/src/app/honeycomb/routes.php',
            "templateDestination" => __DIR__ . '/templates/shared/empty.template.txt',
        ]);

        $this->createFileFromTemplate([
            "destination"         => $packageDirectory . '/src/app/honeycomb/config.json',
            "templateDestination" => __DIR__ . '/templates/config.template.txt',
            "content"             => [
                "serviceProviderNameSpace" => $packageName
            ]
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
            "destination"         => $packageDirectory . '/src/app/providers/' . $packageName . 'ServiceProvider.php',
            "templateDestination" => __DIR__ . '/templates/service.provider.template.txt',
            "content"             => [
                "packageName"      => $packageName,
                "nameSpace"        => $nameSpace . '\providers',
                "nameSpaceGeneral" => $nameSpace . '\http\controllers',
            ],
        ]);

        $this->comment('');
        $this->comment('********************************************************');

        if (App::environment() == 'local')
        {
            $composer = json_decode($this->file->get('composer.json'));

            if (!isset($composer->autoload->{'psr-4'}->{$composerNameSpace}))
                $composer->autoload->{'psr-4'}->{$composerNameSpace} = $packageDirectory;

            $this->file->put('composer.json', json_encode($composer, JSON_PRETTY_PRINT));
        }

        $this->comment('Please add to config/app.php under "providers":');
        $this->info($nameSpace . '\providers\\' . $packageName . 'ServiceProvider::class');

        $this->comment('********************************************************');


    }
}
