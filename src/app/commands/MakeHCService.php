<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use DB;
use File;
use interactivesolutions\honeycombcore\commands\HCCommand;
use stdClass;

class MakeHCService extends HCCommand
{
    /**
     * Configuration path
     *
     * @return this
     */
    const CONFIG_PATH = 'honeycomb/config.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:new-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating full admin service';

    /**
     * Configuration data which needs to be used in creation of services
     *
     * @return this
     */
    private $configurationData = [];

    /**
     * Created files which needs to be deleted in case of error
     *
     * @var array
     */
    private $createdFiles = [];

    /**
     * Model auto fill properties
     *
     * @var array
     */
    private $autoFill = ['count', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Execute the console command.
     *
     * @return this
     */
    public function handle ()
    {
        $this->loadConfiguration ();

        foreach ($this->configurationData as $serviceData) {
            $this->createdFiles = [];
            $this->createService ($serviceData);
            $this->finalizeFile ($serviceData->file);
        }
    }

    /**
     * Generating service information
     *
     * @param $serviceData
     */
    private function createService (stdClass $serviceData)
    {
        $this->comment ('');
        $this->comment ('*************************************');
        $this->comment ('*         Service creation          *');
        $this->comment ('*************************************');
        $this->comment ($serviceData->serviceName);
        $this->comment ('*************************************');

        $this->createTranslations ($serviceData);
        $this->createModels ($serviceData);
        $this->createController ($serviceData);
        $this->createFormValidator ($serviceData);
        $this->createForm ($serviceData);
        $this->createRoutes ($serviceData);
        $this->updateConfiguration ($serviceData);

    }

    /**
     * Loading configuration files
     *
     * @return this
     */
    private function loadConfiguration ()
    {
        $allFiles = File::allFiles ('_automate');

        foreach ($allFiles as $file)
            if (strpos ((string)$file, '.done') === false && $this->validateFile ($file))
                $this->configurationData[] = $this->optimizeData ($file);

    }

    /**
     * Validate file
     *
     * @param $file
     * @return bool
     */
    private function validateFile (string $file)
    {
        //TODO validate
        return true;
    }

    /**
     * Checking package existence
     * @param $item
     */
    private function checkPackage (stdClass $item)
    {
        if (!$this->file->exists ($item->rootDirectory))
            $this->abort ('Package ' . $item->directory . ' not existing, please create a repository and launch "php artisan make:hcpackage" command');
    }

    /**
     * Optimizing files
     * @param $file
     * @return mixed
     */
    function optimizeData (string $file)
    {
        $item = json_decode ($this->file->get ($file));

        if ($item == null)
            $this->abort ($file->getFilename () . ' has Invalid JSON format.');

        $item->file = $file;
        $item->translationFilePrefix = $this->stringWithUnderscore ($item->serviceURL);

        if ($item->directory == '') {
            $item->directory = '';
            $item->rootDirectory = './';
            $item->translationsLocation = $item->translationFilePrefix;
            $item->pacakgeService = false;
        } else {
            $item->directory .= '/';
            $item->rootDirectory = './packages/' . $item->directory . 'src/';
            $item->translationsLocation = $this->getServiceProviderNameSpace ($item) . "::" . $item->translationFilePrefix;
            $item->pacakgeService = true;
            $this->checkPackage ($item);
        }

        $item->controllerName = $item->serviceName . 'Controller';

        // creating name space from service URL
        $item->controllerNamespace = str_replace ('/', '\\', $item->directory . 'app\http\controllers\\' . str_replace ('-', '', $item->serviceURL));

        $item->controllerNamespace = array_filter (explode ('\\', $item->controllerNamespace));
        array_pop ($item->controllerNamespace);
        $item->controllerNamespace = implode ('\\', $item->controllerNamespace);
        $item->controllerNamespace = str_replace ('-', '', $item->controllerNamespace);

        $routesNameSpace = str_replace ('/', '\\\\', $this->createItemDirectoryPath (str_replace ('-', '', $item->serviceURL)));

        if ($routesNameSpace == "")
            $item->controllerNameForRoutes = $item->controllerName;
        else
            $item->controllerNameForRoutes = $routesNameSpace . '\\\\' . $item->controllerName;

        // creating controller directory
        $item->controllerDestination = $this->createItemDirectoryPath ($item->rootDirectory . 'app/http/controllers/' . str_replace ('-', '/', $item->serviceURL));

        // creating models directory
        $item->modelDirectory = str_replace ('/http/controllers', '/models', $item->controllerDestination);
        $item->modelNamespace = str_replace ('\\http\\controllers', '\\models', $item->controllerNamespace);

        // creating form validator data
        $item->formValidationName = $item->serviceName . 'Validator';
        $item->formValidationNameSpace = str_replace ('\\http\\controllers', '\\validators', $item->controllerNamespace);
        $item->formValidationDestination = str_replace ('/http/controllers', '/validators', $item->controllerDestination) . '/' . $item->formValidationName . '.php';

        $item->formName = $item->serviceName . 'Form';
        $item->formNameSpace = str_replace ('\\http\\controllers', '\\forms', $item->controllerNamespace);
        $item->formDestination = str_replace ('/http/controllers', '/forms', $item->controllerDestination) . '/' . $item->formName . '.php';
        $item->formID = $this->stringWithDash ($item->serviceURL);

        // finalizing destination
        $item->controllerDestination .= '/' . $item->controllerName . '.php';

        // creating routes directory
        $item->serviceRouteName = $this->stringWithDots ($item->serviceURL);
        $item->routesDestination = $item->rootDirectory . 'app/routes/routes.' . $item->serviceRouteName . '.php';

        // creating database information
        foreach ($item->database as $dbItem) {
            $dbItem->columns = $this->getTableColumns ($dbItem->tableName);
            $dbItem->modelLocation = $item->modelDirectory . '/' . $dbItem->modelName . '.php';
        }

        $item->mainModelName = $item->database[0]->modelName;

        $item->aclPrefix = $this->stringWithUnderscore ($item->directory . $item->serviceRouteName);

        return $item;
    }

    /**
     * Creating path
     * @param $item
     * @return array|string
     */
    private function createItemDirectoryPath (string $item)
    {
        $item = array_filter (explode ('/', $item));
        array_pop ($item);
        $item = implode ('/', $item);

        return $item;
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
     * Creating translation file
     *
     * @param $service
     */
    private function createTranslations (stdClass $service)
    {
        //TODO integrate interactivesolutions/honeycomb-languages package
        $this->createFileFromTemplate ([
            "destination"         => $service->rootDirectory . 'resources/lang/en/' . $service->translationFilePrefix . '.php',
            "templateDestination" => __DIR__ . '/templates/service/translations.hctpl',
            "content"             => [
                "translations" => $this->gatherTranslations ($service),
            ],
        ]);

        $this->createdFiles[] = $service->rootDirectory . 'resources/lang/en/' . $service->translationFilePrefix . '.php';
    }

    /**
     * Gathering available translations
     *
     * @param $service
     * @return string
     */
    private function gatherTranslations (stdClass $service)
    {
        $output = '';
        $tpl = $this->file->get (__DIR__ . '/templates/shared/array.element.hctpl');

        $line = str_replace ('{key}', 'page_title', $tpl);
        $line = str_replace ('{value}', $service->serviceName, $line);
        $output .= $line;

        if (!empty($service->database)) {

            foreach ($service->database as $tableName => $model)
                if (array_key_exists ('columns', $model) && !empty($model->columns))
                    foreach ($model->columns as $column) {
                        $line = str_replace ('{key}', $column->Field, $tpl);
                        $line = str_replace ('{value}', str_replace ("_", " ", ucfirst ($column->Field)), $line);

                        $output .= $line;
                    }
        }

        return $output;
    }

    /**
     * Creating models
     *
     * @param $item
     * @internal param $modelData
     */
    private function createModels (stdClass $item)
    {
        $modelData = $item->database;
        $tableList = [];

        foreach ($modelData as $tableName => $model) {
            $tableList[] = $model->tableName;

            $this->createFileFromTemplate ([
                "destination"         => $model->modelLocation,
                "templateDestination" => __DIR__ . '/templates/service/model.hctpl',
                "content"             => [
                    "modelNameSpace"  => $item->modelNamespace,
                    "modelName"       => $model->modelName,
                    "columnsFillable" => $this->getColumnsFillable ($model->columns),
                    "modelTable"      => $model->tableName,
                ],
            ]);

            $this->createdFiles[] = $model->modelLocation;
        }

        if (isset($item->generateMigrations) && $item->generateMigrations)
            $this->call ('migrate:generate', ["--path" => $item->rootDirectory . 'database/migrations', "tables" => implode (",", $tableList)]);
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
            if (!in_array ($column->Field, $this->autoFill))
                array_push ($names, $column->Field);
        }

        return '[\'' . implode ('\', \'', $names) . '\']';
    }

    /**
     * Restoring changed files after the abort
     * Deleting create files
     *
     * @return this
     */
    protected function executeAfterAbort ()
    {
        /*foreach ($this->originalFiles as $value)
        {
            $this->file->put($value['path'], $value['content']);
            $this->comment('Restored: ' . $value['path']);
        }*/

        foreach ($this->createdFiles as $value) {
            $this->file->delete ($value);
            $this->error ('Deleted: ' . $value);
        }
    }

    /**
     * Creating controller
     *
     * @param $serviceData
     * @internal param $item
     */
    private function createController (stdClass $serviceData)
    {
        $this->createFileFromTemplate ([
            "destination"         => $serviceData->controllerDestination,
            "templateDestination" => __DIR__ . '/templates/service/controller.hctpl',
            "content"             => [
                "namespace"            => $serviceData->controllerNamespace,
                "controllerName"       => $serviceData->controllerName,
                "acl_prefix"           => $serviceData->aclPrefix,
                "translationsLocation" => $serviceData->translationsLocation,
                "serviceNameDotted"    => $this->stringWithDash ($serviceData->translationFilePrefix),
                "controllerNameDotted" => $serviceData->serviceRouteName,
                "adminListHeader"      => $this->getAdminListHeader ($serviceData),
                "formValidationName"   => $serviceData->formValidationName,
                "functions"            => replaceBrackets ($this->file->get (__DIR__ . '/templates/service/controller/functions.hctpl'),
                    [
                        "modelName"      => $serviceData->mainModelName,
                        "modelNameSpace" => $serviceData->modelNamespace,
                    ]),
                "inputData"            => $this->getInputData ($serviceData),
                "useFiles"             => $this->getUseFiles ($serviceData),
                "mainModelName"        => $serviceData->mainModelName,
                "searchableFields"     => $this->getSearchableFields ($serviceData),
            ],
        ]);

        $this->createdFiles[] = $serviceData->controllerDestination;
    }

    /**
     * Get list header from model data
     *
     * @param $serviceData
     * @return string
     */
    private function getAdminListHeader (stdClass $serviceData)
    {
        $output = '';
        $model = null;

        $tpl = $this->file->get (__DIR__ . '/templates/service/controller/admin.list.header.hctpl');

        $model = $this->getDefaultTable ($serviceData->database);

        if ($model == null)
            $this->abort ('No default table for service');

        $skip = array_merge ($this->autoFill, ['id']);

        if (array_key_exists ('columns', $model) && !empty($model->columns))
            foreach ($model->columns as $column) {
                if (in_array ($column->Field, $skip))
                    continue;

                $field = str_replace ('{key}', $column->Field, $tpl);
                $field = str_replace ('{translationsLocation}', $serviceData->translationsLocation, $field);

                $output .= $field;
            }

        return $output;
    }

    /**
     * Create route files
     *
     * @param $serviceData
     */
    private function createRoutes (stdClass $serviceData)
    {
        $this->createFileFromTemplate ([
            "destination"         => $serviceData->routesDestination,
            "templateDestination" => __DIR__ . '/templates/service/routes.hctpl',
            "content"             => [
                "serviceURL"           => $serviceData->serviceURL,
                "controllerNameDotted" => $serviceData->serviceRouteName,
                "acl_prefix"           => $serviceData->aclPrefix,
                "controllerName"       => $serviceData->controllerNameForRoutes,
            ],
        ]);

        if ($serviceData->rootDirectory != './')
            $this->call ('hc:routes', ["directory" => $serviceData->rootDirectory]);
        else
            $this->call ('hc:routes');

        $this->createdFiles[] = $serviceData->routesDestination;
    }

    /**
     * Updating configuration
     *
     * @param $serviceData
     * @return null
     */
    private function updateConfiguration (stdClass $serviceData)
    {
        $config = json_decode ($this->file->get ($serviceData->rootDirectory . 'app/' . MakeHCService::CONFIG_PATH));

        $config = $this->updateActions ($config, $serviceData);
        $config = $this->updateRolesActions ($config, $serviceData);
        $config = $this->updateMenu ($config, $serviceData);
        $config = $this->updateFormManager ($config, $serviceData);

        $this->file->put ($serviceData->rootDirectory . 'app/' . MakeHCService::CONFIG_PATH, json_encode ($config, JSON_PRETTY_PRINT));
    }

    /**
     * Updating service actions
     *
     * @param $config
     * @param $serviceData
     * @return null
     */
    private function updateActions (stdClass $config, stdClass $serviceData)
    {
        $servicePermissions = [
            "name"       => "admin." . $serviceData->serviceRouteName,
            "controller" => $serviceData->controllerNamespace . '\\' . $serviceData->controllerName,
            "actions"    => [
                $serviceData->aclPrefix . "_list",
                $serviceData->aclPrefix . "_create",
                $serviceData->aclPrefix . "_update",
                $serviceData->aclPrefix . "_delete",
                $serviceData->aclPrefix . "_force_delete",
            ],
            "actionsApps" => [
                'api_v1_' . $serviceData->aclPrefix . "_list",
                'api_v1_' . $serviceData->aclPrefix . "_create",
                'api_v1_' . $serviceData->aclPrefix . "_update",
                'api_v1_' . $serviceData->aclPrefix . "_delete",
                'api_v1_' . $serviceData->aclPrefix . "_force_delete",
            ]
        ];

        $contentChanged = false;

        foreach ($config->acl->permissions as &$value) {
            if ($value->name == "admin." . $serviceData->serviceRouteName) {
                if ($this->confirm ('Duplicate ACL found ' . "admin." . $serviceData->serviceRouteName . ' Confirm override', 'no')) {
                    $contentChanged = true;
                    $value = $servicePermissions;
                    break;
                } else {
                    $this->abort ('Can not override existing configuration. Aborting...');

                    return null;
                }
            }
        }

        if (!$contentChanged)
            $config->acl->permissions = array_merge ($config->acl->permissions, [$servicePermissions]);

        return $config;
    }

    /**
     * Updating roles actions
     *
     * @param $config
     * @param $serviceData
     * @return stdClass
     */
    private function updateRolesActions (stdClass $config, stdClass $serviceData)
    {
        $rolesActions = [
            "project-admin" =>
                [$serviceData->aclPrefix . "_list",
                    $serviceData->aclPrefix . "_create",
                    $serviceData->aclPrefix . "_update",
                    $serviceData->aclPrefix . "_delete",
                ]
        ];

        if (empty($config->acl->rolesActions))
            $config->acl->rolesActions = $rolesActions;
        else
            $config->acl->rolesActions->{"project-admin"} = array_unique (array_merge ($config->acl->rolesActions->{"project-admin"}, $rolesActions['project-admin']));

        return $config;
    }

    /**
     * Finalizing file
     *
     * @param $file
     */
    private function finalizeFile (string $file)
    {
        $this->file->move ($file, $file . '.done');
    }

    /**
     * Get input keys from model data
     *
     * @param $serviceData
     * @return string
     */
    private function getInputData (stdClass $serviceData)
    {
        $output = '';
        $skip = array_merge ($this->autoFill, ['id']);

        if (!empty($serviceData->database)) {
            $tpl = $this->file->get (__DIR__ . '/templates/service/controller/input.data.hctpl');

            foreach ($serviceData->database as $tableName => $model)
                if (array_key_exists ('columns', $model) && !empty($model->columns) && isset($model->default))
                    foreach ($model->columns as $column) {
                        if (in_array ($column->Field, $skip))
                            continue;

                        $line = str_replace ('{key}', $column->Field, $tpl);
                        $output .= $line;
                    }
        }

        return $output;
    }

    /**
     * get use files
     *
     * @param $serviceData
     * @return string
     */
    private function getUseFiles (stdClass $serviceData)
    {
        $output = '';

        $list = [];
        $list[] = [
            "nameSpace" => $serviceData->modelNamespace,
            "name"      => $this->getDefaultTable ($serviceData->database)->modelName,
        ];

        $list[] = [
            "nameSpace" => $serviceData->formValidationNameSpace,
            "name"      => $serviceData->formValidationName,
        ];

        foreach ($list as $key => $value)
            $output .= "\r\n" . 'use ' . $value['nameSpace'] . '\\' . $value['name'] . ';';

        return $output;
    }

    /**
     * create form validator
     *
     * @param $serviceData
     */
    private function createFormValidator (stdClass $serviceData)
    {
        $this->createFileFromTemplate ([
            "destination"         => $serviceData->formValidationDestination,
            "templateDestination" => __DIR__ . '/templates/service/validation.form.hctpl',
            "content"             => [
                "formValidationNameSpace" => $serviceData->formValidationNameSpace,
                "formValidationName"      => $serviceData->formValidationName,
                "formRules"               => $this->getRules ($serviceData),
            ],
        ]);

        $this->createdFiles[] = $serviceData->formValidationDestination;
    }

    /**
     * get rules
     *
     * @param $serviceData
     * @return string
     */
    private function getRules (stdClass $serviceData)
    {
        $output = '';
        $skip = array_merge ($this->autoFill, ['id']);

        if (!empty($serviceData->database)) {
            $tpl = $this->file->get (__DIR__ . '/templates/shared/array.element.hctpl');

            foreach ($serviceData->database as $tableName => $model)
                if (array_key_exists ('columns', $model) && !empty($model->columns) && isset($model->default))
                    foreach ($model->columns as $column) {
                        if (in_array ($column->Field, $skip))
                            continue;

                        if ($column->Null == "NO") {
                            $line = str_replace ('{key}', $column->Field, $tpl);
                            $line = str_replace ('{value}', 'required', $line);

                            $output .= $line;
                        }
                    }
        }

        return $output;
    }

    /**
     * Get searchable fields from model data
     *
     * @param $serviceData
     * @return string
     */
    private function getSearchableFields (stdClass $serviceData)
    {
        $output = '';

        $model = $this->getDefaultTable ($serviceData->database);

        $whereTpl = $this->file->get (__DIR__ . '/templates/shared/where.hctpl');
        $orWhereTpl = $this->file->get (__DIR__ . '/templates/shared/or.where.hctpl');

        if (array_key_exists ('columns', $model) && !empty($model->columns)) {
            $skip = array_merge ($this->autoFill, ['id']);

            foreach ($model->columns as $index => $column) {
                if (in_array ($column->Field, $skip))
                    continue;

                if ($output == '')
                    $output .= str_replace ('{key}', $column->Field, $whereTpl);
                else
                    $output .= str_replace ('{key}', $column->Field, $orWhereTpl);

            }
        }

        return $output;
    }

    /**
     * Getting default database table
     *
     * @param $database
     * @return mixed
     */
    private function getDefaultTable (array $database)
    {
        foreach ($database as $tableData) {
            if (isset($tableData->default))
                $model = $tableData;
        }

        return $model;
    }

    /**
     * Updating menu parameter
     *
     * @param $config
     * @param $serviceData
     * @return null
     */
    private function updateMenu (stdClass $config, stdClass $serviceData)
    {
        $menuItem = [
            "path"          => 'admin/' . $serviceData->serviceURL,
            "translation"   => $serviceData->translationsLocation . '.page_title',
            "icon"          => $serviceData->serviceIcon,
            "aclPermission" => $serviceData->aclPrefix . "_list"
        ];

        $newMenu = true;

        //TODO check if adminMenu exists if not create []
        foreach ($config->adminMenu as &$existingMenuItem) {
            if ($existingMenuItem->path == $menuItem['path']) {
                if ($this->confirm ('Duplicate Menu item found with ' . $existingMenuItem->path . ' path. Confirm override', 'no')) {
                    $existingMenuItem = $menuItem;
                    $newMenu = false;
                    break;
                } else {
                    $this->abort ('Can not override existing configuration. Aborting...');

                    return null;
                }
            }
        }

        if ($newMenu)
            $config->adminMenu = array_merge ($config->adminMenu, [$menuItem]);


        return $config;
    }

    /**
     * get service pro
     *
     * @param $item
     * @return mixed
     */
    private function getServiceProviderNameSpace (stdClass $item)
    {
        return json_decode ($this->file->get ($item->rootDirectory . 'app/' . MakeHCService::CONFIG_PATH))->general->serviceProviderNameSpace;
    }

    /**
     * Creating form manager
     *
     * @param $serviceData
     */
    private function createForm (stdClass $serviceData)
    {
        $this->createFileFromTemplate ([
            "destination"         => $serviceData->formDestination,
            "templateDestination" => __DIR__ . '/templates/service/form.hctpl',
            "content"             => [
                "nameSpace"        => $serviceData->formNameSpace,
                "className"        => $serviceData->formName,
                "formID"           => $serviceData->formID,
                "multiLanguage"    => $serviceData->multiLanguage,
                "formFields"       => $this->getFormFields ($serviceData),
                "serviceRouteName" => $serviceData->serviceRouteName
            ],
        ]);


    }

    /**
     * Get form manager form fields from model
     *
     * @param $data
     * @return string
     */
    private function getFormFields (stdClass $data)
    {
        $output = '';
        $skip = array_merge ($this->autoFill, ['id']);

        $tmp = $this->file->get (__DIR__ . '/templates/service/form/single.field.hctpl');

        $model = $this->getDefaultTable ($data->database);

        if (isset($model->columns))
            foreach ($model->columns as $column) {
                if (in_array ($column->Field, $skip))
                    continue;

                $field = replaceBrackets ($tmp, [
                    "type"     => "singleLine",
                    "fieldID"  => $column->Field,
                    "label"    => $data->translationsLocation . '.' . $column->Field,
                    "required" => $column->Null == "YES" ? 0 : 1,
                ]);

                $output .= $field;

            }

        return $output;
    }

    /**
     * Updating form manager
     *
     * @param $config
     * @param $serviceData
     * @return mixed
     */
    private function updateFormManager (stdClass $config, stdClass $serviceData)
    {
        $config->formData = json_decode (json_encode ($config->formData), true);

        if (isset($config->formData[$serviceData->formID]))
            $this->abort ('Form already exists');

        $config->formData[$serviceData->formID] = $serviceData->formNameSpace . '\\' . $serviceData->formName;

        return $config;
    }
}

/*
    /**
     * Reading original files
     *
    private function readOriginalFiles()
    {
        if ($this->file->exists(__DIR__  . $this->controllerDestination . '/' . $this->controllerName . '.php'))
            $this->abort('Controller exists! Aborting...');

//        dd($this->rootPackageDirectory . 'app/' . MakeHCService::CONFIG_PATH);
//        dd($this->rootPackageDirectory . 'app/' . MakeHCService::CONFIG_PATH);

        if (!$this->file->exists($this->rootPackageDirectory . 'app/' . MakeHCService::CONFIG_PATH))
            $this->abort('Configuration file not found.');
        else
            $this->originalFiles[] = ["path" => $this->rootPackageDirectory . 'app/' . MakeHCService::CONFIG_PATH, "content" => $this->file->get($this->rootPackageDirectory . 'app/' . MakeHCService::CONFIG_PATH)];

        if ($this->file->exists($this->routesDestination))
            $this->originalFiles[] = ["path" => $this->routesDestination, "content" => $this->file->get($this->routesDestination)];
    }
} */
