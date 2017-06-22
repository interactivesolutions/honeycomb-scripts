<?php

namespace interactivesolutions\honeycombscripts\app\commands;

use DB;
use File;
use interactivesolutions\honeycombcore\commands\HCCommand;
use interactivesolutions\honeycombscripts\app\commands\service\HCServiceController;
use interactivesolutions\honeycombscripts\app\commands\service\HCServiceFormValidators;
use interactivesolutions\honeycombscripts\app\commands\service\HCServiceModels;
use interactivesolutions\honeycombscripts\app\commands\service\HCServiceTranslations;
use interactivesolutions\honeycombscripts\app\commands\service\HCServiceForms;
use interactivesolutions\honeycombscripts\app\commands\service\HCServiceRoutes;
use stdClass;

class HCNewService extends HCCommand
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
    protected $description = 'Creating full admin and api service for honeycomb cms';

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

        $this->call('hc:routes');
        $this->call('hc:forms');
        $this->call('hc:admin-menu');
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

        $helpersList = [
            'controller'      => new HCServiceController(),
            'translations'    => new HCServiceTranslations(),
            'models'          => new HCServiceModels(),
            'form-validators' => new HCServiceFormValidators(),
            'forms'           => new HCServiceForms(),
            'routes'          => new HCServiceRoutes()
        ];

        foreach ($helpersList as $helper)
            $serviceData = $helper->optimize ($serviceData);

        // finalizing destination
        $serviceData->controllerDestination .= '/' . $serviceData->controllerName . '.php';

        foreach ($helpersList as $helper) {
            $files = $helper->generate ($serviceData);

            if (is_array ($files))
                $this->createdFiles = array_merge ($this->createdFiles, $files);
            else
                $this->createdFiles[] = $files;
        }

        if ($serviceData->rootDirectory != './')
            $this->call ('hc:routes', ["directory" => $serviceData->rootDirectory]);
        else
            $this->call ('hc:routes');

        if (isset($serviceData->generateMigrations) && $serviceData->generateMigrations)
            $this->call ('migrate:generate', ["--path" => $serviceData->rootDirectory . 'database/migrations', "tables" => implode (",", $helpersList['models']->getTables())]);

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
            if (strpos ((string)$file, '.done') === false)
                $this->configurationData[] = $this->optimizeData ($file);

    }

    /**
     * Optimizing files
     * @param $file
     * @return mixed
     */
    function optimizeData (string $file)
    {
        $item = json_decode (file_get_contents ($file));
        $item->file = $file;

        if ($item == null)
            $this->abort ($file->getFilename () . ' has Invalid JSON format.');

        // check if service has dynamic segment
        if( preg_match('/{_(.*?)}/', $item->serviceURL, $matches) ) {
            $item->dynamicSegmentName = $matches[0];
            $item->dynamicSegment = true;
        } else {
            $item->dynamicSegmentName = '';
            $item->dynamicSegment = false;
        }

        if ($item->directory == '') {
            $item->directory = '';
            $item->rootDirectory = './';
            $item->pacakgeService = false;
        } else {
            $item->directory .= '/';
            $item->rootDirectory = './packages/' . $item->directory . 'src/';
            $item->pacakgeService = true;
            $this->checkPackage ($item);
        }

        return $item;
    }

    /**
     * Checking package existence
     * @param $item
     */
    private function checkPackage (stdClass $item)
    {
        if (!file_exists ($item->rootDirectory))
            $this->abort ('Package ' . $item->directory . ' not existing, please create a repository and launch "php artisan hc:new-package" command');
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
            File::delete ($value);
            $this->error ('Deleted: ' . $value);
        }
    }


    /**
     * Updating configuration
     *
     * @param $serviceData
     * @return null
     */
    private function updateConfiguration (stdClass $serviceData)
    {
        $config = json_decode (file_get_contents ($serviceData->rootDirectory . 'app/' . HCNewService::CONFIG_PATH));

        $config = $this->updateActions ($config, $serviceData);
        $config = $this->updateRolesActions ($config, $serviceData);
        $config = $this->updateMenu ($config, $serviceData);
        $config = $this->updateFormManager ($config, $serviceData);

        file_put_contents ($serviceData->rootDirectory . 'app/' . HCNewService::CONFIG_PATH, json_encode ($config, JSON_PRETTY_PRINT));
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
        File::move ($file, $file . '.done');
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
            "route"         => 'admin.' . $serviceData->serviceRouteName . '.index',
            "translation"   => $serviceData->translationsLocation . '.page_title',
            "icon"          => $serviceData->serviceIcon,
            "aclPermission" => $serviceData->aclPrefix . "_list"
        ];

        $newMenu = true;

        //TODO check if adminMenu exists if not create []
        foreach ($config->adminMenu as &$existingMenuItem) {
            if ($existingMenuItem->route == $menuItem['route']) {
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
     * Updating form manager
     *
     * @param $config
     * @param $serviceData
     * @return mixed
     */
    private function updateFormManager (stdClass $config, stdClass $serviceData)
    {
        $config->formData = json_decode (json_encode ($config->formData), true);

        if (!isset($config->formData[$serviceData->formID]))
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
