<?php
/**
 * @author       bangujo ON 2021-04-12 14:07
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile DBTable.php
 */

namespace Angujo\Elolara\Database;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\Traits\BaseDBClass;
use Angujo\Elolara\Database\Traits\HasComment;
use Angujo\Elolara\Database\Traits\HasName;

/**
 * Class DBTable
 *
 * @package Angujo\Elolara\Database
 *
 * @property string                      $name
 * @property boolean                     $is_pivot
 * @property boolean                     $has_pivot
 * @property string                      $foreign_column_name
 * @property string[]                    $foreign_column_names
 * @property string[]                    $one_through
 * @property string[]                    $many_through
 * @property string                      $class_name
 * @property string                      $relation_name_singular
 * @property string                      $relation_name_plural
 * @property string                      $fqdn
 * @property string                      $class_name_key
 * @property string                      $pivot_table_name
 * @property string                      $pivot_end_table_name
 * @property string                      $comment
 * @property DBTable|null                $pivot_table
 * @property DBTable|null                $pivot_end_table
 * @property string[]|array              $pivot_table_names
 * @property DBTable[]|array             $pivot_tables
 * @property DBColumn[]|array            $columns
 * @property DBColumn[]|array            $primary_columns
 * @property DBUniqueConstraint[]|array  $unique_constraints
 * @property DBForeignConstraint[]|array $foreign_keys
 * @property DBForeignConstraint[]|array $referencing_foreign_keys
 * @property DBColumn|null               $primary_column
 * @property array                       $morph_tos
 * @property array                       $morph_manys
 */
class DBTable extends BaseDBClass
{
    /** @var DatabaseSchema */
    private $db;

    public function __construct(DatabaseSchema $database, $values = [])
    {
        $this->db = $database;
        parent::__construct($values);
        $this->_props['one_through'] =
        $this->_props['many_through'] = [];
    }

    protected function relation_name_singular()
    {
        return function_name_single($this->name);
    }

    protected function relation_name_plural()
    {
        return function_name_plural($this->name);
    }

    protected function fqdn()
    {
        return Config::models_namespace().'\\'.class_name($this->name);
    }

    protected function class_name()
    {
        return class_name($this->name);
    }

    protected function class_name_key()
    {
        return $this->class_name.'::class';
    }

    protected function foreign_column_name()
    {
        return strtolower(\Str::singular(\Str::snake($this->name)).'_'.Config::LARAVEL_PRIMARY_KEY);
    }

    protected function foreign_column_names()
    {
        return [strtolower(\Str::singular(\Str::snake($this->name)).'_'.Config::LARAVEL_PRIMARY_KEY), strtolower(\Str::plural(\Str::snake($this->name)).'_'.Config::LARAVEL_PRIMARY_KEY)];
    }

    /**
     * @return DBColumn[]|array
     */
    protected function columns()
    {
        return $this->db->getColumn($this->name);
    }

    protected function unique_constraints()
    {
        return $this->db->getUniqueConstraint($this->name);
    }

    protected function foreign_keys()
    {
        return $this->db->getForeignKey($this->name);
    }

    protected function referencing_foreign_keys()
    {
        return $this->db->getReferencingForeignKeys($this->name);
    }

    protected function primary_columns()
    {
        return array_filter($this->columns, function(DBColumn $col){ return $col->is_primary; });
    }

    protected function primary_column()
    {
        return 1 < count($this->primary_columns) ? null : \Arr::first($this->primary_columns);
    }

    protected function morph_tos()
    {
        return array_map(function($ts){ return array_map([$this->db, 'getTable'], $ts); }, $this->_props['morphs']['to'] ?? []);
    }

    protected function morph_manys()
    {
        return array_map([$this->db, 'getTable'], $this->_props['morphs']['many'] ?? []);
    }

    public function nullableMorphTo($name)
    {
        return $this->db->getColumn($this->name, "{$name}_id")->is_nullable && $this->db->getColumn($this->name, "{$name}_type")->is_nullable;
    }

    public function uniqueMorph($name)
    {
        $type = "{$name}_type";
        $id   = "{$name}_id";
        return !empty(array_filter($this->unique_constraints, function($c) use ($type, $id){ return 2 === count(array_intersect([$id, $type], $c->column_names)); })) ||
            ($this->db->getColumn($this->name, $type)->is_unique && $this->db->getColumn($this->name, $id)->is_unique);
    }

    protected function pivot_tables()
    {
        return $this->is_pivot ? array_combine($this->_props['pivot_table_names'], array_map(function($name){ return $this->db->getTable($name); }, $this->_props['pivot_table_names'])) : [];
    }

    protected function pivot_table()
    {
        return $this->has_pivot ? $this->db->getTable($this->pivot_table_name) : null;
    }

    protected function pivot_end_table()
    {
        return $this->has_pivot ? $this->db->getTable($this->pivot_end_table_name) : null;
    }

    public function relationColumn(DBTable $endTable)
    {
        /** @var DBForeignConstraint $fk */
        if ($fk = \Arr::first($this->foreign_keys, function(DBForeignConstraint $fk) use ($endTable){ return 0 === strcasecmp($fk->referenced_table_name, $endTable->name); })) return $fk->column;
        return $this->columns["{$this->name}.{$endTable->foreign_column_name}"] ?? \Arr::first($this->columns, function(DBColumn $column) use ($endTable){ return in_array($column->name, $endTable->foreign_column_names); });
    }

    /**
     * @param string $table_name
     *
     * @return DBColumn|null
     */
    public function pivotedColumn(string $table_name)
    {
        if (!$this->is_pivot || !($table = $this->pivot_tables[$table_name] ?? null)) {
            return null;
        }
        /** @var DBForeignConstraint|null $fk */
        if ($fk = \Arr::first($this->foreign_keys, function(DBForeignConstraint $constraint) use ($table_name){ return 0 === strcasecmp($constraint->referenced_table_name, $table_name); })) {
            return $fk->column;
        }
        return \Arr::first($this->columns, function(DBColumn $column) use ($table){ return 0 === strcasecmp($column->name, $table->foreign_column_name); });
    }

    public function setIsPivot(array $combinations)
    {
        $this->_props['is_pivot']          = true;
        $this->_props['pivot_table_names'] = $combinations;
        return $this;
    }

    public function setEndPivot(string $pivot_table_name, string $pivot_end_table_name)
    {
        $this->_props['has_pivot']            = true;
        $this->_props['pivot_table_name']     = $pivot_table_name;
        $this->_props['pivot_end_table_name'] = $pivot_end_table_name;
        return $this;
    }

    public function setMorph(array $morph)
    {
        $this->_props['morphs'] = $morph[$this->name] ?? [];
        return $this;
    }

    public function setOneThrough(array $throughs)
    {
        if (empty($throughs[$this->name])) return $this;
        $this->_props['one_through'] = array_map(function(array $ts){ return array_map([$this->db, 'getTable'], $ts); }, $throughs[$this->name] ?? []);
        return $this;
    }

    public function setManyThrough(array $throughs)
    {
        if (empty($throughs[$this->name])) return $this;
        //  print_r($throughs[$this->name]);
        $this->_props['many_through'] = array_map(function(array $ts){ return array_map([$this->db, 'getTable'], $ts); }, $throughs[$this->name] ?? []);
        return $this;
    }
}