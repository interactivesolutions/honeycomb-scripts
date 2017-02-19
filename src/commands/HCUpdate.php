<?php

namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;
use Nette\Reflection\AnnotationsParser;

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
        $this->call('hc:seed');
        $this->call('hc:permissions');
        $this->call('hc:routes');
        $this->call('hc:admin-menu');
    }
}
