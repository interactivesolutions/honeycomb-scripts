<?php

namespace interactivesolutions\honeycombscripts\commands;

use DB;
use File;
use interactivesolutions\honeycombcore\commands\HCCommand;

class MakeHCService extends HCCommand
{
    /**
     * Configuration path
     */
    const CONFIG_PATH = 'honeycomb/config.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:hcservice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating full admin service';

    /**
     * Configuration data which needs to be used in creation of services
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
     */
    public function handle()
    {
        echo shell_exec('clear');
        $this->loadConfiguration();

        foreach ($this->configurationData as $serviceData)
        {
            $this->createdFiles = [];
            $this->createService($serviceData);
        }

        //$this->updateConfiguration();
    }

    /**
     * Generating service information
     *
     * @param $serviceData
     */
    private function createService($serviceData)
    {
        $this->comment('*************************************');
        $this->comment('*         Service creation          *');
        $this->comment('*************************************');
        $this->comment($serviceData->serviceName);
        $this->comment('*************************************');

        $this->createTranslations($serviceData);
        $this->createModels($serviceData);
        $this->createController($serviceData);
        $this->createRoutes($serviceData);
        $this->updateConfiguration($serviceData);

    }

    /**
     * Loading configuration files
     */
    private function loadConfiguration()
    {
        $allFiles = File::allFiles('_automate');

        foreach ($allFiles as $file)
            if (strpos((string)$file, '.done') === false && $this->validateFile($file))
                $this->configurationData[] = $this->optimizeData($file);

    }

    /**
     * Validate file
     *
     * @param $file
     * @return bool
     */
    private function validateFile($file)
    {
        //TODO validate
        return true;
    }

    /**
     * Checking package existence
     * @param $item
     */
    private function checkPackage($item)
    {
        if (!$this->file->exists($item->rootDirectory))
            $this->abort('Package ' . $item->directory . ' not existing, please create a repository and launch "php artisan make:hcpackage" command');
    }

    /**
     * Optimizing files
     * @param $file
     * @return mixed
     */
    function optimizeData($file)
    {
        $item = json_decode($this->file->get($file));

        if ($item == null)
            $this->abort($file->getFilename() . ' has Invalid JSON format.');

        $item->file = $file;
        $item->translationFilePrefix = $this->stringWithUnderscore($item->serviceURL);

        if ($item->directory == '')
        {
            $item->directory = '';
            $item->rootDirectory = './';
            $item->translationsLocation = $item->translationFilePrefix;
        } else
        {
            $item->directory .= '/';
            $item->rootDirectory = './packages/' . $item->directory . 'src/';
            $item->translationsLocation = json_decode($this->file->get($item->rootDirectory . 'app/' . MakeHCService::CONFIG_PATH))->general->serviceProviderNameSpace . "::" . $item->translationFilePrefix;
            $item->pacakgeService = true;
            $this->checkPackage($item);
        }

        $item->controllerName = $item->serviceName . 'Controller';

        // creating name space from service URL
        $item->controllerNamespace = str_replace('/', '\\', $item->directory . 'App\Http\Controllers\\' . str_replace('-', '', $item->serviceURL));

        $item->controllerNamespace = array_filter(explode('\\', $item->controllerNamespace));
        array_pop($item->controllerNamespace);
        $item->controllerNamespace = implode('\\', $item->controllerNamespace);
        $item->controllerNamespace = str_replace('-', '', $item->controllerNamespace);

        $item->controllerNameForRoutes = str_replace('/', '\\\\', $this->createItemDirectoryPath(str_replace('-', '', $item->serviceURL)) . '\\\\' . $item->controllerName);

        // creating controller directory
        $item->controllerDestination = $this->createItemDirectoryPath($item->rootDirectory . 'app/Http/Controllers/' . str_replace('-', '/', $item->serviceURL));

        // creating models directory\
        $item->modelDirectory = str_replace('/Http/Controllers', '/Models', $item->controllerDestination);
        $item->modelNamespace = str_replace('\\Http\\Controllers', '\\Models', $item->controllerNamespace);

        // finalizing destination
        $item->controllerDestination .= '/' . $item->controllerName . '.php';

        // creating routes directory
        $item->serviceRouteName = $this->stringWithDots($item->serviceURL);
        $item->routesDestination = $item->rootDirectory . 'app/routes/routes.' . $item->serviceRouteName . '.php';

        // creating database information
        foreach ($item->database as $dbItem)
        {
            $dbItem->columns = $this->getTableColumns($dbItem->tableName);
            $dbItem->modelLocation = $item->modelDirectory . '/' . $dbItem->modelName . '.php';
        }

        $item->aclPrefix = $this->stringWithUnderscore($item->directory . $item->serviceRouteName);

        return $item;
    }

    /**
     * Creating path
     * @param $item
     * @return array|string
     */
    private function createItemDirectoryPath($item)
    {
        $item = array_filter(explode('/', $item));
        array_pop($item);
        $item = implode('/', $item);

        return $item;
    }

    /**
     * Getting table columns
     *
     * @param $tableName
     * @return mixed
     */
    private function getTableColumns($tableName)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

        if (!count($columns))
            $this->abort("Table not found: " . $tableName);
        else
            $columns = DB::select(DB::raw('SHOW COLUMNS FROM ' . $tableName));


        return $columns;
    }

    /**
     * Creating translation file
     * @param $service
     */
    private function createTranslations($service)
    {
        //TODO integrate interactivesolutions/honeycomb-languages package
        $this->createFileFromTemplate([
            "destination"         => $service->rootDirectory . 'resources/lang/en/' . $service->translationFilePrefix . '.php',
            "templateDestination" => __DIR__ . '/templates/translations.template.txt',
            "content"             =>
                [
                    "translations" => $this->gatherTranslations($service),
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
    private function gatherTranslations($service)
    {
        $output = '';

        if (!empty($service->database))
        {
            $tpl = $this->file->get(__DIR__ . '/templates/helpers/translations.template.txt');

            foreach ($service->database as $tableName => $model)
                if (array_key_exists('columns', $model) && !empty($model->columns))
                    foreach ($model->columns as $column)
                    {
                        $line = str_replace('{key}', $column->Field, $tpl);
                        $line = str_replace('{value}', str_replace("_", " ", ucfirst($column->Field)), $line);

                        $output .= $line;
                    }
        }

        return $output;
    }

    /**
     * Creating models
     * @param $item
     * @internal param $modelData
     */
    private function createModels($item)
    {
        $modelData = $item->database;
        $tableList = [];

        foreach ($modelData as $tableName => $model)
        {
            $tableList[] = $model->tableName;

            $this->createFileFromTemplate([
                "destination"         => $model->modelLocation,
                "templateDestination" => __DIR__ . '/templates/model.template.txt',
                "content"             =>
                    [
                        "modelnamespace"  => $item->modelNamespace,
                        "modelName"       => $model->modelName,
                        "columnsFillable" => $this->getColumnsFillable($model->columns),
                        "modelTable"      => $model->tableName,
                    ],
            ]);

            $this->createdFiles[] = $model->modelLocation;
        }

        $this->call('migrate:generate', ["--path" => $item->rootDirectory . 'database/migrations', "tables" => implode(",", $tableList)]);
    }

    /**
     * Get models fillable fields
     *
     * @param $columns
     * @return string
     */
    private function getColumnsFillable($columns)
    {
        $names = [];

        foreach ($columns as $column)
        {
            if (!in_array($column->Field, $this->autoFill))
                array_push($names, $column->Field);
        }

        return '[\'' . implode('\',\'', $names) . '\']';
    }

    /**
     * Restoring changed files after the abort
     * Deleting create files
     */
    protected function executeAfterAbort()
    {
        /*foreach ($this->originalFiles as $value)
        {
            $this->file->put($value['path'], $value['content']);
            $this->comment('Restored: ' . $value['path']);
        }*/

        foreach ($this->createdFiles as $value)
        {
            $this->file->delete($value);
            $this->error('Deleted: ' . $value);
        }
    }

    /**
     * Creating controller
     * @param $serviceData
     * @internal param $item
     */
    private function createController($serviceData)
    {
        $this->createFileFromTemplate([
            "destination"         => $serviceData->controllerDestination,
            "templateDestination" => __DIR__ . '/templates/controller.template.txt',
            "content"             =>
                [
                    "namespace"            => $serviceData->controllerNamespace,
                    "controllerName"       => $serviceData->controllerName,
                    "acl_prefix"           => $serviceData->aclPrefix,
                    "translationsLocation" => $serviceData->translationsLocation,
                    "serviceNameDotted"    => $this->stringWithDash($serviceData->translationFilePrefix),
                    "controllerNameDotted" => $serviceData->serviceRouteName,
                    "adminListHeader"      => $this->getAdminListHeader($serviceData),
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
    private function getAdminListHeader($serviceData)
    {
        $output = "";
        $model = null;

        $tpl = $this->file->get(__DIR__ . '/templates/helpers/admin.list.header.template.txt');

        foreach ($serviceData->database as $tableData)
        {
            if (isset($tableData->default))
                $model = $tableData;
        }

        if ($model == null)
            $this->abort('No default table for service');

        $ignoreFields = array_merge($this->autoFill, ['id']);

        if (array_key_exists('columns', $model) && !empty($model->columns))
            foreach ($model->columns as $column)
            {
                if (in_array($column->Field, $ignoreFields))
                    continue;

                $field = str_replace('{key}', $column->Field, $tpl);
                $field = str_replace('{translationsLocation}', $serviceData->translationsLocation, $field);

                $output .= $field;
            }

        return $output;
    }

    /**
     * Create route files
     *
     * @param $serviceData
     */
    private function createRoutes($serviceData)
    {
        $this->createFileFromTemplate([
            "destination"         => $serviceData->routesDestination,
            "templateDestination" => __DIR__ . '/templates/routes.template.txt',
            "content"             =>
                [
                    "serviceURL"           => $serviceData->serviceURL,
                    "controllerNameDotted" => $serviceData->serviceRouteName,
                    "acl_prefix"           => $serviceData->aclPrefix,
                    "controllerName"       => $serviceData->controllerNameForRoutes,
                ],
        ]);

        $this->call('generate:routes');
        $this->createdFiles[] = $serviceData->routesDestination;
    }

    /**
     * Updating configuration
     * @param $serviceData
     * @return null
     */
    private function updateConfiguration($serviceData)
    {
        $config = json_decode($this->file->get($serviceData->rootDirectory . 'app/' . MakeHCService::CONFIG_PATH));
        $servicePermissions = [
            "name"       => "admin." . $serviceData->serviceRouteName,
            "controller" => $serviceData->controllerNamespace . '\\' . $serviceData->controllerName,
            "actions"    =>
                [
                    $serviceData->aclPrefix . "_list",
                    $serviceData->aclPrefix . "_create",
                    $serviceData->aclPrefix . "_update",
                    $serviceData->aclPrefix . "_delete",
                    $serviceData->aclPrefix . "_force_delete",
                ],
        ];

        $contentChanged = false;

        foreach ($config->acl->permissions as &$value)
        {
            if ($value->name == "admin." . $serviceData->serviceRouteName)
            {
                if ($this->confirm('Duplicate ACL found ' . "admin." . $serviceData->serviceRouteName . ' Confirm override', 'no'))
                {
                    $contentChanged = true;
                    $value = $servicePermissions;
                    break;
                } else
                {
                    $this->abort('Can not override existing configuration. Aborting...');
                    return null;
                }
            }
        }

        if (!$contentChanged)
            $config->acl->permissions = array_merge($config->acl->permissions, [$servicePermissions]);

        $this->file->put($serviceData->rootDirectory . 'app/' . MakeHCService::CONFIG_PATH, json_encode($config, JSON_PRETTY_PRINT));
    }
}
/*


    /**
     * Get dotted service name
     *
     * @return mixed
     *
    private function getServiceRouteNameDotted()
    {
        return $this->stringWithDots($this->serviceURL);
    }



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

    /**
     * Extracting type information
     *
     * @param $columns
     * @internal param $type
     *
    private function extractColumnData($columns)
    {
        foreach ($columns as &$column)
        {
            $beginning = strpos($column->Type, '(');
            $end = strpos($column->Type, ')');

            if ($beginning)
            {
                $column->Length = substr($column->Type, $beginning + 1, $end - $beginning - 1);
                $column->Type = substr($column->Type, 0, $beginning);
            }
        }

        return $columns;
    }




} */
