<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 15:33
 */

namespace common\helpers\microservices;

use Yii;

/**
 * Class    AbstractMethod
 *
 * @package common\helpers\microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class AbstractMethod
{
    /**
     * @var string
     */
    public static string $SERVICE_NAME = '';

    /**
     * Get service method name
     *
     * @return string
     */
    protected static function getServiceMethod(): string
    {
        return self::getProjectAlias() . ':' . static::$SERVICE_NAME;
    }

    /**
     * Get project alias
     *
     * @return string
     */
    protected static function getProjectAlias(): string
    {
        return Yii::$app->params['projectAlias'];
    }
}
