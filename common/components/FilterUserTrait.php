<?php

namespace common\components;

use yii\data\ActiveDataProvider;

/**
 * Trait FilterUserTrait
 *
 * @package common\components
 */
trait FilterUserTrait
{
    /**
     * @param ActiveDataProvider $dataProvider
     * @param string             $column
     *
     * @return string
     */
    public static function getColumnName(ActiveDataProvider $dataProvider, string $column): string
    {
        if ($dataProvider->query->joinWith === null) {
            return $column;
        }

        if (empty($dataProvider->query->joinWith)) {
            return $column;
        }

        if (strpos($column, '.') !== false) {
            return $column;
        }

        if (\Yii::$app->controller->modelClass === null) {
            return $column;
        }

        $tableName = \Yii::$app->controller->modelClass::tableName();

        return "{$tableName}.{$column}";
    }
}
