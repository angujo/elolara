<?php
/**
 * @author       bangujo ON 2021-04-13 10:01
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile BaseDBClass.php
 */

namespace Angujo\Elolara\Database\Traits;


/**
 * Class BaseDBClass
 *
 * @package Angujo\Elolara\Database\Traits
 */
abstract class BaseDBClass
{
    protected $_props = [];

    public function __construct($values = [])
    {
        if (is_object($values)) {
            $values = (array)$values;
        }
        $this->_props = is_array($values) ? array_combine(array_map('strtolower', array_keys($values)), $values) : [];
    }

    public function __get($name)
    {
        $name = strtolower($name);
        if (method_exists($this, $name)) return $this->{$name}();
        return $this->{$name} ?? $this->_props[$name] ?? null;
    }

    public function __set($name, $value)
    {
        throw new \Exception('Setting values not allowed!');
    }

    protected function _setProp($key, $value)
    {
        $this->_props[$key] = $value;
        return $this;
    }
}