<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands;

use File;
use InteractiveSolutions\HoneycombCore\Console\HCCommand;
use Symfony\Component\Finder\Finder;

/**
 * Class HCRoutes
 * @package InteractiveSolutions\HoneycombScripts\app\commands
 */
class HCRoutes extends HCCommand
{
    /**
     *
     */
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
    public function handle()
    {
        $rootDirectory = $this->argument('directory') ?? '';

        if ($rootDirectory == '') {

            $files = $this->getConfigFiles();

            foreach ($files as $file) {
                // TODO change all package route folder location to src/Routes, than fix this
                $packageRootDirectory = realpath(implode('/', array_slice(explode('/', $file), 0, -3))) . '/';
                $this->generateRoutes($packageRootDirectory);
                $this->generateRoutes($packageRootDirectory, 'Routes');
            }
        } else {
            $this->generateRoutes($rootDirectory);
        }
    }

    /**
     * Generating final routes file for package
     *
     * @param $directory
     * @param string $appPath
     */
    private function generateRoutes($directory, $appPath = 'app/routes/')
    {
        $dirPath = $directory . $appPath;

        $laravelRouteFolder = base_path() . '/routes';


        if (!file_exists($dirPath) || $laravelRouteFolder == strtolower($dirPath)) {
            $this->error($dirPath);

            return;
        }

        // get all files recursively

        /** @var \Iterator $iterator */
        $iterator = Finder::create()
            ->files()
            ->ignoreDotFiles(true)
            ->sortByName()
            ->in($dirPath);

        // iterate to array
        $files = iterator_to_array($iterator, true);

        $finalContent = '<?php' . "\r\n";

        foreach ($files as $file => $content) {
            $finalContent .= "\r\n";
            $finalContent .= '//' . implode('/', array_slice(explode('/', $file), -6)) . "\r\n";

            $routeFileContent = file_get_contents((string)$file);

            $routeFileContent = str_replace('<?php', '', $routeFileContent) . "\r\n";
            $finalContent .= str_replace('declare(strict_types = 1);', '', $routeFileContent) . "\r\n";
        }

        file_put_contents($directory . HCRoutes::ROUTES_PATH, $finalContent);

        $this->comment($directory . HCRoutes::ROUTES_PATH . ' file generated');
    }
}