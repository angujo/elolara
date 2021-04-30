<?php
/**
 * @author       bangujo ON 2021-04-29 19:57
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile UsesAccessor.php
 */

namespace Angujo\Elolara\Lib;


/**
 * Trait UsesAccessor
 *
 * @package Angujo\Elolara\Lib
 */
trait UsesAccessor
{
    protected $_props = [];

    /**
     * Method to integrate custom accessors into attributes
     * Should be used for attributes that are long running and no need to be rerun for every call
     *
     * @param $name  string
     * @param $value \Closure|mixed
     *
     * @return \Closure|mixed
     */
    protected function accessor($name, $value)
    {
        if (isset($this->_props[$name])) {
            return $this->_props[$name];
        }
        if (is_callable($value)) {
            return $this->_props[$name] = call_user_func_array($value, []);
        }
        return $this->_props[$name] = $value;
    }
}