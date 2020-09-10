<?php

namespace common\components\MicroserviceActions;

use yii\base\Event;
use yii\db\ActiveRecordInterface;
use yii\web\NotFoundHttpException;

/**
 * Class MicroserviceViewAction
 *
 * @package common\components\MicroserviceActions
 */
class MicroserviceViewAction extends MicroserviceAction
{
    const EVENT_RUN_VIEW_ACTION = 'runAction';

    /**
     * Displays a model.
     *
     * @param string $id
     *
     * @return ActiveRecordInterface
     * @throws NotFoundHttpException
     */
    public function run($id)
    {
        $event = new Event(['data' => $id]);

        $this->trigger(self::EVENT_RUN_VIEW_ACTION, $event);

        if (!empty($event->data['id'])) {
            $id = $event->data['id'];
        }

        $model = $this->findModel($id)->one();

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        return $model;
    }
}
