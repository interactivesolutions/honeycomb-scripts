<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands\service;

use InteractiveSolutions\HoneycombCore\Console\HCCommand;
use stdClass;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class HCBaseServiceCreation
 * @package InteractiveSolutions\HoneycombScripts\app\commands\service
 */
class HCBaseServiceCreation extends HCCommand
{
    /**
     * @var array
     */
    private $autoFill = ['count', 'created_at', 'updated_at', 'deleted_at', 'language_code', 'record_id'];
    /**
     * @var array
     */
    private $translationsFill = ['count', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * HCBaseServiceCreation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->output = new ConsoleOutput();
    }

    /**
     * @return array
     */
    protected function getAutoFill(): array
    {
        return $this->autoFill;
    }

    /**
     * @return array
     */
    protected function getTranslationsAutoFill(): array
    {
        return $this->translationsFill;
    }

    /**
     * @param stdClass $data
     * @return stdClass
     */
    public function optimize(stdClass $data)
    {
        return $data;
    }

    /**
     * @param stdClass $data
     * @return string
     */
    public function generate(stdClass $data)
    {
        return '';
    }
}
