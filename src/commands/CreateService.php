<?php

namespace interactivesolutions\honeycombscripts\commands;

use DB;
use interactivesolutions\honeycombcore\commands\HCCommand;

class CreateService extends HCCommand
{
    /**
     * Configuration path
     */
    const CONFIG_PATH = 'app/HoneyComb/config.json';

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
     * Models data holder
     *
     * @var array
     */
    private $modelsData = [];

    /**
     * Name space of the service
     *
     * @var
     */
    private $nameSpace;

    /**
     * Package name where service will be stored
     *
     * @var
     */
    private $packageName;

    /**
     * Service name
     *
     * @var
     */
    private $controllerName;

    /**
     * Service route name
     */
    private $serviceRouteName;

    /**
     * Service URL
     *
     * @var
     */
    private $serviceURL;

    /**
     * Controller directory
     *
     * @var
     */
    private $controllerDirectory;

    /**
     * Routes file destination
     *
     * @var
     */
    private $routesDestination;

    /**
     * Routes directory
     *
     * @var
     */
    private $routesDirectory;

    /**
     * ACL prefix
     *
     * @var
     */
    private $acl_prefix;

    /**
     * Original files
     * @var
     */
    private $originalFiles = [];

    /**
     * Files which were created during this command
     *
     * @var array
     */
    private $createdFiles = [];

    /**
     * Translations location
     *
     * @var
     */
    private $translationsLocation;

    /**
     * Models directory
     *
     * @var
     */
    private $modelsDirectory;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->gatherData();
        $this->optimizeData();
        $this->readOriginalFiles();
        $this->createService();
    }

    /**
     * Gathering required data
     */
    private function gatherData()
    {
        $this->packageName = $this->ask('Enter package name (vendor/package) or leave empty for project level ', 'app');

        if ($this->packageName == 'app')
            $this->translationsLocation = $this->packageName;

        $this->serviceURL = $this->ask('Enter of the service url admin/<----');
        $this->controllerName = $this->ask('Enter service name');

        $this->gatherTablesData();
    }

    /**
     * Gathering information about DB tables
     */
    private function gatherTablesData()
    {
        $repeat = true;

        while($repeat)
        {
            $tableName = $this->ask('Enter main DataBase table name');

            $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

            if (!count($columns))
            {
                $this->error("Table not found: " . $tableName . ". ");
                $repeat = $this->ask("Reenter table name?", false);

                if (!$repeat)
                    $this->abort('Aborting...');
            }
            else
                $repeat = false;
        }

        $columns = $this->filterByValues($columns);

        $this->modelsData[$tableName] = [
            'modelName'     => $this->ask('Enter model name for "' . $tableName . '" table'),
            'modelFillable' => $columns,
        ];
    }

    /**
     * Optimizing gathered data
     */
    private function optimizeData()
    {
        // creating name space from service URL
        $this->nameSpace = str_replace('/', '\\', $this->packageName . '\Http\Controllers\\' . str_replace('-', '', $this->serviceURL));
        $this->nameSpace = array_filter(explode('\\', $this->nameSpace));
        array_pop($this->nameSpace);
        $this->nameSpace = implode('\\', $this->nameSpace);

        //adding controller to service name
        $this->controllerName .= 'Controller';

        $this->serviceRouteName = $this->getServiceRouteNameDotted();

        $this->controllerDirectory = str_replace('\\', '/', $this->nameSpace);
        $this->routesDirectory = $this->packageName . '/routes/';
        $this->modelsDirectory =  str_replace('/Http/Controllers', '/Models', $this->controllerDirectory);

        $this->routesDestination = $this->routesDirectory . 'routes.' . $this->serviceRouteName . '.php';

        $this->acl_prefix = $this->getACLPrefix();
    }

    /**
     * Creating service
     */
    private function createService()
    {
        $this->createModels();
        $this->createController();
        $this->createRoutes();
        $this->updateConfiguration();

        $this->call('generate:routes');
    }

    /**
     * Updating configuration
     */
    private function updateConfiguration()
    {
        $config = json_decode($this->file->get(CreateService::CONFIG_PATH));
        $servicePermissions = [
            "name"       => "admin." . $this->serviceRouteName,
            "controller" => $this->nameSpace . '\\' . $this->controllerName,
            "actions"    =>
                [
                    $this->acl_prefix . "_list",
                    $this->acl_prefix . "_create",
                    $this->acl_prefix . "_update",
                    $this->acl_prefix . "_delete",
                    $this->acl_prefix . "_force_delete",
                ],
        ];

        $contentChanged = false;

        foreach ($config->acl->permissions as &$value)
        {
            if ($value->name == "admin." . $this->serviceRouteName)
            {
                if ($this->confirm('Duplicate ACL found ' . "admin." . $this->serviceRouteName . ' Confirm override', 'no'))
                {
                    $contentChanged = true;
                    $value = $servicePermissions;
                    break;
                } else
                {
                    $this->error('Can not override existing configuration. Aborting...');
                    $this->file->delete($this->controllerDirectory . '/' . $this->controllerName . '.php');
                    $this->comment('Deleting controller');

                    return null;
                }
            }
        }

        if (!$contentChanged)
            $config->acl->permissions = array_merge($config->acl->permissions, [$servicePermissions]);

        $this->file->put(CreateService::CONFIG_PATH, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Creating controller
     */
    private function createController()
    {
        $this->createDirectory($this->controllerDirectory);
        $this->createFileFromTemplate([
            "destination"         => $this->controllerDirectory . '/' . $this->controllerName . '.php',
            "templateDestination" => __DIR__ . '/templates/controller.template.txt',
            "content"             =>
                [
                    "nameSpace"            => $this->nameSpace,
                    "controllerName"       => $this->controllerName,
                    "packageName"          => $this->packageName,
                    "acl_prefix"           => $this->acl_prefix,
                    "translationsLocation" => $this->translationsLocation,
                    "serviceNameDotted"    => $this->stringWithDash($this->packageName . '-' . $this->serviceRouteName),
                    "controllerNameDotted" => $this->serviceRouteName,
                ],
        ]);

        $this->createdFiles[] = $this->controllerDirectory . '/' . $this->controllerName . '.php';
    }

    /**
     * Create route files
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function createRoutes()
    {
        $this->createDirectory($this->routesDirectory);
        $this->createFileFromTemplate([
            "destination"         => $this->routesDestination,
            "templateDestination" => __DIR__ . '/templates/routes.template.txt',
            "content"             =>
                [
                    "serviceURL"           => $this->serviceURL,
                    "controllerNameDotted" => $this->serviceRouteName,
                    "acl_prefix"           => $this->acl_prefix,
                    "controllerName"       => $this->controllerName,
                ],
        ]);

        $this->createdFiles[] = $this->routesDestination;
    }

    /**
     * Get service name as path for generating url
     *
     * @return string
     */
    private function getcontrollerNameAsPath()
    {
        return $this->stringToLower($this->controllerName);
    }

    /**
     * Get dotted service name
     *
     * @return mixed
     */
    private function getServiceRouteNameDotted()
    {
        return $this->stringWithDots($this->serviceURL);
    }

    /**
     * Get acl prefix name from package name and service name
     *
     * @return string
     */
    private function getACLPrefix()
    {
        return $this->packageName . '_' . $this->stringWithUnderscore($this->serviceRouteName);
    }

    /**
     * Get acl service name in lower case and underscore
     *
     * @return mixed|string
     */
    private function getAclcontrollerName()
    {
        $controllerName = explode('/', $this->controllerName);
        $controllerName = array_pop($controllerName);
        $controllerName = $this->stringWithUnderscore($controllerName);

        return $controllerName;
    }

    /**
     * Get controller namespace made by it's path
     *
     * @return string
     */
    private function getControllerNameSpace()
    {
        $path = $this->getControllerPath();

        return $this->makeNamespaceFromPath($path);
    }

    /**
     * Get controller path
     *
     * @return string
     */
    private function getControllerPath()
    {
        return $this->controllerDir . $this->getControllerName(true);
    }

    /**
     * Get controller name
     *
     * @param bool $withExtension
     * @return string
     */
    private function getControllerName($withExtension = false)
    {
        return $this->makeFileName('Controller', $withExtension);
    }

    /**
     * Make file name by adding type or extension
     *
     * @param $fileType
     * @param bool $withExtension
     * @return string
     */
    private function makeFileName($fileType, $withExtension = false)
    {
        $name = $this->controllerName;
        $name .= $fileType;

        if ($withExtension)
            $name .= '.php';

        return $name;
    }

    /**
     * Filter array by values
     *
     * @param $columns
     * @param $notAllowed
     * @return mixed
     */
    protected function filterByValues($columns, array $notAllowed = ['count', 'created_at', 'updated_at', 'deleted_at'])
    {
        $newArray = [];

        foreach ($columns as $key => $column)
            if (!in_array($column, $notAllowed))
                $newArray[] = $column;

        return $newArray;
    }

    /**
     * Reading original files
     */
    private function readOriginalFiles()
    {
        if ($this->file->exists($this->controllerDirectory . '/' . $this->controllerName . '.php'))
            $this->abort('Controller exists! Aborting...');

        if (!$this->file->exists(CreateService::CONFIG_PATH))
            $this->abort('Configuration file not found.');
        else
            $this->originalFiles[] = ["path" => CreateService::CONFIG_PATH, "content" => $this->file->get(CreateService::CONFIG_PATH)];

        if ($this->file->exists($this->routesDestination))
            $this->originalFiles[] = ["path" => $this->routesDestination, "content" => $this->file->get($this->routesDestination)];
    }

    /**
     * Restoring changed files after the abort
     * Deleting create files
     */
    protected function executeAfterAbort()
    {
        foreach ($this->originalFiles as $value)
        {
            $this->file->put($value['path'], $value['content']);
            $this->comment('Restored: ' . $value['path']);
        }

        foreach ($this->createdFiles as $value)
        {
            $this->file->delete($value);
            $this->comment('Deleted: ' . $value);
        }
    }

    /**
     * Creating models
     */
    private function createModels()
    {
        foreach ($this->modelsData as $tableName => $model)
        {
            $this->createFileFromTemplate([
                "destination"         => $this->modelsDirectory . '/' . $model['modelName'] . '.php',
                "templateDestination" => __DIR__ . '/templates/model.template.txt',
                "content"             =>
                    [
                        "modelNamespace" => str_replace('Http\Controllers', 'Models', $this->nameSpace),
                        "modelName"      => $model['modelName'],
                        "modelFillable"  => $this->getModelFillableFields($model['modelFillable']),
                        "modelTable"     => $tableName,
                    ],
            ]);

            $this->createdFiles[] = $this->modelsDirectory . '/' . $model['modelName'] . '.php';
        }

        /*if ($this->confirm("Create Migrations?", 'yes'))
            $this->call('migrate:generate', array_keys($this->modelsData));*/
    }

    /**
     * Get models fillable fields
     *
     * @param $columns
     * @return string
     */
    private function getModelFillableFields($columns)
    {
        return '[\'' . implode('\',\'', $columns) . '\']';
    }
}
