<?php

namespace common\helpers\microservices\Query;

/**
 * Class QueryHelper
 *
 * @package common\helpers\microservices\Query
 */
class QueryHelper
{
    /**
     * @param      $array
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public static function getValue($array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key   = substr($key, $pos + 1);
        }

        if (is_array($array)) {
            if (!isset($array[$key]) && !array_key_exists($key, $array)) {
                return $default;
            }

            if ($array[$key] === null) {
                return $default;
            } else {
                return $array[$key];
            }
        }

        return $default;
    }
}
