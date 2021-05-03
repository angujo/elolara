<?php
/**
 * @author       bangujo ON 2021-04-27 18:25
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile MorphToMany.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany as LaravelMorphToMany;

/**
 * Class MorphToMany
 *
 * @package Angujo\Elolara\Model\Relations
 */
class MorphToMany extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphToMany::class, $model_class); }

    /**
     * @param string   $name
     * @param DBColumn $morphColumn
     * @param DBTable  $endTable
     * @param string   $model
     *
     * @return MorphToMany|RelationshipFunction
     */
    public static function fromTable(string $name, DBColumn $morphColumn, DBTable $endTable, Model $model)
    {
        $me                     = new self($model->name);
        $me->_relations[]       = $endTable->fqdn;
        $me->data_types[]       = $endTable->class_name.'[]';
        $me->data_types[]       = basename(Collection::class);
        $me->name               = function_name_plural($endTable->name);
        $me->phpdoc_description = "* MorphToMany method to query {$endTable->class_name}";
        $me->keys               = array_merge([$name], relation_keys([\Str::plural($name), $morphColumn->table_name,], ["{$name}_id"], [$endTable->foreign_column_name, $morphColumn->name]));
        $me->addImport(Collection::class);
        $me->autoload();

        return $model->setFunction($me);
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}