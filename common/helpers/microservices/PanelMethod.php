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

/**
 * Class    PanelMethod
 *
 * @package common\helpers\microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class PanelMethod extends AbstractMethod
{
    const PROJECTS_VIEW = 'projects.view';

    /**
     * @var string
     */
    public static string $SERVICE_NAME = 'control-panel';

    /**
     * Get project
     *
     * @param array $data
     *
     * @return MjResponse|null
     * @throws MicroserviceException
     */
    public static function viewProject($data): ?MjResponse
    {
        return Microservice::getInstance()
            ->sendServiceRequest(
                self::getServiceMethod(),
                self::PROJECTS_VIEW,
                $data
            );
    }

    /**
     * Get panel alias
     *
     * @return string
     */
    protected static function getProjectAlias(): string
    {
        return env('PANEL_ALIAS', 'panel');
    }
}
