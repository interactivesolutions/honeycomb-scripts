<?php

namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;
use Nette\Reflection\AnnotationsParser;

class SeedHC extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:hc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds honeycomb packages';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        $seeders = [];

        foreach ($this->getSeederFiles() as $filePath)
            $seeders = array_merge($seeders, array_keys(AnnotationsParser::parsePhp(file_get_contents($filePath))));

        foreach ($seeders as $class)
            $this->call('db:seed',["--class" => $class]);

    }

    /**
     * Scan folders for honeycomb seeder files
     *
     * @return array
     */
    protected function getSeederFiles ()
    {
        return array_merge ($this->file->glob (__DIR__ . '/../../../../*/*/*/database/seeds/DatabaseSeeder.php'));
    }
}
