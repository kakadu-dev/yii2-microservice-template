<?php
/**
 * Created by mikhail.
 * Date: 2019-08-09
 * Time: 11:28
 */

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;

        if ($val === '0') {
            return false;
        }

        if (empty($val)) {
            return $default;
        }

        return $val;
    }
}
