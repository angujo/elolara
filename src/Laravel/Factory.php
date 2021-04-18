<?php
/**
 * @author       bangujo ON 2021-04-18 03:10
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Factory.php
 */

namespace Angujo\LaravelModel\Laravel;


use Angujo\LaravelModel\Database\DBMS;
use Angujo\LaravelModel\Model\Model;
use Illuminate\Database\ConnectionInterface;

/**
 * Class Factory
 *
 * @package Angujo\LaravelModel\Laravel
 */
class Factory
{
    private $connection;

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
        return $this->connection = \DB::connection($conn_name);
    }

    public function runSchema()
    {
        $dbms   = new DBMS($this->connection);
        $schema = $dbms->loadSchema();
        $tables = $schema->tables;
        foreach ($tables as $table) {
            $this->writeModel(Model::fromTable($table));
        }
    }

    protected function writeModel(Model $model)
    {
        file_put_contents($model->name.'.php', (string)$model);
    }
}