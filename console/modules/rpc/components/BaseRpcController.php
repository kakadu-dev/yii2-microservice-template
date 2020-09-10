<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 19:33
 */

namespace console\modules\rpc\components;

use ReflectionMethod;
use Yii;
use yii\base\Action;
use yii\base\Arrayable;
use yii\base\Controller;
use yii\base\InlineAction;
use yii\base\InvalidRouteException;
use yii\base\Module;
use yii\data\DataProviderInterface;
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
     * @var mixed|null
     */
    private $predeterminedResult = null;

    /**
     * Runs an action within this controller with the specified action ID and parameters.
     * If the action ID is empty, the method will use [[defaultAction]].
     *
     * @param string $id       the ID of the action to be executed.
     * @param array  $params   the parameters (name-value pairs) to be passed to the action.
     * @param bool   $switcher changed result from switched action
     *
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @see createAction()
     */
    public function runAction($id, $params = [], $switcher = false)
    {
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }

        Yii::debug('Route to run: ' . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $action;
        }

        $oldAction    = $this->action;
        $this->action = $action;

        $modules   = [];
        $runAction = true;

        // call beforeAction on modules
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;

        if ($runAction && $this->beforeAction($action)) {
            // run the action
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }

        if (isset($this->predeterminedResult)) {
            $result = $this->predeterminedResult;
        }

        if ($oldAction !== null) {
            $this->action = $oldAction;
        }

        if ($switcher) {
            $this->predeterminedResult = $result;
        }

        return $result;
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[\yii\base\Action]] when it begins to run with the given parameters.
     * This method will check the parameter names that the action requires and return
     * the provided parameters according to the requirement. If there is any missing parameter,
     * an exception will be thrown.
     *
     * @param Action $action the action to be bound with parameters
     * @param array  $params the parameters to be bound to the action
     *
     * @return array the valid parameters that the action can run with.
     * @throws BadRequestHttpException if there are missing or invalid parameters.
     */
    public function bindActionParams($action, $params)
    {
        if ($action instanceof InlineAction) {
            $method = new ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new ReflectionMethod($action, 'run');
        }

        $args         = [];
        $missing      = [];
        $actionParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                $isValid = true;
                if ($param->isArray()) {
                    $params[$name] = (array) $params[$name];
                } elseif (is_array($params[$name])) {
                    $isValid = false;
                } elseif (
                    PHP_VERSION_ID >= 70000 &&
                    ($type = $param->getType()) !== null &&
                    $type->isBuiltin() &&
                    ($params[$name] !== null || !$type->allowsNull())
                ) {
                    $typeName = PHP_VERSION_ID >= 70100 ? $type->getName() : (string) $type;
                    switch ($typeName) {
                        case 'int':
                            $params[$name] = filter_var($params[$name], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                        break;
                        case 'float':
                            $params[$name] = filter_var($params[$name], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        break;
                        case 'bool':
                            $params[$name] = filter_var(
                                $params[$name],
                                FILTER_VALIDATE_BOOLEAN,
                                FILTER_NULL_ON_FAILURE
                            );
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

        if (!is_array($result) && !is_object($result)) {
            $result = ['result' => $result];
        }

        if ($result instanceof Arrayable || $result instanceof DataProviderInterface) {
            return $result;
        }

        $respHeaders = $this->prepareHeaders();

        if (!empty($respHeaders)) {
            if (is_object($result)) {
                $result = (array) $result;
            }

            $result['headers'] = $respHeaders;
        }

        return (object) $result;
    }

    /**
     * Prepare headers for add to response
     *
     * @return array
     */
    protected function prepareHeaders(): array
    {
        $headers     = Yii::$app->response->getHeaders();
        $respHeaders = [];

        if ($headers->count > 0) {
            $startsWith = 'x-';
            foreach ($headers->toArray() as $name => $value) {
                if (strtolower(substr($name, 0, strlen($startsWith))) !== $startsWith) {
                    $respHeaders[$name] = is_array($value)
                    && count(array_keys($value)) === 1
                    && ($value[0] ?? null) !== null ? $value[0] : $value;
                }
            }
        }

        return $respHeaders;
    }
}
