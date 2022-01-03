<?php
/**
 * @author       bangujo ON 2021-04-17 03:26
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile DBMS.php
 */

namespace Angujo\Elolara\Database;


use Angujo\Elolara\Database\Traits\DBDriver;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Class DBMS
 *
 * @package Angujo\Elolara\Database
 */
class DBMS
{
    /** @var DatabaseSchema */
    protected $schema;
    /** @var ConnectionInterface */
    protected $connection;
    /** @var string */
    protected $driver;

    protected $only_tables = [];
    protected $exclude_tables = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->driver = config('database.connections.' . $this->connection->getName() . '.driver');
        $this->schema = new DatabaseSchema($this->schemaName());
    }

    protected function schemaName()
    {
        switch ($this->driver) {
            case DBDriver::PGSQL:
                return config("database.connections.{$this->connection->getName()}.schema");
            default:
                return $this->connection->getDatabaseName();
        }
    }

    /**
     * @return DatabaseSchema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function loadSchema()
    {
        $this->only_tables = array_diff($this->schema->only_tables, $this->exclude_tables);
        $this->exclude_tables = $this->schema->excluded_tables;

        $this->getTables();
        $this->getUniqueConstraints();
        $this->getForeignConstraints();
        $this->getPrimaryConstraints();
        $this->getColumns();
        return $this->schema;
    }

    public function getTables()
    {
        /** @var Builder $query */
        $query = null;
        switch ($this->driver) {
            case DBDriver::MYSQL:
                $query = $this->connection->table('information_schema.TABLES')
                    ->select('TABLE_NAME as name', 'TABLE_COMMENT as comment', 'TABLE_TYPE as type')
                    ->where('TABLE_SCHEMA', $this->schema->name);
                if (filled($this->exclude_tables)) $query->whereNotIn('TABLE_NAME', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('TABLE_NAME', $this->only_tables);
                break;
            case DBDriver::PGSQL:
                $query = $this->connection->table('information_schema.tables')
                    ->select('table_name as name', 'table_type as type')
                    ->selectRaw('pg_catalog.obj_description(pc."oid",\'pg_class\') comment')
                    ->join('pg_catalog.pg_class as pc', 'relname', '=', 'table_name')
                    ->where('table_catalog', $this->connection->getDatabaseName())
                    ->where('table_schema', $this->schema->name);
                if (filled($this->exclude_tables)) $query->whereNotIn('table_name', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('table_name', $this->only_tables);
                break;
            default:
                throw new \Exception("Invalid connection driver: {$this->driver}");
        }
        $list = $query->get()->toArray();
        $list = array_map(function ($row) {
            return new DBTable($this->schema, $row);
        }, $list);
        $this->schema->setTables(array_combine(array_map(function (DBTable $t) {
            return $t->name;
        }, $list), $list));
    }

    public function getColumns()
    {
        $list = [];
        switch ($this->driver) {
            case DBDriver::MYSQL:
                $query = $this->connection->table('information_schema.COLUMNS')
                    ->select('TABLE_NAME', 'COLUMN_NAME as name', 'ORDINAL_POSITION as ordinal', 'COLUMN_DEFAULT as default',
                        'IS_NULLABLE as nullable', 'DATA_TYPE as type', 'CHARACTER_MAXIMUM_LENGTH as character_length', 'COLUMN_TYPE',
                        'COLUMN_KEY', 'EXTRA', 'COLUMN_COMMENT as comment')
                    ->where('TABLE_SCHEMA', $this->schema->name)
                    ->orderBy('column_name');
                if (filled($this->exclude_tables)) $query->whereNotIn('TABLE_NAME', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('TABLE_NAME', $this->only_tables);
                $list = $query->get()->toArray();
                break;
            case DBDriver::PGSQL:
                $query = $this->connection->table('information_schema.columns as c')
                    ->select('table_name', 'column_name as name', 'ordinal_position as ordinal', 'column_default as default',
                        'is_nullable as nullable', 'c.data_type as type', 'character_maximum_length as character_length', 'c.udt_name as column_type')
                    ->selectRaw('pg_catalog.col_description(pc."oid",c.ordinal_position::int) as comment, null as COLUMN_KEY, ' .
                        '(case when pg_get_serial_sequence(c.table_name,c.column_name) is not null or s.sequence_name is not null then \'auto_increment\' else \'\' end) as EXTRA')
                    ->join('pg_catalog.pg_class as pc', 'relname', '=', 'table_name')
                    ->leftJoin('information_schema.sequences as s', function (JoinClause $join) {
                        $join->on('s.sequence_schema', '=', 'table_schema')
                            ->on(\DB::raw("(table_name||'_'||column_name||'_seq')"), "=", "s.sequence_name");
                    })
                    ->where('table_catalog', $this->connection->getDatabaseName())
                    ->where('table_schema', $this->schema->name)
                    ->orderBy('column_name');
                if (filled($this->exclude_tables)) $query->whereNotIn('table_name', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('table_name', $this->only_tables);
                $list = $query->get()->toArray();
                break;
        }
        $list = array_map(function ($row) {
            return new DBColumn($this->schema, $row);
        }, $list);
        $this->schema->setColumns(array_combine(array_map(function (DBColumn $c) {
            return strtolower("{$c->table_name}.{$c->name}");
        }, $list), $list));
    }

    public function getPrimaryConstraints()
    {
        $list = [];
        switch ($this->driver) {
            case DBDriver::MYSQL:
                $query = $this->connection->table('information_schema.TABLE_CONSTRAINTS as tc')
                    ->select('tc.CONSTRAINT_NAME as name', 'tc.TABLE_NAME')
                    ->selectRaw("group_concat(kcu.COLUMN_NAME separator ', ') as column_names")
                    ->join('information_schema.KEY_COLUMN_USAGE as kcu', function (JoinClause $join) {
                        $join->on('kcu.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                            ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                            ->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
                    })
                    ->where('tc.CONSTRAINT_TYPE', 'PRIMARY KEY')
                    ->where('tc.TABLE_SCHEMA', $this->schema->name)
                    ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME']);
                if (filled($this->exclude_tables)) $query->whereNotIn('tc.TABLE_NAME', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('tc.TABLE_NAME', $this->only_tables);
                $list = $query->get()->toArray();
                break;
            case DBDriver::PGSQL:
                $query = $this->connection->table('information_schema.table_constraints as tc')
                    ->select('tc.constraint_name as name', 'tc.table_name')
                    ->selectRaw("string_agg(kcu.column_name, ', ') as column_names")
                    ->join('information_schema.key_column_usage as kcu', function (JoinClause $join) {
                        $join->on('kcu.constraint_schema', '=', 'tc.constraint_schema')
                            ->on('tc.table_name', '=', 'kcu.table_name')
                            ->on('tc.constraint_name', '=', 'kcu.constraint_name');
                    })
                    ->where('tc.constraint_type', 'PRIMARY KEY')
                    ->where('tc.table_schema', $this->schema->name)
                    ->groupBy(['tc.table_name', 'tc.constraint_name']);
                if (filled($this->exclude_tables)) $query->whereNotIn('tc.table_name', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('tc.table_name', $this->only_tables);
                $list = $query->get()->toArray();
                break;
        }
        $list = array_map(function ($row) {
            return new DBPrimaryConstraint($this->schema, $row);
        }, $list);
        $this->schema->setPrimaryConstraints(array_combine(array_map(function (DBPrimaryConstraint $c) {
            return strtolower("{$c->table_name}.{$c->name}");
        }, $list), $list));
    }

    public function getUniqueConstraints()
    {
        $list = [];
        switch ($this->driver) {
            case DBDriver::MYSQL:
                $query = $this->connection->table('information_schema.TABLE_CONSTRAINTS as tc')
                    ->select('tc.CONSTRAINT_NAME as name', 'tc.TABLE_NAME')
                    ->selectRaw("group_concat(kcu.COLUMN_NAME separator ', ') as column_names")
                    ->join('information_schema.KEY_COLUMN_USAGE as kcu', function (JoinClause $join) {
                        $join->on('kcu.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                            ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                            ->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
                    })
                    ->where('tc.CONSTRAINT_TYPE', 'UNIQUE')
                    ->where('tc.TABLE_SCHEMA', $this->schema->name)
                    ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME']);
                if (filled($this->exclude_tables)) $query->whereNotIn('tc.TABLE_NAME', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('tc.TABLE_NAME', $this->only_tables);
                $list = $query->get()->toArray();
                break;
            case DBDriver::PGSQL:
                $query = $this->connection->table('information_schema.table_constraints as tc')
                    ->select('tc.constraint_name as name', 'tc.table_name')
                    ->selectRaw("string_agg(kcu.column_name, ', ') as column_names")
                    ->join('information_schema.key_column_usage as kcu', function (JoinClause $join) {
                        $join->on('kcu.constraint_schema', '=', 'tc.constraint_schema')
                            ->on('tc.table_name', '=', 'kcu.table_name')
                            ->on('tc.constraint_name', '=', 'kcu.constraint_name');
                    })
                    ->where('tc.constraint_type', 'UNIQUE')
                    ->where('tc.table_schema', $this->schema->name)
                    ->groupBy(['tc.table_name', 'tc.constraint_name']);
                if (filled($this->exclude_tables)) $query->whereNotIn('tc.table_name', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('tc.table_name', $this->only_tables);
                $list = $query->get()->toArray();
                break;
        }
        $list = array_map(function ($row) {
            return new DBUniqueConstraint($this->schema, $row);
        }, $list);
        $this->schema->setUniqueConstraints(array_combine(array_map(function (DBUniqueConstraint $c) {
            return strtolower("{$c->table_name}.{$c->name}");
        }, $list), $list));
    }

    public function getForeignConstraints()
    {
        $list = [];
        switch ($this->driver) {
            case DBDriver::MYSQL:
                $query = $this->connection->table('information_schema.TABLE_CONSTRAINTS as tc')
                    ->select('tc.CONSTRAINT_NAME as name', 'tc.TABLE_NAME', 'kcu.COLUMN_NAME',
                        'kcu.REFERENCED_TABLE_NAME', 'kcu.REFERENCED_COLUMN_NAME')
                    ->join('information_schema.KEY_COLUMN_USAGE as kcu', function (JoinClause $join) {
                        $join->on('kcu.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                            ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                            ->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
                    })
                    ->where('tc.CONSTRAINT_TYPE', 'FOREIGN KEY')
                    ->where('tc.TABLE_SCHEMA', $this->schema->name);
                if (filled($this->exclude_tables)) $query->whereNotIn('tc.TABLE_NAME', $this->exclude_tables)->whereNotIn('kcu.REFERENCED_TABLE_NAME', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('tc.TABLE_NAME', $this->only_tables)->whereIn('kcu.REFERENCED_TABLE_NAME', $this->only_tables);
                // ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME'])
                $list = $query->get()->toArray();
                break;
            case DBDriver::PGSQL:
                $query = $this->connection->table('pg_catalog.pg_constraint  as c')
                    ->select('ra.attnum as ordinal', 'rn.nspname as schema_name', 'rt.relname as table_name', 'ra.attname as column_name', 'c.conname as "name"' .
                        'n.nspname as referenced_schema_name', 't.relname as referenced_table_name', 'a.attname as referenced_column_name')
                    ->join('pg_catalog.pg_namespace as n', function (JoinClause $clause) {
                        $clause->on('n.oid', '=', 'c.connamespace')
                            ->where('n.nspname', $this->schema->name);
                    })
                    ->join('pg_catalog.pg_class as t', 't.oid', '=', 'c.conrelid')
                    ->join('pg_catalog.pg_attribute as a', function (JoinClause $clause) {
                        $clause->on('a.attrelid', '=', 't.oid')
                            ->on('a.attnum', '=', \DB::raw('any (c.conkey)'));
                    })
                    ->join('pg_catalog.pg_class as rt', 'rt.oid', '=', 'c.confrelid')
                    ->join('pg_catalog.pg_namespace as rn', function (JoinClause $clause) {
                        $clause->on('rn.oid', '=', 'rt.relnamespace')
                            ->where('rn.nspname', $this->schema->name);
                    })
                    ->join('pg_catalog.pg_attribute as ra', function (JoinClause $clause) {
                        $clause->on('ra.attrelid', '=', 'rt.oid')
                            ->on('ra.attnum', '=', \DB::raw('any (c.confkey)'));
                    })
                    ->join('pg_catalog.pg_constraint as uc', function (JoinClause $clause) {
                        $clause->on('rn.oid', '=', 'uc.connamespace')
                            ->on('ra.attnum', '=', \DB::raw('any (uc.conkey)'))
                            ->on('rt.oid', '=', 'uc.conrelid')
                            ->on('uc.contype', '=', \DB::raw('any (array[\'u\'::character,\'p\'::character])'));
                    });
                /*$query = $this->connection->table('information_schema.table_constraints as tc')
                    ->select('tc.constraint_name as name', 'tc.table_name', 'kcu.column_name',
                        'kcu.referenced_table_name', 'kcu.referenced_column_name')
                    ->join('information_schema.key_column_usage as kcu', function (JoinClause $join) {
                        $join->on('kcu.constraint_schema', '=', 'tc.constraint_schema')
                            ->on('tc.table_name', '=', 'kcu.table_name')
                            ->on('tc.constraint_name', '=', 'kcu.constraint_name');
                    })
                    ->where('tc.constraint_type', 'FOREIGN KEY')
                    ->where('tc.table_schema', $this->schema->name);*/
                if (filled($this->exclude_tables)) $query->whereNotIn('rt.relname', $this->exclude_tables)->whereNotIn('t.relname', $this->exclude_tables);
                if (filled($this->only_tables)) $query->whereIn('rt.relname', $this->only_tables)->whereIn('t.relname', $this->only_tables);
                // ->groupBy(['tc.TABLE_NAME', 'tc.CONSTRAINT_NAME'])
                $list = $query->get()->toArray();
                break;
        }
        $list = array_map(function ($row) {
            return new DBForeignConstraint($this->schema, $row);
        }, $list);
        $this->schema->setForeignConstraints(array_combine(array_map(function (DBForeignConstraint $c) {
            return strtolower("{$c->table_name}.{$c->name}");
        }, $list), $list));
    }
}