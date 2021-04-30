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
use Angujo\LaravelModel\Model\CoreModel;
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

        $this->prepareDirs();
        $this->writeCoreModel();

        $this->tablesPivotRelations($schema);
        $morphs      = $this->tablesMorphRelations($schema);
        $oneThrough  = $this->oneThroughRelations();
        $manyThrough = $this->manyThroughRelations();
        $tables      = $schema->tables;
        foreach ($tables as $table) {
            $table->setMorph($morphs);
            $table->setOneThrough($oneThrough);
            $table->setManyThrough($manyThrough);
            if (Config::base_abstract()) {
                $this->writeModel(Model::fromTable($table, true)->setConnection($this->con_name));
                $this->writeModel(Model::fromTable($table, false));
            } else  $this->writeModel(Model::fromTable($table, true)->setConnection($this->con_name));
        }
    }

    protected function prepareDirs()
    {
        if (!file_exists($cd = Config::extensions_dir())) mkdir($cd);
        if (!is_writable($cd)) throw new \Exception("'{$cd}' is not writeable!");

        if (!file_exists($md = Config::models_dir())) mkdir($md);
        if (!is_writable($md)) throw new \Exception("'{$md}' is not writeable!");

        if (Config::base_abstract()) {
            if (!file_exists($dir = Config::abstracts_dir()) || !is_dir($dir)) mkdir($dir);
            if (!is_writable($dir)) throw new \Exception("'{$dir}' is not writeable!");
        }
        print_r([Config::models_dir(), Config::abstracts_dir()]);
    }

    private function oneThroughRelations()
    {
        $ones     = $this->throughList(Config::has_one_through());
        $throughs = [];
        foreach ($ones as $tables) {
            $k              = array_pop($tables);
            $throughs[$k][] = $tables;
        }
        return $throughs;
    }

    private function manyThroughRelations()
    {
        $manys    = $this->throughList(Config::has_many_through());
        $throughs = [];
        foreach ($manys as $tables) {
            $k              = array_pop($tables);
            $throughs[$k][] = $tables;
        }
        return $throughs;
    }

    private function throughList($list)
    {
        return array_filter(array_map(function($tbls){
            if (is_string($tbls)) $tbls = array_map('trim', array_filter(explode(',', $tbls)));
            return !is_array($tbls) || blank($tbls) || 3 !== count($tbls) ? null : $tbls;
        }, $list ?? []));
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
                if (!preg_match('/^(\w+)(_id|_type)$/', $column->name)) continue;

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
        $path = (Config::base_abstract() && $model->base_model ? Config::abstracts_dir() : Config::models_dir()).$model->name.'.php';
        if (!Config::overwrite_models() && false === $model->base_model && file_exists($path)) return;
        file_put_contents($path, (string)$model);
    }

    protected function writeCoreModel()
    {
        $model = CoreModel::load();
        $path  = Config::extensions_dir().$model->name.'.php';
        if (file_exists($path)) return;
        file_put_contents($path, (string)$model);
    }
}