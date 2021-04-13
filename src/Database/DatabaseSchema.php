<?php
/**
 * @author       bangujo ON 2021-04-12 14:12
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Database.php
 */

namespace Angujo\LaravelModel\Database;


use Angujo\LaravelModel\Database\Traits\BaseDBClass;
use Angujo\LaravelModel\Database\Traits\HasName;
use Illuminate\Database\Connection;

/**
 * Class Database
 *
 * @package Angujo\LaravelModel\Database
 *
 * @property string               $name
 * @property array|DBTable[]      $tables
 * @property array|DBColumn[]     $columns
 * @property array|DBForeignKey[] $foreign_keys
 */
class DatabaseSchema extends BaseDBClass
{
    protected $connection;
    protected $driver;

    public function __construct(Connection $connection)
    {
        parent::__construct(['name' => $connection->getDatabaseName()]);
        $this->connection = $connection;
        $this->driver     = config('database.connections.'.$this->connection->getName().'.driver');
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

    /**
     * @param      $table_name
     * @param null $name
     *
     * @return DBColumn|DBColumn[]|array|null
     */
    public function getColumn($table_name, $name = null)
    {
        if (is_null($name) || !is_string($name)) {
            return array_filter($this->columns, function($key) use ($table_name){ return 0 === stripos($key, "{$table_name}."); }, ARRAY_FILTER_USE_KEY);
        }
        return $this->columns["{$table_name}.{$name}"] ?? null;
    }

    public function getTables()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.TABLES')
                                         ->select('TABLE_NAME `name`', 'TABLE_COMMENT `comment`', 'TABLE_TYPE `type`')
                                         ->where('TABLE_SCHEMA', $this->name)
                                         ->get();
                break;
        }
        $list = array_map(function($row){ return new DBTable($this, $row); }, $list);
        $this->_setProp('tables', array_combine(array_map(function(DBTable $t){ return $t->name; }, $list), $list));
    }

    public function getColumns()
    {
        $list = [];
        switch ($this->driver) {
            case 'mysql':
                $list = $this->connection->table('information_schema.COLUMNS')
                                         ->select('TABLE_NAME', 'COLUMN_NAME `name`', 'ORDINAL_POSITION `ordinal`', 'COLUMN_DEFAULT `default`',
                                                  'IS_NULLABLE `nullable`', 'DATA_TYPE `type`', 'CHARACTER_MAXIMUM_LENGTH `character_length`', 'COLUMN_TYPE',
                                                  'COLUMN_KEY', 'EXTRA', 'COLUMN_COMMENT `comment`')
                                         ->where('TABLE_SCHEMA', $this->name)
                                         ->orderBy('column_name')
                                         ->get();
                break;
        }
        $list = array_map(function($row){ return new DBColumn($this, $row); }, $list);
        $this->_setProp('columns', array_combine(array_map(function(DBColumn $c){ return "{$c->table_name}.{$c->name}"; }, $list), $list));
    }
}