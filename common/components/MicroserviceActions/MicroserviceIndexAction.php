<?php

namespace common\components\MicroserviceActions;

use common\components\FilterUserTrait;
use common\helpers\microservices\Query\Yii\YiiQuery;
use Yii;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\rest\IndexAction;
use Exception;

/**
 * Class MicroserviceIndexAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceIndexAction extends IndexAction
{
    use FilterUserTrait;

    const EVENT_AFTER_PREPARE_DATAP_ROVIDER = 'afterPrepareDataProvider';

    /**
     * @var string
     */
    public string $extraFilter = 'extraFilter';

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
     * Get filter params
     *
     * @return array
     * @throws Exception
     */
    public function getFilterParams(): array
    {
        return YiiQuery::init(Yii::$app->request->post(), $this->modelClass)->getWhere();
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        $extraFilter = Yii::$app->request->get($this->extraFilter);

        if (!empty($extraFilter) && is_string($extraFilter)) {
            $extraFilter = json_decode($extraFilter, true);
        }

        $this->prepareDataProvider = function (MicroserviceIndexAction $action) use ($extraFilter) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider = $this->setQuery($dataProvider);

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

        $this->trigger(self::EVENT_AFTER_PREPARE_DATAP_ROVIDER, new Event());

        return parent::prepareDataProvider();
    }

    /**
     * @param ActiveDataProvider $activeDataProvider
     *
     * @return ActiveDataProvider
     * @throws Exception
     */
    protected function setQuery(ActiveDataProvider $activeDataProvider): ActiveDataProvider
    {
        $query = YiiQuery::init(Yii::$app->request->post(), $this->modelClass);

        if (!empty($query->getAttributes())) {
            $activeDataProvider->query->select(array_merge(
                $activeDataProvider->query->select ?? [],
                $query->getAttributes()
            ));
        }

        if (!empty($query->getWhere())) {
            $activeDataProvider->query->andWhere($query->getWhere());
        }

        if (!empty($query->getWith())) {
            $activeDataProvider->query->joinWith(array_merge(
                $activeDataProvider->query->joinWith ?? [],
                $query->getWith()
            ));
        }

        if (!empty($query->getOrderBy())) {
            $activeDataProvider->query->addOrderBy($query->getOrderBy());
        }

        if (is_int($query->getPerPage())) {
            $activeDataProvider->getPagination()->setPageSize($query->getPerPage());
        }

        if (is_int($query->getPage())) {
            $activeDataProvider->getPagination()->setPage($query->getPage());
        }

        return $activeDataProvider;
    }
}
