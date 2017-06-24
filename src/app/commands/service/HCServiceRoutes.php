<?php

namespace interactivesolutions\honeycombscripts\app\commands\service;

use DB;
use FilesystemIterator;
use stdClass;

class HCServiceRoutes extends HCBaseServiceCreation
{
    public function __construct ()
    {
        parent::__construct ();
    }

    /**
     * Optimizing configuration for routes
     *
     * @param $data
     * @return stdClass
     */
    public function optimize (stdClass $data)
    {
        $fi = new FilesystemIterator($data->rootDirectory . 'app/routes/admin/', FilesystemIterator::SKIP_DOTS);
        $count = str_pad(iterator_count($fi) + 1, 2, '0', STR_PAD_LEFT) . '_';

        $data->serviceRouteName = $count . $this->stringWithDots ($data->serviceURL);

        $data->adminRoutesDestination = $data->rootDirectory . 'app/routes/admin/routes.' . $data->serviceRouteName . '.php';
        $data->apiRoutesDestination = $data->rootDirectory . 'app/routes/api/routes.' . $data->serviceRouteName . '.php';
        $data->routesDestination = $data->rootDirectory . 'app/routes/routes.' . $data->serviceRouteName . '.php';

        $data->aclPrefix = $this->stringWithUnderscore ($data->directory . $data->serviceRouteName);

        return $data;
    }

    /**
     * Create route files
     *
     * @param stdClass $serviceData
     * @return array
     */
    public function generate (stdClass $serviceData)
    {
        $files = [];
        $files[] = $this->generateRoutes ($serviceData, 'admin');
        $files[] = $this->generateRoutes ($serviceData, 'api');

        return $files;
    }

    /**
     * Generating routes file
     *
     * @param stdClass $service
     * @param string $type
     * @return string
     */
    private function generateRoutes (stdClass $service, string $type)
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

        $this->createFileFromTemplate ([
            "destination"         => $destination,
            "templateDestination" => __DIR__ . '/../templates/service/' . $type . '.routes.hctpl',
            "content"             => [
                "serviceURL"           => $service->serviceURL,
                "controllerNameDotted" => $service->serviceRouteName,
                "acl_prefix"           => $service->aclPrefix,
                "controllerName"       => $service->controllerNameForRoutes,
            ],
        ]);

        return $destination;
    }
}
