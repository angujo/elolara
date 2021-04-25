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
        $this->tablesMorphRelations($schema);
        $tables = $schema->tables;
        foreach ($tables as $table) {
            $this->writeModel(Model::fromTable($table)->setConnection($this->con_name));
        }
    }

    private function tablesMorphRelations(DatabaseSchema $schema)
    {
        $tables   = $schema->tables;
        $morphers = array_filter(array_map(function(DBTable $table){
            $m = $_set = [];
            foreach ($table->columns as $column) {
                if (!preg_match('/^(\w+)(_id|_type)$/', $column->name)) {
                    continue;
                }
                $name = preg_replace('/^(\w+)(_id|_type)$/', '$1', $column->name);
                if (isset($_set[$name])) {
                    $m[$name] = [preg_replace('/^(\w+)(id|type)$/', '$2', $column->name)=>$column->name, preg_replace('/^(\w+)(id|type)$/', '$2', $_set[$name])=>$_set[$name]];
                } else {
                    $_set[$name] = $column->name;
                }
            }
            return empty($m) ? null :$m;
        }, $tables));
        print_r($morphers);
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