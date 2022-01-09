<?php
/**
 * @author       bangujo ON 2021-04-24 19:35
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile hasMany.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany as LaravelHasMany;

/**
 * Class hasMany
 *
 * @package Angujo\Elolara\Model\Relations
 */
class HasMany extends RelationshipFunction
{
    public function __construct(string $modelClass) { parent::__construct(LaravelHasMany::class, $modelClass); }

    public static function fromForeignKey(DBForeignConstraint $foreignKey, Model $model)
    {
        // if (0===strcasecmp('test',$foreignKey->referenced_table_name)) dd($foreignKey);
        $name = $foreignKey->column->comment->ref ??
                $foreignKey->column->comment->reference ??
                $foreignKey->column->comment->name ??
                $foreignKey->table->relation_name_plural;
        /*if ($foreignKey->column->comment->name &&
            1 === preg_match('/\$\{([a-zA-Z0-9_]+)\}/', $foreignKey->column->comment, $matches)) {
            $name = function_name_plural($matches[1]);
        }
        else*/
        if ($model->functionExist($name)) {
            $name =
                function_name_plural(\Str::singular(preg_replace('/([a-zA-Z0-9_]+)_id$/', '$1', $foreignKey->column_name)) .
                                     '_' . $foreignKey->table_name);
        }
        $me               = new self($model->name);
        $me->name         = $name;
        $me->_relations[] = $foreignKey->table->fqdn;
        $model->addImport($foreignKey->table->fqdn);
        $me->data_types[]       = $foreignKey->table->class_name . '[]';
        $me->data_types[]       = basename(Collection::class);
        $me->phpdoc_description =
            "* Relation method to get all {$foreignKey->table->class_name} referenced by {$foreignKey->referenced_column_name}[{$foreignKey->column_name}]";
        $me->keyRelations($foreignKey);
        $me->addImport(Collection::class);
        $me->autoload();

        return $model->setFunction($me);
    }

    public function keyRelations($source)
    {
        if (!is_object($source)) {
            return;
        }
        switch (get_class($source)) {
            case DBForeignConstraint::class:
                $this->keys =
                    relation_keys([$source->table->foreign_column_name, $source->column_name], [Config::LARAVEL_PRIMARY_KEY, $source->referenced_column_name]);
                break;
        }
    }
}