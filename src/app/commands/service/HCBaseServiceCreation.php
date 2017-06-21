<?php

namespace interactivesolutions\honeycombscripts\app\commands\service;

use interactivesolutions\honeycombcore\commands\HCCommand;
use stdClass;
use Symfony\Component\Console\Output\ConsoleOutput;

class HCBaseServiceCreation extends HCCommand
{
    private $autoFill = ['count', 'created_at', 'updated_at', 'deleted_at', 'language_code', 'record_id'];
    private $translationsFill = ['count', 'created_at', 'updated_at', 'deleted_at'];

    public function __construct()
    {
        parent::__construct ();
        $this->output = new ConsoleOutput();
    }

    protected function getAutoFill()
    {
        return $this->autoFill;
    }

    protected function getTranslationsAutoFill()
    {
        return $this->translationsFill;
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
