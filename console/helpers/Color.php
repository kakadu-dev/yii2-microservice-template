<?php

namespace console\helpers;

/**
 * Class Color
 *
 * @package console\helpers
 */
class Color
{
    public const BLACK        = '0;30';
    public const DARK_GRAY    = '1;30';
    public const BLUE         = '0;30';
    public const LIGHT_BLUE   = '1;34';
    public const GREEN        = '0;32';
    public const LIGHT_GREEN  = '1;32';
    public const CYAN         = '0;36';
    public const LIGHT_CYAN   = '1;36';
    public const RED          = '0;31';
    public const LIGHT_RED    = '1;31';
    public const PURPLE       = '0;35';
    public const LIGHT_PURPLE = '1;35';
    public const BROW         = '0;33';
    public const YELLOW       = '1;33';
    public const LIGHT_GRAY   = '0;37';
    public const WHITE        = '1;37';

    /**
     * Choose available color from this constant list
     *
     * @example Color::LIGHT_PURPLE('message')
     *
     * @param $name
     * @param $arguments
     *
     * @return array|mixed|string
     */
    public static function __callStatic($name, $arguments)
    {
        if (empty($arguments) || !is_array($arguments) || (count($arguments) !== 1)) {
            return $arguments;
        }

        if (!is_string($message = array_shift($arguments))) {
            return $message;
        }

        if (!defined($color = self::class . '::' . $name)) {
            return $message;
        }

        return "\033[" . constant($color) . "m" . $message . "\033[0m" . "\n";
    }
}
