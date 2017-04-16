<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use File;
use interactivesolutions\honeycombcore\commands\HCCommand;
use Nette\Reflection\AnnotationsParser;

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
     *
     * @return this
     */
    public function handle ()
    {
        $seeders = [];
        $path    = $this->argument ('path');
        $files   = [];

        if ($path)
            $files[] = base_path ($path . 'src/database/seeds/HoneyCombDatabaseSeeder.php');
        else
            $files = $this->getSeederFiles ();

        foreach ($files as $filePath)
            $seeders = array_merge ($seeders, array_keys (AnnotationsParser::parsePhp (file_get_contents ($filePath))));

        foreach ($seeders as $class)
            if (class_exists ($class))
                $this->call ('db:seed', ["--class" => $class]);

        $this->call ('db:seed');
    }

    /**
     * Scan folders for honeycomb seeder files
     *
     * @return array
     */
    protected function getSeederFiles ()
    {
        return array_merge (File::glob (__DIR__ . '/../../../../../*/*/*/database/seeds/HoneyCombDatabaseSeeder.php'));
    }
}
