<?php
/**
 * @author       bangujo ON 2021-04-29 19:58
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile UsesStaticAccessor.php
 */

namespace Angujo\Elolara\Lib;


/**
 * Trait UsesStaticAccessor
 *
 * @package Angujo\Elolara\Lib
 */
trait UsesStaticAccessor
{
    protected static $_glob = [];

    protected static function accessor_($name, $value)
    {
        if (isset(self::$_glob[$name])) {
            return self::$_glob[$name];
        }
        if (is_callable($value)) {
            return self::$_glob[$name] = call_user_func_array($value, []);
        }
        return self::$_glob[$name] = $value;
    }
}