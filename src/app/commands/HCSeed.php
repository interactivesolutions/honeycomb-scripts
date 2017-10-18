<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands;

use File;
use InteractiveSolutions\HoneycombCore\Console\HCCommand;
use Nette\Reflection\AnnotationsParser;

/**
 * Class HCSeed
 * @package InteractiveSolutions\HoneycombScripts\app\commands
 */
class HCSeed extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:seed {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds honeycomb packages';

    /**
     * Execute the console command.
     * @throws \Exception
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Nette\Utils\RegexpException
     */
    public function handle()
    {
        $seeders = [];
        $path = $this->argument('path');
        $files = [];

        if ($path) {
            $files[] = base_path($path . 'src/database/seeds/HoneyCombDatabaseSeeder.php');
            $files[] = base_path($path . 'src/Database/Seeds/HoneyCombDatabaseSeeder.php');
        } else {
            $files = $this->getSeederFiles();
        }

        foreach ($files as $filePath) {
            $seeders = array_merge($seeders, array_keys(AnnotationsParser::parsePhp(file_get_contents($filePath))));
        }

        foreach ($seeders as $class) {
            if (class_exists($class)) {
                if (app()->environment() == 'production') {
                    $this->call('db:seed', ["--class" => $class, '--force' => true]);
                } else {
                    $this->call('db:seed', ["--class" => $class]);
                }
            }
        }

        if (app()->environment() == 'production') {
            $this->call('db:seed', ['--force' => true]);
        } else {
            $this->call('db:seed');
        }

    }

    /**
     * Scan folders for honeycomb seeder files
     *
     * @return array
     */
    protected function getSeederFiles()
    {
        return array_merge(
            File::glob(__DIR__ . '/../../../../../*/*/*/database/seeds/HoneyCombDatabaseSeeder.php'),
            File::glob(__DIR__ . '/../../../../../*/*/*/Database/Seeds/HoneyCombDatabaseSeeder.php')
        );
    }
}
