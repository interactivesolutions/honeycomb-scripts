<?php

namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;

class GenerateRoutes extends HCCommand
{
    const ROUTES_PATH = 'app/HoneyComb/routes.php';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:routes';

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
        $files = $this->file->allFiles('app/routes');
        $finalContent = '<?php';

        foreach ($files as $file)
        {
            $finalContent .= "\r\n";
            $finalContent .= '//' . (string)$file;
            $finalContent .= str_replace('<?php', '', $this->file->get((string)$file)) . "\r\n";
        }

        $this->file->put(GenerateRoutes::ROUTES_PATH, $finalContent);

        $this->comment(GenerateRoutes::ROUTES_PATH . ' file generated');
    }
}
