<?php

namespace common\helpers\microservices\Query\Yii;

use common\helpers\microservices\Query\JsonParserInterface;
use Exception;

/**
 * Class YiiJsonParser
 *
 * @package common\helpers\microservices\Query\Yii
 */
class YiiParser implements JsonParserInterface
{
    public const OPERATORS = [
        'BETWEEN'          => 'between',
        'LIKE'             => 'like',
        'IN'               => 'in',
        'NOT_IN'           => '!in',
        'NOT_EQUAL'        => '!=',
        'OR'               => 'or',
        'AND'              => 'and',
        'GREATER'          => '>',
        'GREATER_OR_EQUAL' => '>=',
        'LESS'             => '<',
        'LESS_OR_EQUAL'    => '<=',
    ];

    private const BINDER_OPERATOR = [
        'OR'  => 'or',
        'AND' => 'and',
    ];

    /**
     * @var string
     */
    private string $modelName;

    /**
     * @param string $modelName
     *
     * @return $this
     */
    public function setModelName(string $modelName): self
    {
        $this->modelName = $modelName;

        return $this;
    }

    /**
     * @param array $condition
     *
     * @return array
     * @throws Exception
     */
    public function parseJson(array $condition): array
    {
        if (empty($condition)) {
            return [];
        }

        $result = [];

        foreach ($condition as $key => $value) {
            if ($this->isBinderOperator($key)) {
                if (!is_array($value)) {
                    throw new Exception("Expected type 'array' for {$key} operator");
                }

                $cycleParseJson = array_map(function ($item) {
                    return $this->parseJson($item);
                }, $value);

                $result = array_merge($result, [$this->getFieldName($key) => $cycleParseJson]);
                continue;
            }

            if (is_array($value)) {
                $result = array_merge($result, $this->parseCondition($key, $value));
                continue;
            }

            $result = array_merge($result, [$this->getFieldName($key) => $value]);
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getFieldName(string $key): string
    {
        if (strpos($key, '.') !== false) {
            return $key;
        }

        return "{$this->modelName}.{$key}";
    }

    /**
     * @param $key
     *
     * @return bool
     */
    private function isBinderOperator($key): bool
    {
        return in_array($key, self::BINDER_OPERATOR);
    }


    /**
     * @param $key
     * @param $condition
     *
     * @return array
     * @throws Exception
     */
    private function parseCondition($key, $condition): array
    {
        if (($count = count($condition)) === 0 || $count > 2) {
            throw new Exception("Operator '{$key}' has '{$count}' keys");
        }

        $firstOperator  = array_keys($condition)[0];
        $secondOperator = array_keys($condition)[1] ?? null;

        switch ($firstOperator) {
            case self::OPERATORS['BETWEEN']:
                return [$firstOperator, $this->getFieldName($key), $condition[$firstOperator][0], $condition[$firstOperator][1]];
            case self::OPERATORS['LIKE']:
                $isLeftSearch  = substr($condition[$firstOperator], 0, 1) === '%';
                $isRightSearch = substr($condition[$firstOperator], -1, 1) === '%';

                if ($isLeftSearch || $isRightSearch) {
                    return [$firstOperator, $this->getFieldName($key), $condition[$firstOperator], false];
                }

                return [$firstOperator, $this->getFieldName($key), $condition[$firstOperator]];
            case self::OPERATORS['IN']:
            case self::OPERATORS['NOT_IN']:
            case self::OPERATORS['NOT_EQUAL']:
                return [$firstOperator, $this->getFieldName($key), $condition[$firstOperator]];
            case self::OPERATORS['OR']:
                $result = [$firstOperator];
                foreach ($condition[$firstOperator] as $item) {
                    $result[] = [$this->getFieldName($key) => $item];
                }

                return $result;
            case self::OPERATORS['GREATER']:
            case self::OPERATORS['GREATER_OR_EQUAL']:
            case self::OPERATORS['LESS']:
            case self::OPERATORS['LESS_OR_EQUAL']:
                $result = [];
                if ($secondOperator) {
                    $result[] = self::OPERATORS['AND'];
                }
                foreach ($condition as $operator => $value) {
                    $result[] = [$operator, $this->getFieldName($key), $value];
                }

                return $result;
        }

        throw new Exception("Undefined operator: {$firstOperator}");
    }
}
