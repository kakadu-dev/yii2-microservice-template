<?php

namespace common\components\MicroserviceActions;

use common\components\FilterUserTrait;
use common\helpers\microservices\Query\Yii\YiiQuery;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\{ActiveQuery, ActiveRecord};
use yii\rest\IndexAction;
use yii\web\NotFoundHttpException;

/**
 * Class MicroserviceDeleteAllAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceDeleteAllAction extends IndexAction
{
    use FilterUserTrait;

    /**
     * Add custom query condition
     *
     * @see \Closure params
     *
     * @var null|array
     */
    public ?array $addQuery = null;

    /**
     * Column name
     *
     * @see \Closure
     *
     * @var null|array
     */
    public ?array $filterUser = null;

    /**
     * Delete all without condition
     *
     * @var bool
     */
    public bool $hardDelete = false;

    /**
     * @var array
     */
    private array $_deletedModels = [];

    /**
     * Get deleted models
     *
     * @return array
     */
    public function getDeletedModels(): array
    {
        return $this->_deletedModels;
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        if (
            !$this->hardDelete
            && empty(YiiQuery::init(Yii::$app->request->post(), $this->modelClass)->getWhere())
        ) {
            throw new NotFoundHttpException("Param 'query.filter' cannot be empty");
        }

        $this->prepareDataProvider = function (MicroserviceDeleteAllAction $action) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider->query->andWhere(
                YiiQuery::init(Yii::$app->request->post(), $this->modelClass)->getWhere()
            );

            if ($this->addQuery) {
                call_user_func(
                    $this->addQuery,
                    $dataProvider->query,
                    null,
                    $action->dataFilter,
                    $dataProvider
                );
            }

            if ($this->filterUser) {
                $filterUserColumn = call_user_func($this->filterUser);

                if ($filterUserColumn !== null) {
                    $filterUserColumn = FilterUserTrait::getColumnName($dataProvider, $filterUserColumn);
                    $dataProvider->query->andWhere([$filterUserColumn => Yii::$app->user->getId()]);
                }
            }

            return $dataProvider;
        };

        if ($this->hardDelete) {
            $this->modelClass::deleteAll();
        } else {
            $dataProvider = parent::prepareDataProvider();

            if ($dataProvider instanceof ActiveDataProvider) {
                /** @var ActiveQuery $query */
                $query = $dataProvider->query;
                $query
                    ->limit(-1)
                    ->offset(-1)
                    ->orderBy([]);

                $countDeleted = 0;

                foreach ($query->each() as $model) {

                    /** @var $model ActiveRecord */
                    if ($model->delete()) {
                        $this->_deletedModels[] = $model;
                        $countDeleted++;
                    }
                }
            }
        }

        Yii::$app->response->headers->set('X-Total-Deleted', $countDeleted ?? -1);

        return;
    }
}
