<?php

namespace common\components;

use yii\helpers\ArrayHelper;
use yii\rest\Serializer;

/**
 * Class MicroserviceSerializer
 *
 * @package common\components
 */
class MicroserviceSerializer extends Serializer
{
    /**
     * @internal
     */
    public $fieldsParam = 'query.attributes';

    /**
     * @internal
     */
    public $expandParam = 'query.expands';

    /**
     * @inheritDoc
     */
    protected function getRequestedFields()
    {
        $fields = ArrayHelper::getValue($this->request->post(), $this->fieldsParam);
        $expand = $this->getExpandsFields(ArrayHelper::getValue($this->request->post(), $this->expandParam, []));

        return [
            is_array($fields) ? $fields : [],
            !empty($expand) ? $expand : [],
        ];
    }

    /**
     * @param array $expand
     *
     * @return array
     */
    private function getExpandsFields(array $expand): array
    {
        $result = [];

        foreach ($expand as $item) {
            if (!is_string($item)) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }
}
