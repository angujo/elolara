<?php
/**
 * @author       bangujo ON 2021-04-27 18:09
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile MorphMany.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany as LaravelMorphMany;

/**
 * Class MorphMany
 *
 * @package Angujo\Elolara\Model\Relations
 */
class MorphMany extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphMany::class,$model_class); }

    /**
     * @param string  $name
     * @param DBTable $morphTable
     * @param string  $model_class
     *
     * @return MorphMany|RelationshipFunction
     */
    public static function fromTable(string $name, DBTable $morphTable, Model $model)
    {
        $me                     = new self($model->name);
        $me->_relations[]       = $morphTable->fqdn;
        $me->data_types[]       = $morphTable->class_name.'[]';
        $me->data_types[]       = basename(Collection::class);
        $me->name               = function_name_plural($morphTable->name);
        $me->phpdoc_description = "* MorphMany method to query {$morphTable->class_name}";
        $me->keys               = var_export($name, true);
        $me->addImport(Collection::class);
        $me->autoload();

        return  $model->setFunction($me);
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}