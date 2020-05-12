<?php
/**
 * Created by PhpStorm.
 * Date: 2020-05-11
 * Time: 00:09
 */

namespace console\modules\rpc;

use yii\base\Module;

/**
 * Class    RpcModule
 *
 * @package console\modules\rpc
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class RpcModule extends Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'console\modules\rpc\controllers';
}
