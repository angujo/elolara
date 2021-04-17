<?php
/**
 * @author       bangujo ON 2021-04-17 03:26
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile DBMS.php
 */

namespace Angujo\LaravelModel\Database;


use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\JoinClause;

/**
 * Class DBMS
 *
 * @package Angujo\LaravelModel\Database
 */
class DBMS
{
    /** @var DatabaseSchema */
    protected $schema;
    /** @var ConnectionInterface */
    protected $connection;
    /** @var string  */
    protected $driver;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->schema     = new DatabaseSchema($connection->getDatabaseName());
        $this->driver     = config('database.connections.'.$this->connection->getName().'.driver');
    }

    public function getTables()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.TABLES')
                                         ->select('TABLE_NAME as name', 'TABLE_COMMENT as comment', 'TABLE_TYPE as type')
                                         ->where('TABLE_SCHEMA', $this->schema->name)
                                         ->get()->toArray();
                break;
        }
        $list = array_map(function($row){ return new DBTable($this->schema, $row); }, $list);
        $this->schema->setTables(array_combine(array_map(function(DBTable $t){ return $t->name; }, $list), $list));
    }

    public function getColumns()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.COLUMNS')
                                         ->select('TABLE_NAME', 'COLUMN_NAME as name', 'ORDINAL_POSITION as ordinal', 'COLUMN_DEFAULT as default',
                                                  'IS_NULLABLE as nullable', 'DATA_TYPE as type', 'CHARACTER_MAXIMUM_LENGTH as character_length', 'COLUMN_TYPE',
                                                  'COLUMN_KEY', 'EXTRA', 'COLUMN_COMMENT as comment')
                                         ->where('TABLE_SCHEMA', $this->schema->name)
                                         ->orderBy('column_name')
                                         ->get()->toArray();
                break;
        }
        $list = array_map(function($row){ return new DBColumn($this->schema, $row); }, $list);
        $this->schema->setColumns(array_combine(array_map(function(DBColumn $c){ return strtolower("{$c->table_name}.{$c->name}"); }, $list), $list));
    }

    public function getPrimaryConstraints()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.TABLE_CONSTRAINTS as tc')
                                         ->select('tc.CONSTRAINT_NAME as name', 'tc.TABLE_NAME')
                                         ->selectRaw("group_concat(kcu.COLUMN_NAME separator ', ') as column_names")
                                         ->join('information_schema.KEY_COLUMN_USAGE as kcu', function(JoinClause $join){
                                             $join->on('kcu.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                                                  ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                                                  ->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
                                         })
                                         ->where('tc.CONSTRAINT_TYPE', 'PRIMARY KEY')
                                         ->where('tc.TABLE_SCHEMA', $this->schema->name)
                                         ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME'])
                                         ->get()->toArray();
                break;
        }
        $list = array_map(function($row){ return new DBPrimaryConstraint($this->schema, $row); }, $list);
        $this->schema->setPrimaryConstraints(array_combine(array_map(function(DBPrimaryConstraint $c){ return strtolower("{$c->table_name}.{$c->name}"); }, $list), $list));
    }

    public function getUniqueConstraints()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.TABLE_CONSTRAINTS as tc')
                                         ->select('tc.CONSTRAINT_NAME as name', 'tc.TABLE_NAME')
                                         ->selectRaw("group_concat(kcu.COLUMN_NAME separator ', ') as column_names")
                                         ->join('information_schema.KEY_COLUMN_USAGE as kcu', function(JoinClause $join){
                                             $join->on('kcu.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                                                  ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                                                  ->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
                                         })
                                         ->where('tc.CONSTRAINT_TYPE', 'UNIQUE')
                                         ->where('tc.TABLE_SCHEMA', $this->schema->name)
                                         ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME'])
                                         ->get()->toArray();
                break;
        }
        $list = array_map(function($row){ return new DBUniqueConstraint($this->schema, $row); }, $list);
        $this->schema->setUniqueConstraints(array_combine(array_map(function(DBUniqueConstraint $c){ return strtolower("{$c->table_name}.{$c->name}"); }, $list), $list));
    }

    public function getForeignConstraints()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.TABLE_CONSTRAINTS as tc')
                                         ->select('tc.CONSTRAINT_NAME as name', 'tc.TABLE_NAME', 'kcu.COLUMN_NAME',
                                                  'kcu.REFERENCED_TABLE_NAME', 'kcu.REFERENCED_COLUMN_NAME')
                                         ->join('information_schema.KEY_COLUMN_USAGE as kcu', function(JoinClause $join){
                                             $join->on('kcu.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                                                  ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                                                  ->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
                                         })
                                         ->where('tc.CONSTRAINT_TYPE', 'FOREIGN KEY')
                                         ->where('tc.TABLE_SCHEMA', $this->schema->name)
                    // ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME'])
                                         ->get()->toArray();
                break;
        }
        $list = array_map(function($row){ return new DBForeignConstraint($this->schema, $row); }, $list);
        $this->schema->setForeignConstraints(array_combine(array_map(function(DBForeignConstraint $c){ return strtolower("{$c->table_name}.{$c->name}"); }, $list), $list));
    }
}