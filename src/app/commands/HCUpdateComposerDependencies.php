<?php
/**
 * Created by PhpStorm.
 * User: jevge
 * Date: 2017-04-16
 * Time: 07:29
 */

namespace interactivesolutions\honeycombscripts\app\commands;


use Illuminate\Filesystem\Filesystem;
use interactivesolutions\honeycombcore\commands\HCCommand;

class HCUpdateComposerDependencies extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:update-composer-dependencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'When in development of the packages, read the packages/ directory and update main composer.json file with dependencies';

    public function handle ()
    {
        if (app()->environment() != 'local')
            return;

        $list         = $this->getComposerFiles ();
        $mainComposer = validateJSONFromPath (base_path ('composer.json'));

        foreach ($list as $value) {
            $content = validateJSONFromPath ($value);

            $mainComposer = $this->updateMainComposer ($mainComposer, $content, 'require');
            $mainComposer = $this->updateMainComposer ($mainComposer, $content, 'require-dev');
        }

        file_put_contents(base_path ('composer.json'), json_encode($mainComposer, JSON_PRETTY_PRINT));
    }

    /**
     * Scan packages folder and retrieves list of composer.json files
     *
     * @return array
     */
    protected function getComposerFiles () : array
    {
        $file = new Filesystem();

        return $file->glob (base_path ('packages') . '/*/*/composer.json');
    }

    /**
     * Updating main composer file for dependencies
     *
     * @param array $mainComposer
     * @param array $content
     * @param string $composerKey
     * @return array
     */
    private function updateMainComposer (array $mainComposer, array $content, string $composerKey) : array
    {
        if (!isset($content[$composerKey]))
            return $mainComposer;

        $content = $content[$composerKey];

        foreach ($content as $key => $value)
            if (strpos ($key, 'interactivesolutions/honeycomb') === false && !isset($mainComposer[$composerKey][$key]))
                $mainComposer[$composerKey][$key] = $value;

        return $mainComposer;
    }
}