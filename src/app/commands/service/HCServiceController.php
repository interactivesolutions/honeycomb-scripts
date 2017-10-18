<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands\service;

use DB;
use stdClass;

/**
 * Class HCServiceController
 * @package InteractiveSolutions\HoneycombScripts\app\commands\service
 */
class HCServiceController extends HCBaseServiceCreation
{
    /**
     * HCServiceController constructor.
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
     * @return stdClass
     */
    public function optimize(stdClass $data)
    {
        $data->controllerName = $data->serviceName . 'Controller';

        // creating name space from service URL
        $data->controllerNamespace = str_replace('/', '\\',
            $data->directory . 'app\http\controllers\\' . str_replace('-', '', $data->serviceURL));

        $data->controllerNamespace = array_filter(explode('\\', $data->controllerNamespace));
        array_pop($data->controllerNamespace);
        $data->controllerNamespace = implode('\\', $data->controllerNamespace);
        $data->controllerNamespace = str_replace('-', '', $data->controllerNamespace);

        $routesNameSpace = str_replace('/', '\\\\',
            $this->createItemDirectoryPath(str_replace('-', '', $data->serviceURL)));

        if ($routesNameSpace == "") {
            $data->controllerNameForRoutes = $data->controllerName;
        } else {
            $data->controllerNameForRoutes = $routesNameSpace . '\\\\' . $data->controllerName;
        }

        // creating controller directory
        $data->controllerDestination = $this->createItemDirectoryPath($data->rootDirectory . 'app/http/controllers/' . str_replace('-',
                '', $data->serviceURL));

        return $data;
    }

    /**
     * Creating path
     * @param $item
     * @return array|string
     */
    private function createItemDirectoryPath(string $item)
    {
        $item = array_filter(explode('/', $item));
        array_pop($item);
        $item = implode('/', $item);

        return $item;
    }

    /**
     * @param stdClass $data
     * @return string|void
     */
    public function generate(stdClass $data)
    {
        if (isset($data->mainModel->multiLanguage)) {
            $this->createMultiLanguageController($data);
        } else {
            $this->createBasicController($data);
        }
    }

    /**
     * Creating multi language controller
     *
     * @param stdClass $data
     */
    private function createMultiLanguageController(stdClass $data)
    {
        $this->createFileFromTemplate([
            "destination" => $data->controllerDestination,
            "templateDestination" => __DIR__ . '/../templates/service/controller/multiLanguage.hctpl',
            "content" => [
                "namespace" => $data->controllerNamespace,
                "controllerName" => $data->controllerName,
                "acl_prefix" => $data->aclPrefix,
                "serviceURL" => $data->serviceURL,
                "translationsLocation" => $data->translationsLocation,
                "serviceNameDotted" => $this->stringWithDash($data->translationFilePrefix),
                "controllerNameDotted" => $data->serviceRouteName,
                "adminListHeader" => $this->getAdminListHeader($data),
                "formValidationName" => $data->formValidationName,
                "translationsFormValidationName" => $data->formTranslationsValidationName,
                "functions" => replaceBrackets(file_get_contents(__DIR__ . '/../templates/service/controller/multilanguage/functions.hctpl'),
                    [
                        "modelName" => $data->mainModel->modelName,
                        "modelNameSpace" => $data->modelNamespace,
                    ]),
                "inputData" => $this->getInputData($data),
                "useFiles" => $this->getUseFiles($data, true),
                "mainModelName" => $data->mainModel->modelName,
                "searchableFields" => $this->getSearchableFields($data),
                "searchableFieldsTranslations" => $this->getSearchableFields($data, true),
            ],
        ]);

        return $data->controllerDestination;
    }

    /**
     * Creating simple controller
     *
     * @param stdClass $data
     */
    private function createBasicController(stdClass $data)
    {
        $this->createFileFromTemplate([
            "destination" => $data->controllerDestination,
            "templateDestination" => __DIR__ . '/../templates/service/controller/basic.hctpl',
            "content" => [
                "namespace" => $data->controllerNamespace,
                "controllerName" => $data->controllerName,
                "acl_prefix" => $data->aclPrefix,
                "translationsLocation" => $data->translationsLocation,
                "serviceNameDotted" => $this->stringWithDash($data->translationFilePrefix),
                "controllerNameDotted" => $data->serviceRouteName,
                "adminListHeader" => $this->getAdminListHeader($data),
                "formValidationName" => $data->formValidationName,
                "functions" => replaceBrackets(file_get_contents(__DIR__ . '/../templates/service/controller/basic/functions.hctpl'),
                    [
                        "modelName" => $data->mainModel->modelName,
                        "modelNameSpace" => $data->modelNamespace,
                    ]),
                "inputData" => $this->getInputData($data),
                "useFiles" => $this->getUseFiles($data),
                "mainModelName" => $data->mainModel->modelName,
                "searchableFields" => $this->getSearchableFields($data),
            ],
        ]);

        return $data->controllerDestination;
    }

    /**
     * Get list header from model data
     *
     * @param $data
     * @return string
     */
    private function getAdminListHeader(stdClass $data)
    {
        $output = '';
        $model = $data->mainModel;

        //getting parameters which are not multilanguage
        $output .= $this->gatherHeaders($model, array_merge($this->getAutoFill(), ['id']), $data->translationsLocation,
            false);

        //getting parameters which are not multilanguage
        if (isset($model->multiLanguage)) {
            $output .= $this->gatherHeaders($model->multiLanguage,
                array_merge($this->getAutoFill(), ['id', 'language_code', 'record_id']), $data->translationsLocation,
                true);
        }

        return $output;
    }

    /**
     * @param stdClass $model
     * @param array $skip
     * @param string $translationsLocation
     * @param bool $multiLanguage
     * @return string
     */
    private function gatherHeaders(stdClass $model, array $skip, string $translationsLocation, bool $multiLanguage)
    {
        $output = '';

        if ($multiLanguage) {
            $tpl = file_get_contents(__DIR__ . '/../templates/service/controller/multilanguage/admin.list.header.hctpl');
        } else {
            $tpl = file_get_contents(__DIR__ . '/../templates/service/controller/basic/admin.list.header.hctpl');
        }

        if (array_key_exists('columns', $model) && !empty($model->columns)) {
            foreach ($model->columns as $column) {
                if (in_array($column->Field, $skip)) {
                    continue;
                }

                $field = str_replace('{key}', $column->Field, $tpl);
                $field = str_replace('{translationsLocation}', $translationsLocation, $field);

                $output .= $field;
            }
        }

        return $output;
    }

    /**
     * Get input keys from model data
     *
     * @param $data
     * @return string
     */
    private function getInputData(stdClass $data)
    {
        $output = '';
        $skip = array_merge($this->getAutoFill(), ['id']);

        if (!empty($data->database)) {

            if (isset($data->multiLanguage)) {
                $path = '/templates/service/controller/multilanguage/input.data.hctpl';
            } else {
                $path = '/templates/service/controller/basic/input.data.hctpl';
            }

            $tpl = file_get_contents(__DIR__ . '/..' . $path);

            foreach ($data->database as $tableName => $model) {
                if (array_key_exists('columns', $model) && !empty($model->columns) && isset($model->default)) {
                    foreach ($model->columns as $column) {
                        if (in_array($column->Field, $skip)) {
                            continue;
                        }

                        $line = str_replace('{key}', $column->Field, $tpl);
                        $output .= $line;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * get use files
     *
     * @param stdClass $data
     * @param bool $multiLanguage
     * @return string
     */
    private function getUseFiles(stdClass $data, bool $multiLanguage = false)
    {
        $output = '';

        $list = [];
        $list[] = [
            "nameSpace" => $data->modelNamespace,
            "name" => $data->mainModel->modelName,
        ];

        if ($multiLanguage) {
            $list[] = [
                "nameSpace" => $data->modelNamespace,
                "name" => $data->mainModel->modelName . 'Translations',
            ];
        }

        $list[] = [
            "nameSpace" => $data->formValidationNameSpace,
            "name" => $data->formValidationName,
        ];

        if (isset($data->mainModel->multiLanguage)) {

            $list[] = [
                "nameSpace" => $data->formValidationNameSpace,
                "name" => $data->formTranslationsValidationName,
            ];
        }

        foreach ($list as $key => $value) {
            $output .= "\r\n" . 'use ' . $value['nameSpace'] . '\\' . $value['name'] . ';';
        }

        return $output;
    }

    /**
     * Get searchable fields from model data
     *
     * @param stdClass $data
     * @param bool $multiLanguage
     * @return string
     */
    private function getSearchableFields(stdClass $data, bool $multiLanguage = false)
    {
        $output = '';

        $model = $data->mainModel;

        $whereTpl = file_get_contents(__DIR__ . '/../templates/shared/where.hctpl');
        $orWhereTpl = file_get_contents(__DIR__ . '/../templates/shared/or.where.hctpl');
        $skip = array_merge($this->getAutoFill(), ['id']);


        if ($multiLanguage) {
            if (array_key_exists('multiLanguage', $model) && !empty($model->multiLanguage)) {
                if (array_key_exists('columns', $model->multiLanguage) && !empty($model->multiLanguage->columns)) {
                    foreach ($model->multiLanguage->columns as $index => $column) {

                        if (in_array($column->Field, $skip)) {
                            continue;
                        }

                        if ($output == '') {
                            $output .= str_replace('{key}', $column->Field, $whereTpl);
                        } else {
                            $output .= str_replace('{key}', $column->Field, $orWhereTpl);
                        }

                    }
                }
            }
        } else {
            if (array_key_exists('columns', $model) && !empty($model->columns)) {

                foreach ($model->columns as $index => $column) {
                    if (in_array($column->Field, $skip)) {
                        continue;
                    }

                    if ($output == '') {
                        $output .= str_replace('{key}', $column->Field, $whereTpl);
                    } else {
                        $output .= str_replace('{key}', $column->Field, $orWhereTpl);
                    }

                }
            }
        }

        return $output;
    }
}
