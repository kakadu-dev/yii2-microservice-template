<?php

namespace common\components\MicroserviceActions;

use Yii;
use yii\db\ActiveRecord;
use yii\web\{NotFoundHttpException, ServerErrorHttpException};

/**
 * Class MicroserviceDeleteAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceDeleteAction extends MicroserviceAction
{
    /**
     * @var ActiveRecord|null
     */
    private ?ActiveRecord $_model;

    /**
     * Get deleted model
     *
     * @return ActiveRecord|null
     */
    public function getModel(): ?ActiveRecord
    {
        return $this->_model;
    }

    /**
     * Deletes a model.
     *
     * @param mixed $id id of the model to be deleted.
     *
     * @throws NotFoundHttpException on failure.
     * @throws ServerErrorHttpException
     */
    public function run($id)
    {
        $model = $this->findModel($id)->one();

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $this->_model = $model;

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
