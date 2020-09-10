<?php

namespace common\components\MicroserviceActions;

use common\components\FilterUserTrait;
use common\helpers\microservices\Query\Yii\YiiQuery;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\{ActiveQuery, ActiveRecord};
use yii\rest\IndexAction;
use yii\web\BadRequestHttpException;

/**
 * Class MicroserviceUpdateAllAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceUpdateAllAction extends IndexAction
{
    use FilterUserTrait;

    /**
     * @var string
     */
    public string $extraFilter = 'extraFilter';

    /**
     * @var string
     */
    public string $updatedAttribute = 'updatedAttributes';

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
     * @var array
     */
    private array $_updatedModels = [];

    /**
     * Get deleted models
     *
     * @return array
     */
    public function getUpdatedModels(): array
    {
        return $this->_updatedModels;
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        $extraFilter       = Yii::$app->request->get($this->extraFilter);
        $updatedAttributes = Yii::$app->request->get($this->updatedAttribute, []);

        if (empty($updatedAttributes)) {
            throw new BadRequestHttpException("Param '{$this->updatedAttribute}' cannot be empty");
        }

        $this->prepareDataProvider = function (MicroserviceUpdateAllAction $action) use ($extraFilter) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider->query->andWhere(
                YiiQuery::init(Yii::$app->request->post(), $this->modelClass)->getWhere()
            );

            if ($this->addQuery) {
                call_user_func($this->addQuery, $dataProvider->query, $extraFilter, $action->dataFilter, $dataProvider);

                if ($action->dataFilter->hasErrors()) {
                    return $action->dataFilter;
                }
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

        $dataProvider = parent::prepareDataProvider();
        $countUpdated = 0;

        if ($dataProvider instanceof ActiveDataProvider) {
            /** @var ActiveQuery $query */
            $query = $dataProvider->query;
            $query
                ->limit(-1)
                ->offset(-1)
                ->orderBy([]);

            foreach ($query->each() as $model) {
                /** @var $model ActiveRecord */
                $model->setAttributes($updatedAttributes);

                if ($model->save()) {
                    $this->_updatedModels[] = $model;
                    $countUpdated++;
                }
            }
        }

        Yii::$app->response->headers->set('X-Total-Updated', $countUpdated);

        return $dataProvider;
    }
}
