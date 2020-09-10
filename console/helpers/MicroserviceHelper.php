<?php

namespace console\helpers;

use common\components\AppProject;
use Exception;
use Kakadu\Microservices\Microservice;
use Yii;

class MicroserviceHelper
{
    /**
     * Create microservice instance
     *
     * @return void
     * @throws Exception
     */
    public static function createMicroservice(): void
    {
        [
            'projectAlias' => $projectAlias,
            'serviceName'  => $serviceName,
            'ijsonHost'    => $ijsonHost,
        ] = Yii::$app->params;

        Microservice::create("$projectAlias:$serviceName", [
            'ijson' => $ijsonHost,
            'env'   => YII_ENV,
        ], YII_DEBUG);
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function init(): void
    {
        self::createMicroservice();

        [
            'projectId' => $projectId,
            'domain'    => $domain,
        ] = Yii::$app->params;

        AppProject::setProjectId($projectId);
        AppProject::setApiDomain($domain);
    }
}
