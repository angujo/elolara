<?php
/**
 * @author       bangujo ON 2021-04-18 03:10
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Factory.php
 */

namespace Angujo\Elolara\Laravel;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DatabaseSchema;
use Angujo\Elolara\Database\DBMS;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\CoreModel;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\SchemaModel;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class Factory
 *
 * @package Angujo\Elolara\Laravel
 */
class Factory
{
    /** @var ConnectionInterface */
    private $connection;
    private $con_name;
    /** @var ProgressBar */
    public static $BAR;

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

    public function runSchema(OutputStyle $output)
    {
        $dbms = new DBMS($this->connection);

        $schema = $dbms->getSchema()
                       ->setExcludedTables(...\Arr::wrap(Config::excluded_tables()))
                       ->setOnlyTables(...\Arr::wrap(Config::only_tables()));

        $dbms->loadSchema();
        $tables    = $schema->tables;
        self::$BAR = $output->createProgressBar((count($tables) * 17) + 5);
        self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
        self::$BAR->setMessage('Init');
        self::$BAR->start();
        self::$BAR->setMessage('Preparing dirs...');
        $this->prepareDirs();
        self::$BAR->advance();
        self::$BAR->setMessage('Writing core models...');
        $this->writeCoreModel();
        if (Config::db_directories()) $this->writeSchemaModel();
        self::$BAR->advance();

        $this->tablesPivotRelations($schema);
        self::$BAR->setMessage('loading morph relations...');
        $morphs = $this->tablesMorphRelations($schema);
        self::$BAR->advance();
        self::$BAR->setMessage('Loading On-Through relations...');
        $oneThrough = $this->oneThroughRelations();
        self::$BAR->advance();
        self::$BAR->setMessage('Loading Many-Through relations...');
        $manyThrough = $this->manyThroughRelations();
        self::$BAR->advance();
        foreach ($tables as $table) {
            self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% {$table->name}: %message%");
            self::$BAR->setMessage("Morphs...");
            $table->setMorph($morphs);
            self::$BAR->advance();
            self::$BAR->setMessage("One-Through...");
            if (!empty($oneThrough[$table->name])) $table->setOneThrough($oneThrough);
            self::$BAR->advance();
            self::$BAR->setMessage("Many-Through...");
            if (!empty($manyThrough[$table->name])) $table->setManyThrough($manyThrough);
            self::$BAR->advance();
            self::$BAR->setMessage("Writing Model...");
            if (Config::base_abstract()) {
                $this->writeModel(Model::fromTable($table, true)->setConnection($this->con_name));
                $this->writeModel(Model::fromTable($table, false));
            } else  $this->writeModel(Model::fromTable($table, true)->setConnection($this->con_name));
            self::$BAR->advance();
        }
        self::$BAR->setFormat("%current%/%max% [%bar%] %percent:3s%% %message%");
        self::$BAR->setMessage('Done!');
        self::$BAR->finish();
    }

    protected function prepareDirs()
    {
        if (!file_exists($md = Config::models_dir())) mkdir($md, 0777, true);
        if (!is_writable($md)) throw new \Exception("'{$md}' is not writeable!");

        if (!file_exists($cd = Config::extensions_dir())) mkdir($cd, 0777, true);
        if (!is_writable($cd)) throw new \Exception("'{$cd}' is not writeable!");

        if (Config::base_abstract()) {
            if (!file_exists($dir = Config::abstracts_dir()) || !is_dir($dir)) mkdir($dir, 0777, true);
            if (!is_writable($dir)) throw new \Exception("'{$dir}' is not writeable!");
        }
    }

    private function oneThroughRelations()
    {
        $ones     = $this->throughList(Config::has_one_through());
        $throughs = [];
        foreach ($ones as $tables) {
            $k              = array_shift($tables);
            $throughs[$k][] = $tables;
        }
        return $throughs;
    }

    private function manyThroughRelations()
    {
        $manys    = $this->throughList(Config::has_many_through());
        $throughs = [];
        foreach ($manys as $tables) {
            $k              = array_shift($tables);
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
        $pivots = array_filter($this->pivotTableFactory($schema), function($p){ return count($p) == 2; });
        if (empty($pivots)) return;
        foreach ($pivots as $pivot => $relations) {
            $schema->getTable($pivot)->setIsPivot($relations);
            foreach ($relations as $_relations) {
                $schema->getTable($_relations[0])->setEndPivot($pivot, $_relations[1]);
            }
        }
    }

    private function pivotTableFactory(DatabaseSchema $schema)
    {
        /** @var string[] $pivots */
        $pivots = (is_array($p = Config::pivot_tables()) ? $p : []);
        $mix    = [];
        foreach ($pivots as $pivot_name) {
            if (!($table = $schema->getTable($pivot_name))) continue;
            foreach ($table->foreign_keys as $referencing_foreign_key) {
                $mix[$pivot_name][] = $referencing_foreign_key->referenced_table_name;
            }
            $mix[$pivot_name] = array_combination($mix[$pivot_name], 2);
        }
        return $mix;
    }

    protected function writeModel(Model $model)
    {
        $path = (Config::base_abstract() && $model->base_model ? Config::abstracts_dir() : Config::models_dir()).$model->name.'.php';
        if (!Config::overwrite_models() && false === $model->base_model && file_exists($path)) return;
        file_put_contents($path, (string)$model);
    }

    /**
     * Set the parent Model for all
     * We'll always overwrite depending on config changes.
     * If user want's to update, then a custom file can be set as model_class in config
     * Parent changes can be pushed there.
     */
    protected function writeSchemaModel()
    {
        $model = SchemaModel::load();
        $path  = Config::extensions_dir().$model->name.'.php';
        file_put_contents($path, (string)$model);
    }

    protected function writeCoreModel()
    {
        $model = CoreModel::load();
        $path  = Config::extensions_dir().$model->name.'.php';
        file_put_contents($path, (string)$model);
    }
}