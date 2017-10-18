<?php

declare(strict_types = 1);

namespace interactivesolutions\honeycombscripts\app\commands;

use InteractiveSolutions\HoneycombCore\Console\HCCommand;


/**
 * Class HCLanguages
 * @package interactivesolutions\honeycombscripts\app\commands
 */
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
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        \interactivesolutions\honeycomblanguages\app\models\HCLanguages::where('iso_639_1',
            app()->getLocale())->update(['content' => 1, 'front_end' => 1, 'back_end' => 1]);
    }
}
