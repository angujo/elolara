<?php

namespace {namespace};

{imports}

 /**
 * This is the parent class to be inherited by all models and base-models
 * Acts as canvas for making custom changes to all models
 *
 * @access public
 * @version {lm_version}
 * @php {php_version}
 * @package {lm_name}
 * @subpackage models
 * @author {lm_author}
 * @generated {date}
 * @name {name}
 *
 * @todo Append your properties and functions
 *
 */
abstract class {name} extends {parent}
{
    {uses}

    //TODO Add custom entries here

     /**
     * Method to get morphing/relation name used by model
     *
     * @param string|null $class_name
     *
     * @return false|int|string
     */
    public static function morphName(string $class_name = null)
    {
        $cls = is_string($class_name) && $class_name ? $class_name : static::class;
        return array_search($cls, {relation_name});
    }

    /**
    * An Alias Method
    * @see morphName
    */
    public static function relationName(string $class_name = null){ return self::morphName($class_name); }
{functions}
}