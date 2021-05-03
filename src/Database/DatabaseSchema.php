<?php
/**
 * @author       bangujo ON 2021-04-12 14:12
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Database.php
 */

namespace Angujo\Elolara\Database;


use Angujo\Elolara\Database\Traits\BaseDBClass;
use Angujo\Elolara\Database\Traits\HasName;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\JoinClause;

/**
 * Class Database
 *
 * @package Angujo\Elolara\Database
 *
 * @property string                      $name
 * @property array|DBTable[]             $tables
 * @property array|DBColumn[]            $columns
 * @property array|DBPrimaryConstraint[] $primary_constraints
 * @property array|DBUniqueConstraint[]  $unique_constraints
 * @property array|DBForeignConstraint[] $foreign_constraints
 */
class DatabaseSchema extends BaseDBClass
{
    public $excluded_tables = [];
    public $only_tables     = [];

    public function __construct($name)
    {
        parent::__construct(['name' => $name]);
    }

    public function getUniqueConstraint(string $table_name, string $name = null, string $column_name = null)
    {
        if (!$column_name && !$name) {
            return array_filter($this->unique_constraints, function(DBUniqueConstraint $foreign) use ($table_name){ return 0 === strcasecmp($table_name, $foreign->table_name); });
        }
        if ($column_name && is_string($column_name)) {
            return array_filter($this->unique_constraints, function(DBUniqueConstraint $foreign) use ($table_name, $column_name){ return 0 === strcasecmp($table_name, $foreign->table_name) && in_array($column_name, $foreign->column_names); });
        }
        return $this->unique_constraints["{$table_name}.{$name}"] ?? null;
    }

    public function getPrimaryConstraint(string $table_name, string $name = null, string $column_name = null)
    {
        if (!$column_name && !$name) {
            return array_filter($this->primary_constraints, function(DBPrimaryConstraint $foreign) use ($table_name){ return 0 === strcasecmp($table_name, $foreign->table_name); });
        }
        if ($column_name) {
            return array_filter($this->primary_constraints, function(DBPrimaryConstraint $foreign) use ($table_name, $column_name){ return 0 === strcasecmp($table_name, $foreign->table_name) && in_array($column_name, $foreign->column_names); });
        }
        return $this->primary_constraints["{$table_name}.{$name}"] ?? null;
    }

    public function getReferencingForeignKeys(string $table_name, string $column_name = null)
    {
        if ($column_name && is_string($column_name)) {
            return array_filter($this->foreign_constraints, function(DBForeignConstraint $foreign) use ($table_name, $column_name){ return 0 === strcasecmp($table_name, $foreign->referenced_table_name) && 0 === strcasecmp($column_name, $foreign->column_name); });
        }
        return array_filter($this->foreign_constraints, function(DBForeignConstraint $foreign) use ($table_name, $column_name){ return 0 === strcasecmp($table_name, $foreign->referenced_table_name); });
    }

    /**
     * @param string|null $table_name
     * @param string|null $name
     * @param string|null $column_name
     *
     * @return DBForeignConstraint|DBForeignConstraint[]|array|null
     */
    public function getForeignKey(string $table_name, ?string $name = null, ?string $column_name = null)
    {
        if (!$column_name && (!$name || !is_string($name))) {
            return array_filter($this->foreign_constraints, function(DBForeignConstraint $foreign) use ($table_name){ return 0 === strcasecmp($table_name, $foreign->table_name); });
        }
        if ($column_name && is_string($column_name)) {
            return \Arr::first($this->foreign_constraints, function(DBForeignConstraint $foreign) use ($table_name, $column_name){ return 0 === strcasecmp($table_name, $foreign->table_name) && 0 === strcasecmp($column_name, $foreign->column_name); });
        }
        return $this->foreign_constraints["{$table_name}.{$name}"] ?? null;
    }

    /**
     * @param $name
     *
     * @return DBTable|null
     */
    public function getTable($name)
    {
        return $this->tables[$name] ?? null;
    }

    public function getRelatableTable(string $search_name, ?string $column_name)
    {
        if (!trim($search_name)) {
            return null;
        }
        return \Arr::first($this->tables, function(DBTable $t) use ($search_name, $column_name){
            return $t->primary_column && in_array($t->name, [\Str::singular($search_name), \Str::plural($search_name)]) &&
                !(\Arr::first($t->foreign_keys, function(DBForeignConstraint $fk) use ($column_name){ return in_array($column_name, [$fk->column_name, $fk->referenced_column_name]); }));
        }) ?: null;
    }

    /**
     * @param                            $table_name
     * @param null|string[]|array|string $name
     *
     * @return DBColumn|DBColumn[]|array|null
     */
    public function getColumn($table_name, $name = null)
    {
        if (is_null($name) || empty($name) || !is_string($name)) {
            return array_filter($this->columns, function($key) use ($table_name){ return 0 === stripos($key, "{$table_name}."); }, ARRAY_FILTER_USE_KEY);
        }
        if (is_array($name)) {
            return array_filter($this->columns, function($key) use ($table_name, $name){ return in_array($key, array_map(function($n) use ($table_name){ return "{$table_name}.{$n}"; }, $name)); }, ARRAY_FILTER_USE_KEY);
        }
        return $this->columns["{$table_name}.{$name}"] ?? null;
    }

    public function setTables(array $data)
    {
        $this->_setProp('tables', $data);
        return $this;
    }

    public function setColumns(array $data)
    {
        $this->_setProp('columns', $data);
        return $this;
    }

    public function setPrimaryConstraints(array $data)
    {
        $this->_setProp('primary_constraints', $data);
        return $this;
    }

    public function setUniqueConstraints(array $data)
    {
        $this->_setProp('unique_constraints', $data);
        return $this;
    }

    public function setForeignConstraints(array $data)
    {
        $this->_setProp('foreign_constraints', $data);
        return $this;
    }

    public function setExcludedTables(string ...$tables)
    {
        $this->excluded_tables = array_unique(array_filter(array_merge($this->excluded_tables, func_get_args()), 'is_string'));
        return $this;
    }

    public function setOnlyTables(string ...$tables)
    {
        $this->only_tables = array_unique(array_filter(array_merge($this->only_tables, func_get_args()), 'is_string'));
        return $this;
    }
}