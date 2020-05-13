<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 19:33
 */

namespace console\modules\rpc\components;

use Yii;
use yii\base\Controller;
use yii\base\InlineAction;
use yii\web\BadRequestHttpException;

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
     * @var array the parameters bound to the current action.
     */
    public array $actionParams = [];

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[\yii\base\Action]] when it begins to run with the given parameters.
     * This method will check the parameter names that the action requires and return
     * the provided parameters according to the requirement. If there is any missing parameter,
     * an exception will be thrown.
     * @param \yii\base\Action $action the action to be bound with parameters
     * @param array $params the parameters to be bound to the action
     * @return array the valid parameters that the action can run with.
     * @throws BadRequestHttpException if there are missing or invalid parameters.
     */
    public function bindActionParams($action, $params)
    {
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        $args = [];
        $missing = [];
        $actionParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                $isValid = true;
                if ($param->isArray()) {
                    $params[$name] = (array)$params[$name];
                } elseif (is_array($params[$name])) {
                    $isValid = false;
                } elseif (
                    PHP_VERSION_ID >= 70000 &&
                    ($type = $param->getType()) !== null &&
                    $type->isBuiltin() &&
                    ($params[$name] !== null || !$type->allowsNull())
                ) {
                    $typeName = PHP_VERSION_ID >= 70100 ? $type->getName() : (string)$type;
                    switch ($typeName) {
                        case 'int':
                            $params[$name] = filter_var($params[$name], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                        break;
                        case 'float':
                            $params[$name] = filter_var($params[$name], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        break;
                        case 'bool':
                            $params[$name] = filter_var($params[$name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        break;
                    }
                    if ($params[$name] === null) {
                        $isValid = false;
                    }
                }
                if (!$isValid) {
                    throw new BadRequestHttpException(Yii::t('yii', 'Invalid data received for parameter "{param}".', [
                        'param' => $name,
                    ]));
                }
                $args[] = $actionParams[$name] = $params[$name];
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new BadRequestHttpException(Yii::t('yii', 'Missing required parameters: {params}', [
                'params' => implode(', ', $missing),
            ]));
        }

        $this->actionParams = $actionParams;

        return $args;
    }

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
