<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 19:33
 */

namespace console\modules\rpc\components;

use yii\base\Controller;

/**
 * Class    BaseRpcController
 *
 * @package console\modules\rpc\components
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class BaseRpcController extends Controller
{
    /**
     * @inheritDoc
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        if (!is_array($result)) {
            $result = ['result' => $result];
        }

        return (object) $result;
    }
}
