<?php
/**
 * @author       bangujo ON 2021-04-18 02:49
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile ModelCommand.php
 */

namespace Angujo\Elolara\Laravel;


use Angujo\Elolara\Config;
use Illuminate\Console\Command;

/**
 * Class ModelCommand
 *
 * @package Angujo\Elolara\Laravel
 */
class ModelCommand extends Command
{
    protected $signature = LM_APP_NAME.':generate
                            {--i|interactive : Interactive command interface}
                            {--f|force : Force overwrite of Base and Model files (not recommended)}
                            {--m|migrate : Perform migration first}
                            {--c|connection= : Connection to use}
                            {--d|database= : Database to work on}
                            {--e|exclude=* : Tables to be excluded}
                            {tables?* : List of tables to be generated}';

    protected $description = 'Parse DB schema tables into models';

    /** @var Factory */
    private $factory;
    private $migrate    = false;
    private $connection = null;
    private $database   = null;
    private $exclude    = [];
    private $tables     = [];
    private $force      = false;

    public function __construct(Factory $factory)
    {
        parent::__construct();
        $this->factory    = $factory;
        $this->connection = config('database.default');
        $this->database   = config("database.connections.{$this->connection}.database");
    }

    public function handle()
    {
        $this->force = $this->option('force');
        if ($this->option('interactive')) {
            $this->interactive();
        } else {
            $this->singleCommand();
        }
        $this->processCommand();
    }

    private function interactive()
    {
        $this->info('This screen will guide you to interactively generate your models.');
        $this->newLine();
        $this->migrate    = $this->confirm('Do you wish to run Database Migrations?', true);
        $connections      = array_keys(config('database.connections'));
        $defIndex         = array_search(config('database.default'), $connections);
        $this->connection = $this->choice('Which connection would you use?', $connections, $defIndex, null, false);
        while (true) {
            $this->database = ($ndb = trim(strtolower($this->ask('Database name?')))) ? $ndb : $this->database;
            if (in_array($this->database, Config::SCHEMAS_EXCLUDE)) {
                $this->error("Database '{$this->database}' cannot be parsed, it is on exclusion list or is DBMS defined.");
            } else {
                break;
            }
        }
        if ($this->confirm('Are there tables you would like excluded from generation?')) {
            $this->info('Separate table names using space or comma.');
            $list          = $this->ask('Tables to be excluded:');
            $this->exclude = array_filter(array_map('trim', preg_split('/(\s+|,)/', $list)), 'strlen');
        }
        if ($this->confirm('Would you like to run ONLY specific tables?')) {
            $this->info('Separate table names using space or comma.');
            $list         = $this->ask('Tables to used:');
            $this->tables = array_filter(array_map('trim', preg_split('/(\s+|,)/', $list)), 'strlen');
        }
    }

    private function singleCommand()
    {
        $this->migrate    = $this->option('migrate');
        $this->connection = $this->option('connection') ?? $this->connection;
        $this->database   = $this->option('database') ?? $this->database;
        $this->exclude    = ($ex = $this->option('exclude')) && is_array($ex) ? $ex : [];
        $this->tables     = ($tbls = $this->argument('tables')) && is_array($tbls) ? $tbls : [];
    }

    private function processCommand()
    {
        if ($this->migrate && 0 !== ($exitCode = \Artisan::call('migrate --verbose'))) {
            return $exitCode;
        }
        Config::schemaConfig($this->database);
        if ($this->force && $this->confirm('Do you wish to overwrite all models?(All customized changes will be lost!)')) {
            Config::overwrite_models(true);
        }
        if (!empty($this->exclude)) {
            Config::excluded_tables($this->exclude);
        }
        if (!empty($this->tables)) {
            Config::only_tables($this->tables);
        }
        $this->factory->setConnection($this->connection, $this->database);
        $this->factory->runSchema();

        return 0;
        // var_dump(Config::all());
    }
}