<?php

namespace interactivesolutions\honeycombscripts\app\commands\service;

use interactivesolutions\honeycombcore\commands\HCCommand;
use stdClass;
use Symfony\Component\Console\Output\ConsoleOutput;

class HCBaseServiceCreation extends HCCommand
{
    private $autoFill = ['count', 'created_at', 'updated_at', 'deleted_at'];

    public function __construct()
    {
        parent::__construct ();
        $this->output = new ConsoleOutput();
    }

    protected function getAutoFill()
    {
        return $this->autoFill;
    }

    public function optimize(stdClass $data)
    {
        return $data;
    }

    public function generate(stdClass $data)
    {
        return '';
    }
}
