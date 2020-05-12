<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 17:55
 */

namespace console\modules\rpc\controllers;

use console\modules\rpc\components\BaseRpcController;

/**
 * Class    DemoController
 *
 * @package console\modules\rpc\controllers
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class DemoController extends BaseRpcController
{
    public function actionHello()
    {
        return ['Hello' => 'World'];
    }
}
