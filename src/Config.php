<?php
/**
 * @author       bangujo ON 2021-04-17 08:07
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Config.php
 */

namespace Angujo\Elolara;


use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Class Config
 *
 * @package Angujo\Elolara
 *
 * @method static boolean full_namespace_import(Boolean $value = null)
 * @method static boolean column_auto_relate(Boolean $value = null)
 * @method static boolean constant_column_names(Boolean $value = null)
 * @method static boolean date_base(Boolean $value = null)
 * @method static boolean define_connection(Boolean $value = null)
 * @method static boolean overwrite_models(Boolean $value = null)
 * @method static boolean db_directories(Boolean $value = null)
 * @method static string[]|array soft_delete_columns($value = null)
 * @method static string[]|array create_columns($value = null)
 * @method static string[]|array update_columns($value = null)
 * @method static string[]|array excluded_tables($value = null)
 * @method static string[]|array only_tables($value = null)
 * @method static string[]|array relation_naming($value = null)
 * @method static string[]|array core_traits()
 * @method static string[]|array schema_traits()
 * @method static string[]|array table_traits()
 * @method static string[]|array table_configs($value = null)
 * @method static string relation_remove_prx($value = null)
 * @method static string relation_remove_sfx($value = null)
 * @method static string model_class($value = null)
 * @method static string date_format($value = null)
 * @method static string base_dir($value = null)
 * @method static string constant_column_prefix(string $value = null)
 * @method static boolean composite_keys($value = null)
 * @method static string eloquent_extension_name(string $value = null)
 * @method static boolean base_abstract($value = null)
 * @method static string namespace($value = null)
 * @method static string base_abstract_prefix($value = null)
 * @method static string pivot_name_regex($value = null)
 * @method static string schema_name()
 * @method static string column_relation_pattern($value = null)
 * @method static string[]|array has_one_through($value = null)
 * @method static string[]|array has_many_through($value = null)
 */
class Config
{
    private static $me;
    public const SCHEMAS_EXCLUDE     = ['mysql', 'sys', 'information_schema', 'master', 'template'];
    public const LARAVEL_CONSTANTS   = ['created_at', 'updated_at'];
    public const LARAVEL_ID          = 'id';
    public const LARAVEL_TS_CREATED  = 'created_at';
    public const LARAVEL_TS_UPDATED  = 'updated_at';
    public const LARAVEL_TS_DELETED  = 'deleted_at';
    public const LARAVEL_PRIMARY_KEY = 'id';

    public static $laravel_primitives = ['array', 'boolean', 'collection', 'date', 'datetime', 'decimal:(\d+)', 'double', 'encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:object', 'float', 'integer', 'object', 'real', 'string', 'timestamp',];
    /** @var array|string[] */
    private $values = [];

    protected function __construct()
    {
        $this->values                = array_replace($this->defaults(), $this->user());
        $this->values['schema_name'] = null;
        $this->values['base_dir']    = preg_replace(['/\\$/', '/\/$/'], '', $this->values['base_dir'] ?? app_path('Models')).DIRECTORY_SEPARATOR;
        $this->setCoreTraits();
        $this->cleanCasts();
        $this->values['overwrite_models'] = false;
    }

    public static function __callStatic($method, $args)
    {
        self::$me = self::$me ?? new self();
        return !empty($args) ? call_user_func_array([self::$me, 'setProperty'], [$method, array_pop($args)]) : self::$me->getProperty($method);
    }

    public static function column_relation_regex()
    {
        return '/'.str_ireplace('{relation_name}', '(\w+)', Config::column_relation_pattern()).'/';
    }

    public static function timestampColumnNames()
    {
        return array_unique(array_merge([self::LARAVEL_TS_CREATED, self::LARAVEL_TS_UPDATED], self::create_columns(), self::update_columns()));
    }

    public static function timestamp_create_names()
    {
        return array_unique(array_merge([self::LARAVEL_TS_CREATED,], self::create_columns()));
    }

    public static function timestamp_update_names()
    {
        return array_unique(array_merge([self::LARAVEL_TS_UPDATED,], self::update_columns()));
    }

    /**
     * @param $schema_name
     *
     * @return Config
     */
    public static function schemaConfig(string $schema_name)
    : Config
    {
        $schema                            = (self::$me ?? (self::$me = new self()))->values['schemas'][$schema_name] ?? [];
        self::$me->values['table_configs'] = $schema['tables'] ?? [];
        self::$me->values['schema_name']   = $schema_name;
        self::$me->values['schema_traits'] = array_filter(\Arr::wrap($schema['traits'] ?? []));
        self::$me->values['table_traits']  = array_map(function($tbl){ return \Arr::wrap($tbl['traits'] ?? []); }, array_filter(\Arr::wrap($schema['tables'] ?? [])));
        unset(self::$me->values['schemas']);
        self::$me->values = array_replace(self::$me->values, $schema);
        self::$me->cleanCasts();
        return self::$me;
    }

    public static function all()
    {
        return (self::$me ?? new self())->values;
    }

    protected static function db_dir_extension($sfx = null)
    {
        return !self::db_directories() || (self::db_directories() && !self::schema_name()) ? '' : ucfirst(\Str::camel(self::schema_name())).$sfx;
    }

    public static function models_dir()
    {
        return self::base_dir().self::db_dir_extension(DIRECTORY_SEPARATOR);
    }

    public static function abstracts_prefix()
    {
        $df = self::base_abstract_prefix();
        if (!is_string($df)) $df = 'Base';
        return preg_replace('/[^a-zA-Z0-9_]/', '', $df ?: '') ?: 'Base';
    }

    public static function extension_ns()
    {
        $df = self::eloquent_extension_name();
        if (!is_string($df)) $df = 'Extension';
        return Util::className(preg_replace('/[^a-zA-Z0-9_]/', '', $df ?: '')) ?: 'Extension';
    }

    public static function super_model_name()
    {
        return Util::className(LM_APP_NAME.'_model');
    }

    public static function schema_model_name()
    {
        return Util::className(Config::schema_name().'_model');
    }

    public static function extensions_dir()
    {
        return self::base_dir().self::extension_ns().DIRECTORY_SEPARATOR;
    }

    public static function extension_namespace()
    {
        return self::namespace().'\\'.self::extension_ns();
    }

    public static function super_model_fqdn()
    {
        return self::namespace().'\\'.self::extension_ns().'\\'.self::super_model_name();
    }

    public static function schema_model_fqdn()
    {
        return self::namespace().'\\'.self::extension_ns().'\\'.self::schema_model_name();
    }

    public static function abstracts_namespace()
    {
        return self::schema_namespace('\\').'\\'.self::abstracts_prefix();
    }

    public static function schema_namespace($sfx = null)
    {
        return self::namespace().(($sns = self::db_dir_extension($sfx)) ? "\\{$sns}" : '');
    }

    public static function models_namespace()
    {
        return self::schema_namespace();
    }

    public static function abstracts_dir()
    {
        return self::models_dir().self::abstracts_prefix().DIRECTORY_SEPARATOR;
    }

    private function getProperty($method)
    {
        $method = strtolower(\Str::snake($method));
        return $this->values[$method] ?? null;
    }

    private function setProperty($method, $value)
    {
        $method = strtolower(\Str::snake($method));
        return $this->values[$method] = $value;
    }

    /**
     * @return string[]|array
     */
    private function defaults()
    {
        $configs = include(__DIR__.DIRECTORY_SEPARATOR.'Laravel'.DIRECTORY_SEPARATOR.'config.php');
        unset($configs['has_one_through'], $configs['has_many_through']);
        return $configs;
    }

    /**
     * @return string[]|array
     */
    private function user()
    {
        return config(LM_APP_NAME);
    }

    protected function setCoreTraits()
    {
        if (empty($this->values['traits'])) return;
        $this->values['core_traits'] = (array_filter($this->values['traits'], 'is_string'));
        unset($this->values['traits']);
    }

    protected function cleanCasts()
    : Config
    {
        $conversion = ['bool' => 'boolean'];
        $c          = $this->values['type_casts'] ?? null;
        $regex      = array_merge(self::$laravel_primitives,
                                  ['date(time)?:(Y|m|d|Y\-m|Y\-m\-d)',
                                   'time:H:i(:s)?',
                                   'datetime:((Y|m|d|Y\-m|Y\-m\-d)((\s+)H:i(:s)?)?)',//optional time
                                   'datetime:((Y|m|d|Y\-m|Y\-m\-d)?((\s+)?H:i(:s)?))',//optional date
                                  ]);
        $n_casts    = [];
        foreach ($c as $col => $validation) {
            if (false !== stripos($col, '%')) {
                $col = str_ireplace('%', '(.*?)', $col);
            }
            if (in_array($validation, $regex)) {
                $n_casts[$col] = $validation;
            } elseif (class_exists($validation)) {
                $implements = [Castable::class, CastsAttributes::class];
                if (empty(array_intersect($implements, class_implements($validation)))) {
                    continue;
                }
                $n_casts[$col] = [$validation, basename($validation).'::class'];
            } elseif (array_key_exists($validation, $conversion)) {
                $n_casts[$col] = $conversion[$validation];
            } else {
                foreach ($regex as $_regex) {
                    if (preg_match('/^'.$_regex.'$/', $validation)) {
                        $n_casts[$col] = $validation;
                        break;
                    }
                }
            }
        }
        $this->values['type_casts'] = $n_casts;
        return $this;
    }
}