<?php
/**
 * @author       bangujo ON 2021-04-24 19:55
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile BelongsToMany.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as LaravelBelongsToMany;

/**
 * Class BelongsToMany
 *
 * @package Angujo\Elolara\Model\Relations
 */
class BelongsToMany extends RelationshipFunction
{
    public function __construct($modelClass){ parent::__construct(LaravelBelongsToMany::class, $modelClass); }

    public static function fromTable(DBTable $table, DBTable $pivotTable, DBTable $endTable, Model $model)
    {

        $name             = $endTable->relation_name_plural;
        $me               = new self($model->name);
        $me->_relations[] = $endTable->fqdn;
        $me->data_types[] = $endTable->class_name.'[]';
        $me->data_types[] = basename(Collection::class);
        // $me->is_nullable        = $foreignKey->column->is_nullable;
        $me->name               = $name;
        $me->phpdoc_description = "* Relation method to get {$endTable->class_name}";
        $me->addImport(Collection::class);
        $me->keyRelations_($table, $pivotTable, $endTable);
        $me->pivotName($pivotTable);
        $me->pivotColumns($table, $pivotTable, $endTable);
        $me->autoload();

        return $model->setFunction($me);
    }

    /**
     * @inheritDoc
     */
    function keyRelations_($source, DBTable $pivotTable, DBTable $endTable)
    {
        $names = [$source->name, $endTable->name];
        sort($names);
        $f = \Str::singular(array_pop($names));
        array_unshift($names, $f);
        $source_column = $pivotTable->pivotedColumn($source->name);
        $pivot_column  = $pivotTable->pivotedColumn($endTable->name);
        $this->keys    = relation_keys(
            [implode('_', $names), $pivotTable->name],
            [$source->foreign_column_name, $source_column->name],
            [$endTable->foreign_column_name, $pivot_column->name]
        //TODO Research and append more on  $parentKey = null, $relatedKey = null, $relation = null to append here
        // @see @vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasRelationships.php
        );

    }

    public function pivotColumns(DBTable $source, DBTable $pivotTable, DBTable $endTable)
    {
        $source_column = $pivotTable->pivotedColumn($source->name);
        $pivot_column  = $pivotTable->pivotedColumn($endTable->name);
        $cols          = array_map(function(DBColumn $column){ return $column->name; },
            array_filter($pivotTable->columns, function(DBColumn $c) use ($source_column, $pivot_column){ return !in_array($c->name, [$pivot_column->name, $source_column->name]); }));
        if (empty($cols)) {
            return;
        }
        $timestamps = array_intersect(Config::timestampColumnNames(), $cols);
        if (2 === count($timestamps)) {
            $cols             = array_diff($cols, $timestamps);
            $this->rel_extend .= '->withTimestamps()';
        }
        $this->rel_extend .= '->withPivot('.implode(', ', array_map(function($cn){ return var_export($cn, true); }, $cols)).')';
    }

    public function pivotName(DBTable $pivot_table)
    {
        if (Config::pivot_name_regex() && $pivot_table->comment && ($name = preg_replace('/'.Config::pivot_name_regex().'/', '$1', $pivot_table->comment))) {
            $this->rel_extend .= '->as('.var_export($name, true).')';
        }
    }

    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}