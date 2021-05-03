<?php
/**
 * @author       bangujo ON 2021-04-27 16:59
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile MorphOne.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\MorphOne as LaravelMorphOne;

/**
 * Class MorphOne
 *
 * @package Angujo\Elolara\Model\Relations
 */
class MorphOne extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphOne::class, $model_class); }

    /**
     * @param string  $name
     * @param DBTable $morphTable
     * @param string  $model_class
     *
     * @return MorphOne|RelationshipFunction
     */
    public static function fromTable(string $name, DBTable $morphTable, Model $model)
    {
        $me                     = new self($model->name);
        $me->is_nullable        = true;
        $me->_relations[]       = $morphTable->fqdn;
        $me->data_types[]       = $morphTable->class_name;
        $me->name               = function_name_single($morphTable->name);// ($name);
        $me->phpdoc_description = "* MorphOne method to query {$morphTable->class_name}";
        $me->keys               = var_export($name, true);
        $me->autoload();

        return  $model->setFunction($me);
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
    }
}