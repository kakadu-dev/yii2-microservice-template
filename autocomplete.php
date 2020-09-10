<?php

use console\modules\rpc\components\Request;
use console\modules\rpc\components\Response;
use yii\BaseYii;
use Kakadu\Yii2Helpers\ActiveRecord\MysqlConnection;

/**
 * Yii bootstrap file.
 * Used for enhanced IDE code autocompletion.
 *
 */
class Yii extends BaseYii
{
    /**
     * @var BaseApplication|WebApplication|ConsoleApplication the application instance
     */
    public static $app;
}

/**
 * Class BaseApplication
 * Used for properties that are identical for both WebApplication and ConsoleApplication
 *
 * @property MysqlConnection $db
 */
abstract class BaseApplication extends yii\base\Application
{
}

/**
 * Class WebApplication
 * Include only Web application related components here
 *
 */
class WebApplication extends yii\web\Application
{
}

/**
 * Class ConsoleApplication
 * Include only Console application related components here
 *
 * @property Request  $request
 * @property Response $response
 */
class ConsoleApplication extends yii\console\Application
{
}
