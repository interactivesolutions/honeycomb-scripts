<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use File;
use interactivesolutions\honeycombcore\commands\HCCommand;

class HCRoutes extends HCCommand
{
    const ROUTES_PATH = 'app/honeycomb/routes.php';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:routes {directory?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generating routes file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        if ($this->argument ('directory'))
            $rootDirectory = $this->argument ('directory');
        else
            $rootDirectory = '';

        if ($rootDirectory == '') {

            if (app ()->environment () == 'local') {

                $files = $this->getConfigFiles ();

                foreach ($files as $file)
                    if (strpos ($file, '/vendor/') === false)
                        $this->generateRoutes (realpath (implode ('/', array_slice (explode ('/', $file), 0, -3))) . '/');
            }
        } else
            $this->generateRoutes ($rootDirectory);
    }

    /**
     * Generating final routes file for package
     *
     * @param $directory
     */
    private function generateRoutes ($directory)
    {
        if (!file_exists ($directory . 'app/routes/'))
            return;

        $files = \File::allFiles ($directory . 'app/routes');

        $finalContent = '<?php' . "\r\n";

        foreach ($files as $file) {

            $finalContent .= "\r\n";
            $finalContent .= '//' . implode ('/', array_slice (explode ('/', $file), -6)) . "\r\n";
            $finalContent .= str_replace ('<?php', '', file_get_contents ((string)$file)) . "\r\n";
        }

        file_put_contents ($directory . HCRoutes::ROUTES_PATH, $finalContent);

        $this->comment ($directory . HCRoutes::ROUTES_PATH . ' file generated');
    }
}
