<?php
/**
 * @author       bangujo ON 2021-04-24 14:03
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile HasOne.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\HasOne as LaravelHasOne;

/**
 * Class HasOne
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class HasOne extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelHasOne::class, $model_class); }

    public static function fromForeignKey(DBForeignConstraint $foreignKey, $model_class)
    {
        $name                   = $foreignKey->table->relation_name_singular;
        $me                     = new self($model_class);
        $me->_relations[]       = $foreignKey->table->fqdn;
        $me->data_types[]       = $foreignKey->table->class_name;
        $me->is_nullable        = $foreignKey->column->is_nullable;
        $me->name               = $name;
        $me->phpdoc_description = "* Relation method to get {$foreignKey->table->class_name} referenced by {$foreignKey->referenced_column_name}[{$foreignKey->column_name}]";
        $me->keyRelations($foreignKey);
        $me->autoload();

        return $me;
    }

    public function keyRelations($source)
    {
        if (!is_object($source)) {
            return ;
        }
        switch (get_class($source)) {
            case DBForeignConstraint::class:
                $this->keys=  relation_keys([$source->table->foreign_column_name, $source->column_name],[Config::LARAVEL_PRIMARY_KEY,$source->referenced_column_name]);
                break;
        }
    }
}