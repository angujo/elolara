<?php
/**
 * @author       bangujo ON 2021-04-27 18:25
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile MorphToMany.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Angujo\Elolara\Ankurk91\Relations\MorphToOneRel as Ankurk91MorphToOne;

/**
 * Class MorphToMany
 *
 * @package Angujo\Elolara\Model\Relations
 */
class MorphToOne extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(Ankurk91MorphToOne::class, $model_class); }

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
        $me->data_types[]       = $endTable->class_name;
        $me->is_nullable        = $morphColumn->is_nullable;
        $me->name               = function_name_single($endTable->name);
        $me->phpdoc_description = "* MorphToOne method to query {$endTable->class_name}";
        $me->keys               = array_merge([$name], relation_keys([\Str::plural($name), $morphColumn->table_name,], ["{$name}_id"], [$endTable->foreign_column_name, $morphColumn->name]));
        $me->autoload();
        $model->addTrait(\Angujo\Elolara\Ankurk91\MorphToOne::class);

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