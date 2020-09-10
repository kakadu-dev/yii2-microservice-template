<?php

namespace common\components\MicroserviceActions;

use common\helpers\microservices\Query\Yii\YiiQuery;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\rest\Action;
use yii\web\NotFoundHttpException;

/**
 * Class MicroserviceAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceAction extends Action
{
    /**
     * @inheritDoc
     */
    public function findModel($id)
    {
        if ($this->findModel !== null) {
            return call_user_func($this->findModel, $id, $this);
        }

        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $keys       = $modelClass::primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $model = $modelClass::find()
                    ->where(array_combine($keys, $values))
                    ->andWhere(
                        YiiQuery::init(Yii::$app->request->post(), $this->modelClass)->getWhere()
                    );
            }
        } elseif ($id !== null) {

            $key = array_shift($keys);
            if ($key === null) {
                throw new NotFoundHttpException("Object not found: $id");
            }

            $model = $modelClass::find()
                ->where([$key => $id])
                ->andWhere(
                    YiiQuery::init(Yii::$app->request->post(), $this->modelClass)->getWhere()
                );

        }

        if (isset($model) && $model->exists()) {
            return $model;
        }

        throw new NotFoundHttpException("Object not found: $id");
    }
}
