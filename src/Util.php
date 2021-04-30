<?php
/**
 * @author       bangujo ON 2021-04-13 13:14
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Util.php
 */

namespace Angujo\Elolara;


/**
 * Class Util
 *
 * @package Angujo\Elolara
 */
class Util
{
    /**
     * @param $value
     *
     * @return bool|mixed
     */
    public static function booleanValue($value)
    {
        if (is_null($value)) {
            return null;
        }
        if (is_bool($value)) {
            return (bool)$value;
        }
        $trues  = ['true', 'yes', 'on', 1];
        $falses = ['false', 'no', 'off', 0];
        if ((is_string($value) || is_numeric($value)) && in_array(strtolower($value), array_merge($trues, $falses))) {
            return in_array(strtolower($value), $trues);
        }
        return $value;
    }

    public static function className(string $name)
    : string
    {
        return ucfirst(\Str::camel(\Str::singular($name)));
    }

    public static function baseClassName(string $name)
    : string
    {
        return ucfirst(\Str::camel(Config::abstracts_prefix().ucfirst(\Str::singular($name))));
    }
}