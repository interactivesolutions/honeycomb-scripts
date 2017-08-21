<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use File;
use interactivesolutions\honeycombcore\commands\HCCommand;

class HCUpdate extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all of the honey comb environment';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        $this->call('vendor:publish', ['--force' => true, '--tag' => "public"]);
        $this->call('vendor:publish', ['--tag' => "migrations"]);
        $this->call('vendor:publish', ['--tag' => "config"]);
        $this->call('migrate');
        $this->call('hc:seed');
        $this->call('hc:routes');
        $this->call('hc:forms');
        $this->call('hc:languages');
        $this->call('hc:permissions');
        $this->call('hc:admin-menu');

       File::delete ('bootstrap/cache/config.php');
       File::delete ('bootstrap/cache/services.php');

        if (app()->environment() == 'production')
            $this->call('config:cache');
    }
}
