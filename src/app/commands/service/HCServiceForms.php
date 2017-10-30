<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands\service;

use DB;
use stdClass;

/**
 * Class HCServiceForms
 * @package InteractiveSolutions\HoneycombScripts\app\commands\service
 */
class HCServiceForms extends HCBaseServiceCreation
{
    /**
     * HCServiceForms constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Creating models
     *
     * @param $data
     * @internal param $item
     * @internal param $modelData
     * @return stdClass
     */
    public function optimize(stdClass $data)
    {
        $data->formName = $data->serviceName . 'Form';
        $data->formNameSpace = str_replace('\\Http\\Controllers', '\\Forms', $data->controllerNamespace);
        $data->formDestination = str_replace('/Http/Controllers', '/Forms',
                $data->controllerDestination) . '/' . $data->formName . '.php';
        $data->formID = $this->stringWithDash(strtolower($data->serviceURL));

        return $data;
    }

    /**
     * Creating form manager
     *
     * @param $serviceData
     * @return string
     */
    public function generate(stdClass $serviceData)
    {
        $this->createFileFromTemplate([
            "destination" => $serviceData->formDestination,
            "templateDestination" => __DIR__ . '/../templates/service/form.hctpl',
            "content" => [
                "nameSpace" => $serviceData->formNameSpace,
                "className" => $serviceData->formName,
                "formID" => $serviceData->formID,
                "multiLanguage" => $serviceData->multiLanguage,
                "formFields" => $this->getFormFields($serviceData),
                "serviceRouteName" => $serviceData->serviceRouteName,
            ],
        ]);

        return $serviceData->formDestination;
    }

    /**
     * Get form manager form fields from model
     *
     * @param $data
     * @return string
     */
    private function getFormFields(stdClass $data)
    {
        $output = '';

        $output .= $this->getFields(
            $data->mainModel,
            $data->translationsLocation,
            array_merge($this->getAutoFill(), ['id']),
            file_get_contents(__DIR__ . '/../templates/service/form/basic.field.hctpl'));

        if (isset($data->mainModel->multiLanguage)) {
            $output .= $this->getFields(
                $data->mainModel->multiLanguage,
                $data->translationsLocation,
                array_merge($this->getAutoFill(), ['id', 'record_id', 'language_code']),
                file_get_contents(__DIR__ . '/../templates/service/form/multi.field.hctpl'));
        }

        return $output;
    }

    /**
     * @param stdClass $model
     * @param string $translationsLocation
     * @param array $skip
     * @param string $template
     * @return string
     */
    private function getFields(stdClass $model, string $translationsLocation, array $skip, string $template)
    {
        $output = '';

        if (isset($model->columns)) {
            foreach ($model->columns as $column) {
                if (in_array($column->Field, $skip)) {
                    continue;
                }

                $field = replaceBrackets($template, [
                    "type" => "singleLine",
                    "fieldID" => $column->Field,
                    "label" => $translationsLocation . '.' . $column->Field,
                    "required" => $column->Null == "YES" ? 0 : 1,
                ]);

                $output .= $field;
            }
        }

        return $output;
    }
}
