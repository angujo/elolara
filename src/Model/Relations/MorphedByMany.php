<?php
/**
 * @author       bangujo ON 2021-04-27 19:42
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile MorphedByMany.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany as LaravelMorphToMany;

/**
 * Class MorphedByMany
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class MorphedByMany extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphToMany::class, $model_class); }

    /**
     * @param string   $name
     * @param DBColumn $morphColumn
     * @param DBTable  $endTable
     * @param string   $model_class
     *
     * @return MorphedByMany
     */
    public static function fromTable(string $name, DBColumn $morphColumn, DBTable $endTable, string $model_class)
    {
        $me                     = new self($model_class);
        $me->_relations[]       = $endTable->fqdn;
        $me->data_types[]       = $endTable->class_name.'[]';
        $me->data_types[]       = basename(Collection::class);
        $me->name               = function_name_plural($endTable->name);
        $me->phpdoc_description = "* MorphToMany method to query {$endTable->class_name}";
        $me->keys               = array_merge([$name], relation_keys([\Str::plural($name), $morphColumn->table_name,], ["{$name}_id"], [$endTable->foreign_column_name, $morphColumn->name]));
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