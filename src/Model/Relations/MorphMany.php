<?php
/**
 * @author       bangujo ON 2021-04-27 18:09
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile MorphMany.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany as LaravelMorphMany;

/**
 * Class MorphMany
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class MorphMany extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphMany::class, $model_class); }

    /**
     * @param string  $name
     * @param DBTable $morphTable
     * @param string  $model_class
     *
     * @return MorphMany
     */
    public static function fromTable(string $name, DBTable $morphTable, string $model_class)
    {
        $me                     = new self($model_class);
        $me->_relations[]       = $morphTable->fqdn;
        $me->data_types[]       = $morphTable->class_name.'[]';
        $me->data_types[]       = basename(Collection::class);
        $me->name               = function_name_plural($morphTable->name);
        $me->phpdoc_description = "* MorphMany method to query {$morphTable->class_name}";
        $me->keys               = var_export($name, true);
        $me->addImport(Collection::class);
        $me->autoload();

        return $me;
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}