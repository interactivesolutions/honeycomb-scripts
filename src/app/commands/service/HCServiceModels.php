<?php

namespace interactivesolutions\honeycombscripts\app\commands\service;

use DB;
use stdClass;

class HCServiceModels extends HCBaseServiceCreation
{
    private $tableNames;

    public function __construct()
    {
        parent::__construct ();
    }

    /**
     * Optimizing configuration for models
     *
     * @param $data
     * @internal param $item
     * @internal param $modelData
     * @return stdClass
     */
    public function optimize (stdClass $data)
    {
        $data->modelDirectory = str_replace ('/http/controllers', '/models', $data->controllerDestination);
        $data->modelNamespace = str_replace ('\\http\\controllers', '\\models', $data->controllerNamespace);

        foreach ($data->database as $dbItem) {
            $dbItem->columns = $this->getTableColumns ($dbItem->tableName);
            $dbItem->modelLocation = $data->modelDirectory . '/' . $dbItem->modelName . '.php';

            if (isset($dbItem->default) && $dbItem->default)
                if (isset($dbItem->multiLanguage)) {
                    $dbItem->multiLanguage->columns = $this->getTableColumns ($dbItem->multiLanguage->tableName);
                    $dbItem->multiLanguage->modelName = $dbItem->modelName . 'Translations';
                    $dbItem->multiLanguage->modelLocation = $data->modelDirectory . '/' . $dbItem->modelName . 'Translations.php';
                }
        }

        //TODO get default model, there can be only one
        $data->mainModel= $data->database[0];

        return $data;
    }

    /**
     * Getting table columns
     *
     * @param $tableName
     * @return mixed
     */
    private function getTableColumns (string $tableName)
    {
        $columns = DB::getSchemaBuilder ()->getColumnListing ($tableName);

        if (!count ($columns))
            $this->abort ("Table not found: " . $tableName);
        else
            $columns = DB::select (DB::raw ('SHOW COLUMNS FROM ' . $tableName));


        return $columns;
    }

    /**
     * Creating models
     *
     * @param $service
     * @internal param $modelData
     * @return array|string
     */
    public function generate (stdClass $service)
    {
        $modelData = $service->database;
        $this->tableNames = [];
        $files = [];

        foreach ($modelData as $tableName => $model) {
            $this->tableNames[] = $model->tableName;

            $template = __DIR__ . '/../templates/service/model/basic.hctpl';

            if (isset($model->multiLanguage)) {
                $this->createFileFromTemplate ([
                    "destination"         => $model->multiLanguage->modelLocation,
                    "templateDestination" => $template,
                    "content"             => [
                        "modelNameSpace"  => $service->modelNamespace,
                        "modelName"       => $model->multiLanguage->modelName,
                        "columnsFillable" => $this->getColumnsFillable ($model->multiLanguage->columns),
                        "modelTable"      => $model->multiLanguage->tableName,
                    ],
                ]);

                $files[] = $model->multiLanguage->modelLocation;

                $template = __DIR__ . '/../templates/service/model/multiLanguage.hctpl';
            }

            $this->createFileFromTemplate ([
                "destination"         => $model->modelLocation,
                "templateDestination" => $template,
                "content"             => [
                    "modelNameSpace"  => $service->modelNamespace,
                    "modelName"       => $model->modelName,
                    "columnsFillable" => $this->getColumnsFillable ($model->columns),
                    "modelTable"      => $model->tableName,
                ],
            ]);

            $files[] = $model->modelLocation;
        }

        return $files;
    }

    /**
     * Get models fillable fields
     *
     * @param $columns
     * @return string
     */
    private function getColumnsFillable (array $columns)
    {
        $names = [];

        foreach ($columns as $column) {
            if (!in_array ($column->Field, $this->getAutoFill()))
                array_push ($names, $column->Field);
        }

        return '[\'' . implode ('\', \'', $names) . '\']';
    }

    /**
     * Returning table names list for migration generation
     *
     * @return mixed
     */
    public function getTables()
    {
        return $this->tableNames;
    }
}
