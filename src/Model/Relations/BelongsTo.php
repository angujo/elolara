<?php
/**
 * @author       bangujo ON 2021-04-24 18:01
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile BelongsTo.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\BelongsTo as LaravelBelongsTo;

/**
 * Class BelongsTo
 *
 * @package Angujo\Elolara\Model\Relations
 */
class BelongsTo extends RelationshipFunction
{

    public function __construct(string $model_class){ parent::__construct(LaravelBelongsTo::class, $model_class); }

    public static function fromForeignKey(DBForeignConstraint $foreignKey, Model $model)
    {
        if (!($name = self::relationName($foreignKey))) {
            return null;
        }
        if (0 === strcasecmp(class_name($name), class_name($foreignKey->table->name))) {
            $name = $foreignKey->column->relation_name_singular;
        }
        $me                     = new self($model->name);
        $me->_relations[]       = $foreignKey->referenced_table->fqdn;
        $me->data_types[]       = $foreignKey->referenced_table->class_name;
        $me->is_nullable        = $foreignKey->column->is_nullable;
        $me->name               = $name;
        $me->phpdoc_description = "* Relation method to get {$foreignKey->referenced_table->class_name} referenced by {$foreignKey->column_name}";
        $me->keyRelations($foreignKey);
        $me->addImport(...$me->_relations);
        $me->autoload();

        return $model->setFunction($me);
    }

    public static function fromColumn(DBColumn $column, Model $model)
    {
        $name = $column->relation_name_singular;
        $me   = new self($model->name);

        $me->_relations[]       = $column->probable_table->fqdn;
        $me->data_types[]       = $column->probable_table->class_name;
        $me->is_nullable        = $column->is_nullable;
        $me->name               = $name;
        $me->phpdoc_description = "* Relation method to get {$column->probable_table->class_name} referenced by {$column->name}\n* Probable Relation";
        $me->keyRelations($column);
        $me->autoload();
        $model->addImport(...$me->imports());
        $model->setPhpDocProperty($me);

        return $model->setFunction($me);;
    }


    public function keyRelations($source)
    {
        if (!is_object($source)) {
            return;
        }
        switch (get_class($source)) {
            case DBForeignConstraint::class:
                $this->keys = relation_keys([Config::LARAVEL_PRIMARY_KEY, $source->referenced_column_name], [$source->referenced_table->foreign_column_name, $source->column_name]);
                break;
            case DBColumn::class:
                $this->keys = relation_keys([Config::LARAVEL_PRIMARY_KEY, $source->probable_table->primary_column->name], [$source->probable_table->foreign_column_name, $source->name]);
                break;
        }
    }
}