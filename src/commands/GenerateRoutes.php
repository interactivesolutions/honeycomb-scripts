<?php

namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;

class GenerateRoutes extends HCCommand
{
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

        $this->file->put('app/routes.honeycomb.php', $finalContent);

        $this->comment('app/routes.honeycomb.php file generated');
    }
}
