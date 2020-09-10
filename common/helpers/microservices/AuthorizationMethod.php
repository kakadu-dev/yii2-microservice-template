<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 14:35
 */

namespace common\helpers\microservices;

use Kakadu\Microservices\exceptions\MicroserviceException;
use Kakadu\Microservices\Microservice;
use Kakadu\Microservices\MjResponse;
use yii\helpers\ArrayHelper;

/**
 * Class    AuthorizationMethod
 *
 * @package common\helpers\microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class AuthorizationMethod extends AbstractMethod
{
    const IMPORT_SERVICE_RULE    = 'actions.import-service-rules';

    /**
     * @var string
     */
    public static string $serviceName = 'authorization';

    /**
     * Import microservice authorization rules
     *
     * @param string $service
     * @param string $version
     * @param array  $rules
     *
     * @return MjResponse|null
     * @throws MicroserviceException
     */
    public static function importRules(string $service, string $version, array $rules): ?MjResponse
    {
        return Microservice::getInstance()
            ->sendServiceRequest(
                self::getServiceMethod(),
                self::IMPORT_SERVICE_RULE,
                [
                    'service' => $service,
                    'version' => $version,
                    'rules'   => $rules,
                ],
                true,
                [
                    'headers' => [
                        'Option' => 'if present',
                    ],
                ]
            );
    }
}
