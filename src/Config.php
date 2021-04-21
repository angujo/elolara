<?php
/**
 * @author       bangujo ON 2021-04-17 08:07
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Config.php
 */

namespace Angujo\LaravelModel;


use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Class Config
 *
 * @package Angujo\LaravelModel
 *
 * @method static boolean full_namespace_import(Boolean $value = null)
 * @method static boolean date_base($value = null)
 * @method static boolean define_connection($value = null)
 * @method static boolean overwrite_models($value = null)
 * @method static boolean db_directories($value = null)
 * @method static string[]|array soft_delete_columns($value = null)
 * @method static string[]|array create_columns($value = null)
 * @method static string[]|array update_columns($value = null)
 * @method static string[]|array excluded_tables($value = null)
 * @method static string[]|array only_tables($value = null)
 * @method static string[]|array relation_naming($value = null)
 * @method static string relation_remove_prx($value = null)
 * @method static string relation_remove_sfx($value = null)
 * @method static string model_class($value = null)
 * @method static string date_format($value = null)
 * @method static string base_dir($value = null)
 * @method static boolean composite_keys($value = null)
 * @method static string eloquent_extension_name($value = null)
 * @method static boolean base_abstract($value = null)
 * @method static string namespace($value = null)
 * @method static int pivot_level($value = null)
 * @method static string pivot_extension_name($value = null)
 * @method static string column_relation_pattern($value = null)
 * @method static boolean polymorph($value = null)
 * @method static string[]|array type_casts($value = null)
 */
class Config
{
    private static $me;
    public const CONFIG_NAME         = 'laravelmodel';
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
        $this->values = array_replace($this->defaults(), $this->user());
        $this->cleanCasts();
        $this->values['overwrite_models'] = false;
    }

    public static function __callStatic($method, $args)
    {
        self::$me = self::$me ?? new self();
        return !empty($args) ? call_user_func_array([self::$me, 'setProperty'], [$method, array_pop($args)]) : self::$me->getProperty($method);
    }

    public static function timestampColumnNames()
    {
        return array_merge([self::LARAVEL_TS_CREATED, self::LARAVEL_TS_UPDATED], self::create_columns(), self::update_columns());
    }

    /**
     * @param $schema_name
     *
     * @return Config
     */
    public static function schemaConfig($schema_name)
    : Config
    {
        $schema = (self::$me = self::$me ?? new self())->values['schemas'][$schema_name] ?? [];
        unset(self::$me->values['schemas']);
        self::$me->values = array_replace(self::$me->values, $schema);
        self::$me->cleanCasts();
        return self::$me;
    }

    public static function all()
    {
        return (self::$me ?? new self())->values;
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
        return include(__DIR__.DIRECTORY_SEPARATOR.'Laravel'.DIRECTORY_SEPARATOR.'config.php');
    }

    /**
     * @return string[]|array
     */
    private function user()
    {
        return config(self::CONFIG_NAME);
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