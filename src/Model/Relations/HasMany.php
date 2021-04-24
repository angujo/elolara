<?php
/**
 * @author       bangujo ON 2021-04-24 19:35
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile hasMany.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany as LaravelHasMany;

/**
 * Class hasMany
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class HasMany extends RelationshipFunction
{
    public function __construct($modelClass){ parent::__construct(LaravelHasMany::class, $modelClass); }

    public static function fromForeignKey(DBForeignConstraint $foreignKey, $model_class)
    {
        $name                   = $foreignKey->table->relation_name_plural;
        $me                     = new self($model_class);
        $me->_relations[]       = $foreignKey->table->fqdn;
        $me->data_types[]       = $foreignKey->table->class_name.'[]';
        $me->data_types[]       = basename(Collection::class);
        $me->name               = $name;
        $me->phpdoc_description = "* Relation method to get all {$foreignKey->table->class_name} referenced by {$foreignKey->referenced_column_name}[{$foreignKey->column_name}]";
        $me->keyRelations($foreignKey);
        $me->addImport(Collection::class);
        $me->autoload();

        return $me;
    }

    public function keyRelations($source)
    {
        if (!is_object($source)) {
            return;
        }
        switch (get_class($source)) {
            case DBForeignConstraint::class:
                $this->keys = relation_keys([$source->table->foreign_column_name, $source->column_name], [Config::LARAVEL_PRIMARY_KEY, $source->referenced_column_name]);
                break;
        }
    }
}