<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands\service;

use InteractiveSolutions\HoneycombScripts\app\commands\HCNewService;
use stdClass;

/**
 * Class HCServiceTranslations
 * @package InteractiveSolutions\HoneycombScripts\app\commands\service
 */
class HCServiceTranslations extends HCBaseServiceCreation
{
    /**
     * HCServiceTranslations constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Optimizing Translations data
     *
     * @param stdClass $data
     * @return stdClass
     */
    public function optimize(stdClass $data)
    {
        $data->translationFilePrefix = $this->stringWithUnderscore(strtolower($data->serviceURL));

        $data->translationFilePrefix = $this->stringWithUnderscore($data->translationFilePrefix);
        $data->translationsLocation = $this->getTranslationPrefix($data) . $data->translationFilePrefix;

        return $data;
    }

    /**
     * Getting translation prefix
     *
     * @param $data
     * @return string
     */
    private function getTranslationPrefix(stdClass $data)
    {
        if ($data->rootDirectory == './') {
            return '';
        } else {
            return $this->getServiceProviderNameSpace($data) . "::";
        }

    }

    /**
     * Getting service provider namespace
     *
     * @param $item
     * @return mixed
     */
    private function getServiceProviderNameSpace(stdClass $item)
    {
        return json_decode(file_get_contents($item->rootDirectory . 'app/' . HCNewService::CONFIG_PATH))->general->serviceProviderNameSpace;
    }

    /**
     * Creating translation file
     *
     * @param $service
     * @return string
     */
    public function generate(stdClass $service)
    {
        $this->createFileFromTemplate([
            "destination" => $service->rootDirectory . 'resources/lang/en/' . $service->translationFilePrefix . '.php',
            "templateDestination" => __DIR__ . '/../templates/service/translations.hctpl',
            "content" => [
                "translations" => $this->gatherTranslations($service),
            ],
        ]);

        return $service->rootDirectory . 'resources/lang/en/' . $service->translationFilePrefix . '.php';
    }

    /**
     * Gathering available translations
     *
     * @param $service
     * @return string
     */
    private function gatherTranslations(stdClass $service)
    {
        $output = '';
        $tpl = file_get_contents(__DIR__ . '/../templates/shared/array.element.hctpl');

        $line = str_replace('{key}', 'page_title', $tpl);
        $line = str_replace('{value}', $service->serviceName, $line);
        $output .= $line;

        $output .= $this->gatherTranslationsFromModel($service->mainModel, $tpl,
            array_merge($this->getAutoFill(), ['id']));
        if (isset($service->mainModel->multiLanguage)) {
            $output .= $this->gatherTranslationsFromModel($service->mainModel->multiLanguage, $tpl,
                array_merge($this->getAutoFill(), ['id', 'language_code', 'record_id']));
        }

        return $output;
    }

    /**
     * Gathering fields from specific model
     *
     * @param stdClass $model
     * @param string $tpl
     * @param array $skip
     * @return string
     */
    private function gatherTranslationsFromModel(stdClass $model, string $tpl, array $skip)
    {
        $output = '';

        if (array_key_exists('columns', $model) && !empty($model->columns)) {
            foreach ($model->columns as $column) {
                if (in_array($column->Field, $skip)) {
                    continue;
                }

                $line = str_replace('{key}', $column->Field, $tpl);
                $line = str_replace('{value}', str_replace("_", " ", ucfirst($column->Field)), $line);

                $output .= $line;
            }
        }

        return $output;
    }
}
