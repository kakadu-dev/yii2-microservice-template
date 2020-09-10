<?php

namespace common\components\MicroserviceActions;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\{ActiveRecordInterface, ActiveRecord};
use yii\helpers\Url;
use yii\rest\CreateAction;
use yii\web\ServerErrorHttpException;

/**
 * Class MicroserviceCreateAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceCreateAction extends CreateAction
{
    /**
     * Creates a new model.
     * @return ActiveRecord|ActiveRecordInterface the model newly created
     * @throws ServerErrorHttpException if there is any error when creating the model
     * @throws InvalidConfigException
     */
    public function run()
    {
        /* @var $model ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
    }
}
