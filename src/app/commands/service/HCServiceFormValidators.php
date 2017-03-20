<?php

namespace interactivesolutions\honeycombscripts\app\commands\service;

use DB;
use stdClass;

class HCServiceFormValidators extends HCBaseServiceCreation
{
    public function __construct ()
    {
        parent::__construct ();
    }

    /**
     * Optimizing configuration for form creation
     *
     * @param stdClass $data
     * @return stdClass
     */
    public function optimize (stdClass $data)
    {
        $data->formValidationName = $data->serviceName . 'Validator';
        $data->formValidationNameSpace = str_replace ('\\http\\controllers', '\\validators', $data->controllerNamespace);
        $data->formValidationDestination = str_replace ('/http/controllers', '/validators', $data->controllerDestination) . '/' . $data->formValidationName . '.php';

        if ($data->mainModel->multiLanguage) {
            $data->formTranslationsValidationName = $data->serviceName . 'TranslationsValidator';
            $data->formTranslationsValidationDestination = str_replace ('/http/controllers', '/validators', $data->controllerDestination) . '/' . $data->formTranslationsValidationName . '.php';
        }

        return $data;
    }

    /**
     * Generating validator files
     *
     * @param stdClass $data
     * @return array
     */
    public function generate (stdClass $data)
    {
        $files = [];

        $this->createFileFromTemplate ([
            "destination"         => $data->formValidationDestination,
            "templateDestination" => __DIR__ . '/../templates/service/validator.hctpl',
            "content"             => [
                "formValidationNameSpace" => $data->formValidationNameSpace,
                "formValidationName"      => $data->formValidationName,
                "formRules"               => $this->getRules ($data->mainModel, array_merge ($this->getAutoFill (), ['id']), false),
            ],
        ]);

        $files[] = $data->formValidationDestination;

        if (isset($data->mainModel->multiLanguage)) {

            $this->createFileFromTemplate ([
                "destination"         => $data->formTranslationsValidationDestination,
                "templateDestination" => __DIR__ . '/../templates/service/validator.hctpl',
                "content"             => [
                    "formValidationNameSpace" => $data->formValidationNameSpace,
                    "formValidationName"      => $data->formTranslationsValidationName,
                    "formRules"               => $this->getRules ($data->mainModel->multiLanguage, array_merge ($this->getAutoFill (), ['id', 'record_id', 'language_id']), true),
                ],
            ]);

            $files[] = $data->formTranslationsValidationDestination;
        }

        return $files;
    }

    /**
     * get rules
     *
     * @param stdClass $model
     * @param array $skip
     *
     * @param bool $multiLanguage
     * @return string
     */
    private function getRules (stdClass $model, array $skip, bool $multiLanguage)
    {
        $output = '';

        if (!empty($model)) {
            $tpl = file_get_contents (__DIR__ . '/../templates/shared/array.element.hctpl');

            foreach ($model->columns as $column) {
                if (in_array ($column->Field, $skip))
                    continue;

                $key = $column->Field;

                if ($multiLanguage)
                    $key = 'translations.*.' . $key;

                if ($column->Null == "NO") {
                    $line = str_replace ('{key}', $key, $tpl);
                    $line = str_replace ('{value}', 'required', $line);

                    $output .= $line;
                }
            }
        }

        return $output;
    }
}
