<?php

namespace common\helpers\microservices\Query\Yii;

use common\helpers\microservices\Query\BaseQuery;
use common\helpers\microservices\Query\JsonParserInterface;
use common\helpers\microservices\Query\QueryHelper;
use Exception;

/**
 * Class Query
 *
 * @package common\helpers\microservices\Query
 */
class YiiQuery extends BaseQuery
{
    /**
     * @param $parameters
     *
     * @return $this
     */
    protected function configure($parameters): BaseQuery
    {
        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    protected function setAttributes(array $data): self
    {
        $this->where = $this
            ->getJsonParser()
            ->setModelName($this->getMainTableName())
            ->parseJson(
                QueryHelper::getValue($data, 'query.filter', [])
            );

        $this->andWhere = $this
            ->getJsonParser()
            ->setModelName($this->getMainTableName())
            ->parseJson(
                QueryHelper::getValue($data, 'payload.authorization.filter', [])
            );

        $this->with       = QueryHelper::getValue($data, 'query.expands', []);
        $this->orderBy    = QueryHelper::getValue($data, 'query.orderBy', []);
        $this->page       = QueryHelper::getValue($data, 'query.page');
        $this->perPage    = QueryHelper::getValue($data, 'query.perPage');
        $this->allPage    = QueryHelper::getValue($data, 'query.allPage', $this->allPage);
        $this->attributes = QueryHelper::getValue($data, 'query.attributes', []);

        return $this;
    }

    /**
     * @return JsonParserInterface
     */
    protected function getJsonParser(): JsonParserInterface
    {
        return new YiiParser();
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        if (empty($this->andWhere)) {
            return $this->where;
        }

        if (empty($this->where)) {
            return $this->andWhere;
        }

        return ['and', $this->where, $this->andWhere];
    }

    /**
     * @param array|null  $with
     * @param string|null $mainClass
     *
     * @return array
     * @throws Exception
     */
    public function getWith(?array $with = [], ?string $mainClass = null): array
    {
        $query     = [];
        $with      = empty($with) ? $this->with : $with;
        $mainClass = $mainClass !== null ? $mainClass : $this->getMainClass();

        foreach ($with as $item) {

            if (is_array($item)) {

                $relation    = $item['name'];
                $expandClass = $this->getExpandClassName($mainClass, $relation);
                $expandTable = $this->getExpandTableName($expandClass);

                if (!is_string($expandTable)) {
                    throw new Exception(
                        "Class {$mainClass} doesn't have relation {$relation}}"
                    );
                }

                if ($mainClass === $expandClass) {
                    $expandTable = $relation;
                    $relation    = "{$relation} {$relation}";
                }

                $attributes = $item['attributes'] ?? [];
                if (!empty($attributes)) {
                    $attributes = $this->convertAttributes($attributes, $expandTable);
                }

                $where = $item['where'] ?? [];
                if (!empty($where)) {
                    $where = $this->getJsonParser()
                        ->setModelName($expandTable)
                        ->parseJson($where);;
                }

                $orderBy = $item['order'] ?? [];
                if (!empty($orderBy)) {
                    $orderBy = $this->getOrderBy($orderBy);
                }

                $with = $item['expands'] ?? [];
                if (!empty($with)) {
                    $with = $this->getWith($with, $expandClass);
                }

                $query[$relation] = function ($query) use ($attributes, $with, $where, $orderBy) {
                    if (!empty($attributes)) {
                        $query->select($attributes);
                    }

                    if (!empty($where)) {
                        $query->andWhere($where);
                    }

                    if (!empty($orderBy)) {
                        $query->orderBy($orderBy);
                    }

                    if (!empty($with)) {
                        $query->joinWith($with);
                    }
                };
            }
        }

        return $query;
    }

    /**
     * @param string|null $expandClassName
     *
     * @return string|null
     */
    public function getExpandTableName(?string $expandClassName): ?string
    {
        if (!is_string($expandClassName)) {
            return null;
        }

        return $expandClassName::tableName();
    }

    /**
     * @param string $mainClass
     * @param string $expandName
     *
     * @return string|null
     */
    public function getExpandClassName(string $mainClass, string $expandName): ?string
    {
        $class = new $mainClass();

        if (!$class->hasProperty($expandName)) {
            return null;
        }

        $method       = "get$expandName";
        $calledMethod = $class->$method();
        $query        = "yii\db\Query";

        if (!$calledMethod instanceof $query) {
            return null;
        }

        return $calledMethod->modelClass;
    }

    /**
     * @param array $order
     *
     * @return array|string[]
     */
    public function getOrderBy(array $order = []): array
    {
        $order = !empty($order) ? $order : $this->orderBy;

        if (empty($order)) {
            return $order;
        }

        $orderBy = [];
        foreach ($order as $item) {

            $sort = substr($item, 0, 1) === '-' ? SORT_DESC : SORT_ASC;

            $field = $sort === SORT_ASC ? $item : substr($item, 1);

            $orderBy[$field] = $sort;
        }

        return $orderBy;
    }

    /**
     * @return int|null
     */
    public function getPage(): ?int
    {
        return ($this->page !== null) ? $this->page - 1 : $this->page;
    }

    /**
     * @return int|null
     */
    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    /**
     * @return bool
     */
    public function getAllPage(): bool
    {
        return $this->allPage;
    }

    /**
     * @return array|string[]
     */
    public function getAttributes(): array
    {
        return $this->convertAttributes($this->attributes, $this->getMainTableName());
    }

    /**
     * @param array  $attributes
     * @param string $modelName
     *
     * @return array
     */
    private function convertAttributes(array $attributes, string $modelName): array
    {
        $hasExpands = !empty($this->with);

        return array_map(function ($key) use ($hasExpands, $modelName) {
            if ($hasExpands) {
                return "{$modelName}.{$key}";
            }

            return $key;
        }, $attributes);
    }

    /**
     * @return string|null
     */
    protected function getMainTableName(): ?string
    {
        if ($this->getMainClass() === null) {
            return null;
        }

        return $this->getMainClass()::tableName();
    }

    /**
     * @return string|null
     */
    protected function getMainClass(): ?string
    {
        return $this->mainClass;
    }
}
