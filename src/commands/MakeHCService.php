<?php

namespace interactivesolutions\honeycombscripts\commands;

use DB;
use interactivesolutions\honeycombcore\commands\HCCommand;

class MakeHCService extends HCCommand
{
    /**
     * Configuration path
     */
    const CONFIG_PATH = 'HoneyComb/config.json';

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
    private $namespace;

    /**
     * Package name where service will be stored
     *
     * @var
     */
    private $packageDirectory;

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
     * Model auto fill properties
     *
     * @var array
     */
    private $autoFill = ['count', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Defines if the service will be for package or for application
     *
     * @var bool
     */
    private $packageService = false;

    /**
     * Translation file name
     * @var
     */
    private $translationFilePrefix;

    /**
     * Full package directory
     * @var
     */
    private $rootPackageDirectory;

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
        $this->packageDirectory = $this->ask('Enter package directory (vendor/package) or leave empty for project level ', 'app');
        $this->serviceURL = $this->ask('Enter of the service url admin/<----');
        $this->controllerName = $this->ask('Enter SERVICE name');
        $this->translationFilePrefix = $this->stringWithUnderscore($this->serviceURL);

        if ($this->packageDirectory == 'app')
        {
            $this->rootPackageDirectory = '/';
            $this->translationsLocation = $this->packageDirectory . '.' . $this->translationFilePrefix;
        }
        else
        {
            $this->rootPackageDirectory = 'packages/' . $this->packageDirectory . '/src';
            $this->translationsLocation = json_decode($this->file->get($this->rootPackageDirectory . '/app/HoneyComb/config.json'))->general->serviceProviderNameSpace . "::" . $this->translationFilePrefix;
            $this->packageService = true;
            $this->checkPackage();
        }

        $this->gatherTablesData();
    }

    /**
     * Checking package existence
     */
    private function checkPackage()
    {
        if (!$this->file->exists($this->rootPackageDirectory))
            $this->abort('Package not existing, please create a repository and launch "php artisan make:hcpackage" command');
    }

    /**
     * Gathering information about DB tables
     */
    private function gatherTablesData()
    {
        $repeat = true;
        $oneMore = true;

        while($oneMore)
        {
            while ($repeat)
            {
                $tableName = $this->ask('Enter DataBase table name');

                $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

                if (!count($columns))
                {
                    $this->error("Table not found: " . $tableName . ". ");
                    $repeat = $this->confirm("Reenter table name?");

                    if (!$repeat)
                        $this->abort('Aborting...');
                    else
                        continue;
                } else
                {
                    $repeat = false;
                    $columns = DB::select(DB::raw('SHOW COLUMNS FROM ' . $tableName));
                }
            }

            $this->modelsData[$tableName] = [
                'modelName'   => $this->ask('Enter model name for "' . $tableName . '" table'),
                'columnsData' => $this->extractColumnData($columns),
            ];

            $oneMore = $this->confirm('Add more models information?');

            if ($oneMore)
                $repeat = true;
        }

    }

    /**
     * Optimizing gathered data
     */
    private function optimizeData()
    {
        // creating name space from service URL
        $this->namespace = str_replace('/', '\\', $this->packageDirectory . '\Http\Controllers\\' . str_replace('-', '', $this->serviceURL));
        $this->namespace = array_filter(explode('\\', $this->namespace));
        array_pop($this->namespace);
        $this->namespace = implode('\\', $this->namespace);

        $this->controllerDirectory = str_replace('\\', '/', $this->rootPackageDirectory) . '/Http/Controllers' . '/' . str_replace('-', '/', $this->serviceURL);
        $this->controllerDirectory = array_filter(explode('/', $this->controllerDirectory));
        array_pop($this->controllerDirectory);
        $this->controllerDirectory = implode('/', $this->controllerDirectory);

        $this->modelsDirectory = str_replace('/Http/Controllers', '/Models', $this->controllerDirectory) . '/' . str_replace('-', '/', $this->serviceURL);
        $this->modelsDirectory = array_filter(explode('/', $this->modelsDirectory));
        array_pop($this->modelsDirectory);
        $this->modelsDirectory = implode('/', $this->modelsDirectory);

        $this->routesDirectory = $this->rootPackageDirectory . '/Routes';
        $this->namespace = str_replace('-', '', $this->namespace);

        if ($this->packageService)
        {
            $this->controllerDirectory = str_replace('/Http/Controllers', '/app/Http/Controllers', $this->controllerDirectory);
            $this->modelsDirectory = str_replace('/Models', '/app/Models', $this->modelsDirectory);
            $this->routesDirectory = str_replace('/Routes', '/app/Routes', $this->routesDirectory);
        }

        //adding controller to service name
        $this->controllerName .= 'Controller';

        $this->serviceRouteName = $this->getServiceRouteNameDotted();

        $this->routesDestination = $this->routesDirectory . '/routes.' . $this->serviceRouteName . '.php';

        $this->acl_prefix = $this->getACLPrefix();
    }

    /**
     * Creating service
     */
    private function createService()
    {
        $this->createTranslations();
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
        $config = json_decode($this->file->get($this->rootPackageDirectory . '/app/' . MakeHCService::CONFIG_PATH));
        $servicePermissions = [
            "name"       => "admin." . $this->serviceRouteName,
            "controller" => $this->namespace . '\\' . $this->controllerName,
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

        $this->file->put($this->rootPackageDirectory . '/app/' . MakeHCService::CONFIG_PATH, json_encode($config, JSON_PRETTY_PRINT));
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
                    "namespace"            => $this->namespace,
                    "controllerName"       => $this->controllerName,
                    "acl_prefix"           => $this->acl_prefix,
                    "translationsLocation" => $this->translationsLocation,
                    "serviceNameDotted"    => $this->stringWithDash($this->packageDirectory . '-' . $this->serviceRouteName),
                    "controllerNameDotted" => $this->serviceRouteName,
                    "adminListHeader"      => $this->getAdminListHeader(),
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
        return $this->stringWithUnderscore($this->packageDirectory . '_' . $this->serviceRouteName);
    }

    /**
     * Reading original files
     */
    private function readOriginalFiles()
    {
        if ($this->file->exists($this->controllerDirectory . '/' . $this->controllerName . '.php'))
            $this->abort('Controller exists! Aborting...');

        if (!$this->file->exists($this->rootPackageDirectory . '/app/' . MakeHCService::CONFIG_PATH))
            $this->abort('Configuration file not found.');
        else
            $this->originalFiles[] = ["path" => $this->rootPackageDirectory . '/app/' . MakeHCService::CONFIG_PATH, "content" => $this->file->get($this->rootPackageDirectory . '/app/' . MakeHCService::CONFIG_PATH)];

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
            $this->createDirectory($this->modelsDirectory);
            $this->createFileFromTemplate([
                "destination"         => $this->modelsDirectory . '/' . $model['modelName'] . '.php',
                "templateDestination" => __DIR__ . '/templates/model.template.txt',
                "content"             =>
                    [
                        "modelnamespace"  => str_replace('Http\Controllers', 'Models', $this->namespace),
                        "modelName"       => $model['modelName'],
                        "columnsFillable" => $this->getColumnsFillable($model['columnsData']),
                        "modelTable"      => $tableName,
                    ],
            ]);

            $this->createdFiles[] = $this->modelsDirectory . '/' . $model['modelName'] . '.php';
        }

        if ($this->confirm("Create Migrations?", 'yes'))
            $this->call('migrate:generate', ["--path" => $this->rootPackageDirectory . '/database/migrations', "tables" => implode("','", array_keys($this->modelsData))]);
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
     * Get list header from model data
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function getAdminListHeader()
    {
        $output = "";

        $tpl = $this->file->get(__DIR__ . '/templates/helpers/admin.list.header.template.txt');

        $mainModel = head($this->modelsData);
        $ignoreFields = array_merge($this->autoFill, ['id']);

        if (array_key_exists('columnsData', $mainModel) && !empty($mainModel['columnsData']))
        {
            foreach ($mainModel['columnsData'] as $columnInfo)
            {
                if (in_array($columnInfo->Field, $ignoreFields))
                    continue;

                $field = str_replace('{key}', $columnInfo->Field, $tpl);
                $field = str_replace('{translationsLocation}', $this->translationsLocation, $field);

                $output .= $field;
            }
        }

        return $output;
    }

    /**
     * Extracting type information
     *
     * @param $columns
     * @internal param $type
     */
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

    /**
     * Creating translation file
     */
    private function createTranslations()
    {
        //TODO connect interactivesolutions/honeycomb-languages package
        $this->createDirectory($this->rootPackageDirectory . '/resources/lang/en');
        $this->createFileFromTemplate([
            "destination"         => $this->rootPackageDirectory . '/resources/lang/en/' . $this->translationFilePrefix . '.php',
            "templateDestination" => __DIR__ . '/templates/translations.template.txt',
            "content"             =>
                [
                    "translations" => $this->gatherTranslations(),
                ],
        ]);
    }

    /**
     * Gathering available translations
     *
     * @return string
     */
    private function gatherTranslations()
    {
        $output = '';

        if (!empty($this->modelsData))
        {

            $tpl = $this->file->get(__DIR__ . '/templates/helpers/translations.template.txt');

            foreach ($this->modelsData as $tableName => $model)
                if (array_key_exists('columnsData', $model) && !empty($model['columnsData']))
                    foreach ($model['columnsData'] as $column)
                    {
                        $line = str_replace('{key}', $column->Field, $tpl);
                        $line = str_replace('{value}', str_replace("_", " ", ucfirst($column->Field)), $line);

                        $output .= $line;
                    }
        }

        return $output;
    }
}
