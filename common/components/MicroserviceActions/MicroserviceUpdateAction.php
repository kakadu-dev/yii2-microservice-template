<?php

namespace common\components\MicroserviceActions;

use Yii;
use yii\base\{Model, InvalidConfigException};
use yii\db\ActiveRecord;
use yii\web\{ServerErrorHttpException, NotFoundHttpException};

/**
 * Class MicroserviceUpdateAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceUpdateAction extends MicroserviceAction
{
    /**
     * @var string
     */
    public string $scenario = Model::SCENARIO_DEFAULT;

    /**
     * Updates an existing model.
     *
     * @param $id
     *
     * @return ActiveRecord
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws InvalidConfigException
     */
    public function run($id)
    {
        /* @var $model ActiveRecord */
        $model = $this->findModel($id)->one();

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $model->scenario = $this->scenario;
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        return $model;
    }
}
