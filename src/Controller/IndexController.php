<?php

namespace App\Controller;

use ESD\Go\GoController;
use ESD\Plugins\Cloud\Gateway\Annotation\RequestMapping;
use ESD\Plugins\Cloud\Gateway\Annotation\RestGatewayController;
use ESD\Plugins\Cloud\Gateway\Controller\GatewayController;

/**
 * @RestGatewayController("/")
 */
class IndexController extends GatewayController
{

    /**
     * @RequestMapping("gateway")
     * @return string
     */
    public function actionGateway()
    {
        return 'gateway';
    }
}