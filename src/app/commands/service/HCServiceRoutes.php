<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands\service;

use DB;
use File;
use FilesystemIterator;
use stdClass;

/**
 * Class HCServiceRoutes
 * @package InteractiveSolutions\HoneycombScripts\app\commands\service
 */
class HCServiceRoutes extends HCBaseServiceCreation
{
    /**
     * HCServiceRoutes constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Optimizing configuration for routes
     *
     * @param $data
     * @return stdClass
     */
    public function optimize(stdClass $data)
    {
        $this->createRouteFolders($data);

        $fi = new FilesystemIterator($data->rootDirectory . 'app/routes/admin/', FilesystemIterator::SKIP_DOTS);
        $count = str_pad((string)(iterator_count($fi) + 1), 2, '0', STR_PAD_LEFT) . '_';

        $data->serviceRouteName = 'routes.' . $this->stringWithDots($data->serviceURL);

        $data->adminRoutesDestination = $data->rootDirectory . 'app/routes/admin/' . $count . $data->serviceRouteName . '.php';
        $data->apiRoutesDestination = $data->rootDirectory . 'app/routes/api/' . $count . $data->serviceRouteName . '.php';

        $data->aclPrefix = $this->stringWithUnderscore($data->directory . $data->serviceRouteName);

        return $data;
    }

    /**
     * Create route files
     *
     * @param stdClass $serviceData
     * @return array
     */
    public function generate(stdClass $serviceData)
    {
        $files = [];
        $files[] = $this->generateRoutes($serviceData, 'admin');
        $files[] = $this->generateRoutes($serviceData, 'api');

        return $files;
    }

    /**
     * Generating routes file
     *
     * @param stdClass $service
     * @param string $type
     * @return string
     */
    private function generateRoutes(stdClass $service, string $type)
    {
        switch ($type) {
            case 'api' :

                $destination = $service->apiRoutesDestination;
                break;

            case 'admin' :

                $destination = $service->adminRoutesDestination;
                break;

            default :
                return '';
        }

        $this->createFileFromTemplate([
            "destination" => $destination,
            "templateDestination" => __DIR__ . '/../templates/service/' . $type . '.routes.hctpl',
            "content" => [
                "serviceURL" => $service->serviceURL,
                "controllerNameDotted" => $service->serviceRouteName,
                "acl_prefix" => $service->aclPrefix,
                "controllerName" => $service->controllerNameForRoutes,
            ],
        ]);

        return $destination;
    }

    /**
     * @param stdClass $data
     */
    protected function createRouteFolders(stdClass $data)
    {
        if (!File::exists($data->rootDirectory . 'app/routes/admin')) {
            $this->createDirectory($data->rootDirectory . 'app/routes/admin');
        }

        if (!File::exists($data->rootDirectory . 'app/routes/api')) {
            $this->createDirectory($data->rootDirectory . 'app/routes/api');
        }
    }
}
