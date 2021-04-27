<?php
/**
 * @author       bangujo ON 2021-04-18 03:10
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Factory.php
 */

namespace Angujo\LaravelModel\Laravel;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DatabaseSchema;
use Angujo\LaravelModel\Database\DBMS;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Model;
use Illuminate\Database\ConnectionInterface;

/**
 * Class Factory
 *
 * @package Angujo\LaravelModel\Laravel
 */
class Factory
{
    /** @var ConnectionInterface */
    private $connection;
    private $con_name;

    public function __construct()
    {

    }

    /**
     * @param string $conn_name
     * @param string $db_name
     *
     * @return ConnectionInterface
     */
    public function setConnection(string $conn_name, string $db_name)
    {
        $config             = \Config::get('database.connections.'.$conn_name);
        $config['database'] = $db_name;
        config()->set('database.connections.'.$conn_name, $config);
        \DB::purge($conn_name);
        $this->con_name = $conn_name;
        return $this->connection = \DB::connection($conn_name);
    }

    public function runSchema()
    {
        $dbms   = new DBMS($this->connection);
        $schema = $dbms->loadSchema();
        $this->tablesPivotRelations($schema);
        $morphs = $this->tablesMorphRelations($schema);
        $tables = $schema->tables;
        foreach ($tables as $table) {
            $table->setMorph($morphs);
            $this->writeModel(Model::fromTable($table)->setConnection($this->con_name));
        }
    }

    /**
     * @param DatabaseSchema $schema
     *
     * @return array
     */
    private function tablesMorphRelations(DatabaseSchema $schema)
    {
        $tables = $schema->tables;
        $morphs = array_filter(array_map(function(DBTable $table){
            $m = [];
            foreach ($table->columns as $column) {
                if (!preg_match('/^(\w+)(_id|_type)$/', $column->name)) {
                    continue;
                }
                $name = preg_replace('/^(\w+)(_id|_type)$/', '$1', $column->name);
                if (preg_match('/^(\w+)_type$/', $column->name)) {
                    $m[$name]['tables'] = array_filter(preg_split('/(\s+)?,(\s+)?/', $column->comment), function($n){ return trim($n) && preg_match('/^([a-zA-Z][a-zA-Z0-9_]+)$/', $n); });
                }
                $m[$name][preg_replace('/^(\w+)(id|type)$/', '$2', $column->name)] = $column->name;
            }
            return array_filter($m, function($f){ return 3 === count($f); });
        }, $tables), 'filled');
        $output = [];
        foreach ($morphs as $table_name => $_morphs) {
            foreach ($_morphs as $morph => $entries) {
                $output[$table_name]['to'][$morph] = $entries['tables'];
                foreach ($entries['tables'] as $_table_name) {
                    $output[$_table_name]['many'][$morph] = $table_name;
                }
            }
        }
        return $output;
    }

    private function tablesPivotRelations(DatabaseSchema $schema)
    {
        $tables       = $schema->tables;
        $combinations = $this->pivotCombinations($tables);
        $relations    = array_intersect(array_keys($combinations), array_keys($tables));
        if (empty($relations)) {
            return;
        }
        foreach ($relations as $relation) {
            $comb = $combinations[$relation];
            $schema->getTable($relation)->setIsPivot($comb);
            foreach ($comb as $i => $t_name) {
                $schema->getTable($t_name)->setEndPivot($relation, $comb[$i ? 0 : 1]);
            }
        }
    }

    private function pivotCombinations(array $tables)
    {
        $maps = array_map(function($tables){
            return [
                [implode('_', $tables), $tables],
                [implode('_', array_reverse($tables)), $tables],
                [implode('_', [\Str::singular($tables[0]), $tables[1]]), $tables],
                [implode('_', [\Str::singular($tables[1]), $tables[0]]), $tables],
            ];
        },
            array_filter(array_combination(array_map(function(DBTable $t){ return $t->name; }, $tables)), function($tn){ return 2 === count($tn); }));
        $maps = array_merge(array_column($maps, 0), array_column($maps, 1), array_column($maps, 2), array_column($maps, 3));
        return array_combine(array_column($maps, 0), array_column($maps, 1));
    }

    protected function writeModel(Model $model)
    {
        file_put_contents(preg_replace(['/\\\$/', '/\/$/'], '', Config::base_dir()).DIRECTORY_SEPARATOR.$model->name.'.php', (string)$model);
    }
}