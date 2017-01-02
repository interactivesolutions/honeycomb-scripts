<?php

namespace interactivesolutions\honeycombscripts\commands;

use DB;
use interactivesolutions\honeycombcore\commands\HCCommand;

class CreateService extends HCCommand
{
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
    private $serviceName;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->gatherData();
        $this->optimizeData();
        $this->createService();

        return;
        dd($this->packageName, $this->packageName . '/http/controllers/' . $this->nameSpace);

        $tableList = explode(',', $this->argument('names'));

        foreach($tableList as $tableName)
        {
            $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

            if( ! count($columns) ) {
                $this->error("Table not found: " . $tableName . ". Continuing...");
                continue;
            }

            $columns = $this->filterByValues($columns);

            $this->modelsData[$tableName] = [
                'modelName' => $this->ask('Enter model name for "' . $tableName . '" table'),
                'columns'   => $columns,
            ];
        }


        foreach ( $this->modelsData as $tableName => $model )
        {
            $tpl = $this->file->get($this->getTpl('ocmodel'));

            $tpl = str_replace('{modelNamespace}', $this->getModelRootNamespace(), $tpl);
            $tpl = str_replace('{modelName}', $this->getModelName($model['modelName']), $tpl);
            $tpl = str_replace('{modelTable}', $tableName, $tpl);
            $tpl = str_replace('{modelFillable}', $this->getModelFillableFields($model['columns']), $tpl);

            $path = $this->getModelFilePath($model['modelName']);

            $this->createFiles($path, $tpl);

            $this->comment('Model ' . $model['modelName'] . ' created..');
        }

        if ($this->confirm("Create Migrations?", 'yes'))
        {
            $this->call('migrate:generate', array_keys($this->modelsData));
        }
    }

    /**
     * Gathering required data
     */
    private function gatherData()
    {
        $this->packageName = $this->ask('Enter package name (vendor/package) or leave empty for project level ', 'app');
        $this->serviceURL = $this->ask('Enter of the service url admin/<----');
        $this->serviceName = $this->ask('Enter service name');
    }

    /**
     * Optimizing gathered data
     */
    private function optimizeData()
    {
        // creating name space from service URL
        $this->nameSpace = '\\' . str_replace('-', '', $this->serviceURL);
        $this->nameSpace = str_replace('/', '\\', $this->nameSpace);
        $this->nameSpace = explode('\\', $this->nameSpace);
        array_pop($this->nameSpace);
        $this->nameSpace = array_filter($this->nameSpace);
        $this->nameSpace = implode('\\', $this->nameSpace);

        //adding controller to service name
        $this->serviceName .= 'Controller';

        $this->serviceRouteName = $this->getServiceRouteNameDotted();

        $this->controllerDirectory = $this->packageName . '/http/controllers/' . $this->nameSpace;
        $this->routesDirectory = $this->packageName . '/routes/';

        $this->routesDestination = $this->packageName . '/routes/' . 'routes.' . $this->serviceRouteName . '.php';

    }

    /**
     * Creating service
     */
    private function createService()
    {
        $this->createController();
        $this->createRoutes();

        $this->call('generate:routes');
    }

    private function createController()
    {
        $this->createDirectory($this->controllerDirectory);
        $this->createFileFromTemplate([
            "destination" => $this->controllerDirectory . '/' . $this->serviceName . '.php',
            "templateDestination" => __DIR__ . '/templates/controller.template.txt',
            "content" =>
                [
                    "packageName" => $this->packageName,
                    "nameSpace" => $this->nameSpace,
                    "serviceName" => $this->serviceName
                ]
        ]);

        $this->comment('Service created.');
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
            "destination" => $this->routesDestination,
            "templateDestination" => __DIR__ . '/templates/routes.template.txt',
            "content" =>
                [
                    "serviceName" => $this->serviceURL,
                    "serviceNameDotted" => $this->serviceRouteName,
                    "acl_name" => $this->getACLPrefix(),
                    "controllerNamespace" => $this->serviceName
                ]
          ]);

        $this->comment('Route created.');
    }

    /**
     * Get service name as path for generating url
     *
     * @return string
     */
    private function getServiceNameAsPath()
    {
        return $this->stringToLower($this->serviceName);
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
    private function getAclServiceName()
    {
        $serviceName = explode('/', $this->serviceName);
        $serviceName = array_pop($serviceName);
        $serviceName = $this->stringWithUnderscore($serviceName);

        return $serviceName;
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
        $name = $this->serviceName;
        $name .= $fileType;

        if( $withExtension )
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

        foreach ( $columns as $key => $column ) {
            if( ! in_array($column, $notAllowed) ) {
                $newArray[] = $column;
            }
        }

        return $newArray;
    }
}
