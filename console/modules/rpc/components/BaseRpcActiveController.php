<?php
/**
 * Created by mikhail.
 * Date: 5/21/20
 * Time: 15:19
 */

namespace console\modules\rpc\components;

use common\components\MicroserviceActions\{MicroserviceCreateAction,
    MicroserviceDeleteAction,
    MicroserviceDeleteAllAction,
    MicroserviceIndexAction,
    MicroserviceUpdateAction,
    MicroserviceUpdateAllAction,
    MicroserviceViewAction,
};
use MP\ExtendedApi\EActiveDataFilter;
use MP\Services\ImplementServices;
use Yii;
use yii\base\{Action, InvalidConfigException, Model};
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;
use yii\rest\Serializer;
use yii\web\ForbiddenHttpException;

/**
 * Class    BaseRpcActiveController
 *
 * @package console\modules\rpc\components
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class BaseRpcActiveController extends BaseRpcController
{
    use ImplementServices;

    /**
     * @var string the model class name. This property must be set.
     */
    public string $modelClass;

    /**
     * Search model class
     *
     * @var ActiveRecordInterface|string
     */
    public string $searchClass;

    /**
     * @var string the scenario used for updating a model.
     * @see \yii\base\Model::scenarios()
     */
    public string $updateScenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string the scenario used for creating a model.
     * @see \yii\base\Model::scenarios()
     */
    public string $createScenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string|array the configuration for creating the serializer that formats the response data.
     */
    public $serializer = 'common\components\MicroserviceSerializer';

    /**
     * List external actions
     *
     * 'delete-all' => true,
     *
     * @var array
     */
    public array $externalActions = [];

    /**
     * Check action access
     *
     * 'index'  => 'rule',
     * 'update' => 'permission',
     *  and etc...
     *
     * @var array
     */
    public array $checkAccessRules = [];

    /**
     * @var array
     */
    public array $actionsParams = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->modelClass === null) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        $customHeaders   = $this->prepareHeaders();
        $activeHeaders   = $this->prepareActiveHeaders();
        $responseHeaders = [];

        Yii::$app->response->headers->removeAll();

        if (!empty($customHeaders)) {
            $responseHeaders['headers'] = $customHeaders;
        }

        $data = $this->serializeData($result->result ?? $result);

        $serializerHeaders = $this->prepareActiveHeaders(2);

        $activeHeaders = array_merge($activeHeaders, $serializerHeaders);

        if (!empty($activeHeaders)) {
            $responseHeaders = array_merge($responseHeaders, $activeHeaders);
        }

        Yii::$app->response->headers->removeAll();

        return (object) $this->responseFormat($data, $responseHeaders, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        $actions = [
            'index'  => [
                'class'       => MicroserviceIndexAction::class,
                'modelClass'  => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'view'   => [
                'class'       => MicroserviceViewAction::class,
                'modelClass'  => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'create' => [
                'class'       => MicroserviceCreateAction::class,
                'modelClass'  => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario'    => $this->createScenario,
            ],
            'update' => [
                'class'       => MicroserviceUpdateAction::class,
                'modelClass'  => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario'    => $this->updateScenario,
            ],
            'delete' => [
                'class'       => MicroserviceDeleteAction::class,
                'modelClass'  => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
        ];

        $actions = ArrayHelper::merge($actions, $this->actionsParams);

        if (!empty($this->searchClass)) {
            $actions['index']['dataFilter'] = [
                'class'       => EActiveDataFilter::class,
                'searchModel' => $this->searchClass,
            ];
        }

        foreach ($this->externalActions as $externalAction => $value) {
            if ($value) {
                switch ($externalAction) {
                    case 'delete-all':
                        $actions[$externalAction]          = $actions['index'];
                        $actions[$externalAction]['class'] = MicroserviceDeleteAllAction::class;
                    break;

                    case 'update-all':
                        $actions[$externalAction]          = $actions['index'];
                        $actions[$externalAction]['class'] = MicroserviceUpdateAllAction::class;
                    break;
                }
            }
        }

        return $actions;
    }

    /**
     * Check access to action
     *
     * @param       $action
     * @param null  $model
     * @param array $params
     *
     * @throws ForbiddenHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        if ($this->checkAccessRules[$action] ?? null) {
            $allow = Yii::$app->user->can($this->checkAccessRules[$action], ['model' => $model, 'params' => $params]);

            if (!$allow) {
                $this->forbidden();
            }
        }
    }

    /**
     * Prepare active controller headers for add to response
     *
     * @param int $attempt
     *
     * @return array
     */
    protected function prepareActiveHeaders($attempt = 1): array
    {
        $headers = Yii::$app->response->getHeaders();

        $getValue = function ($value) {
            if (!is_array($value)) {
                return $value;
            }

            if (count($value) === 1) {
                return array_shift($value);
            }

            return $value;
        };

        switch ($this->action->id) {
            case 'index':
                $serializer = new Serializer();

                return [
                    'pagination' => [
                        'totalItems'  => $getValue($headers->remove($serializer->totalCountHeader)),
                        'pageCount'   => $getValue($headers->remove($serializer->pageCountHeader)),
                        'currentPage' => $getValue($headers->remove($serializer->currentPageHeader)),
                        'perPage'     => $getValue($headers->remove($serializer->perPageHeader)),
                    ],
                ];
            case 'delete-all':
                if ($attempt === 2) {
                    return [];
                }

                return [
                    'totalDeleted' => $getValue($headers->remove('X-Total-Deleted')),
                ];
            case 'update-all':
                if ($attempt === 2) {
                    return [];
                }

                return [
                    'totalUpdated' => $getValue($headers->remove('X-Total-Updated')),
                ];
        }

        return [];
    }

    /**
     * Format response
     *
     * @param                                 $result
     * @param array                           $headers
     * @param Action|MicroserviceDeleteAction $action
     *
     * @return array
     */
    protected function responseFormat($result, array $headers, $action): array
    {
        $response = [];

        switch ($this->action->id) {
            case 'index':
                $response['list'] = $result;
            break;
            case 'delete-all':
            case 'update-all':
            break;
            case 'delete':
                $response['model'] = $this->serializeData($action->getModel());
            break;
            case 'view':
            case 'create':
            case 'update':
                $response['model'] = $result;
            break;
            default:
                $response['result'] = $result;
            break;
        }

        return array_merge($response, $headers);
    }

    /**
     * Serializes the specified data.
     * The default implementation will create a serializer based on the configuration given by [[serializer]].
     * It then uses the serializer to serialize the given data.
     *
     * @param mixed $data the data to be serialized
     *
     * @return mixed the serialized data.
     */
    protected function serializeData($data)
    {
        return Yii::createObject($this->serializer)->serialize($data);
    }

    /**
     * Throw forbidden error
     *
     * @throws ForbiddenHttpException
     */
    protected function forbidden(): void
    {
        throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to perform this action.'));
    }
}
