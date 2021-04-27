<?php
/**
 * @author       bangujo ON 2021-04-27 16:59
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile MorphOne.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\MorphOne as LaravelMorphOne;

/**
 * Class MorphOne
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class MorphOne extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphOne::class, $model_class); }

    /**
     * @param string  $name
     * @param DBTable $morphTable
     * @param string  $model_class
     *
     * @return MorphOne
     */
    public static function fromTable(string $name, DBTable $morphTable, string $model_class)
    {
        $me                     = new self($model_class);
        $me->is_nullable        = true;
        $me->_relations[]       = $morphTable->fqdn;
        $me->data_types[]       = $morphTable->class_name;
        $me->name               = function_name_single($morphTable->name);// ($name);
        $me->phpdoc_description = "* MorphOne method to query {$morphTable->class_name}";
        $me->keys               = var_export($name, true);
        $me->autoload();

        return $me;
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
    }
}