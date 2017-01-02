<?php

namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;

class PrepareProject extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:hcproject';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleting default laravel project files and creating hc structure';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->removeDefaultStructure();
    }

    /**
     * Removing default structure of application
     */
    private function removeDefaultStructure()
    {
        $confirm = $this->confirm('Are you sure? It will delete some directories with all files in them.)');

        if ($confirm)
        {
            $this->deleteDirectory('app/Http/Controllers', true);
            $this->deleteDirectory('app/Http/Console', true);
            $this->deleteDirectory('app/routes', true);
            $this->deleteDirectory('app/HoneyComb', true);
            $this->deleteDirectory('routes', true);

            $this->createDirectory('app/Http/Controllers');
            $this->createDirectory('app/Http/Console');
            $this->createDirectory('app/routes');
            $this->createDirectory('app/HoneyComb');

            $this->createFileFromTemplate([
                "destination" => 'app/Http/Console/Kernel.php',
                "templateDestination" => __DIR__ . '/templates/app.console.kernel.template.txt',
            ]);

            $this->createFileFromTemplate([
                "destination" => 'app/HoneyComb/config.json',
                "templateDestination" => __DIR__ . '/templates/config.template.txt',
            ]);
        }
    }
}
