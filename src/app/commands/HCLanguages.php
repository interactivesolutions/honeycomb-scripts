<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;

class HCLanguages extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:languages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enables app()->getLocale() for content';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle ()
    {
        \interactivesolutions\honeycomblanguages\app\models\HCLanguages::where('iso_639_1', app()->getLocale())->update(['content' => 1, 'front_end' => 1, 'back_end' => 1]);
    }
}
