<?php
/**
 * @author       bangujo ON 2021-04-17 08:07
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Config.php
 */

namespace Angujo\LaravelModel;


/**
 * Class Config
 *
 * @package Angujo\LaravelModel
 *
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
    public const CONFIG_NAME        = 'laravelmodel';
    public const SCHEMAS_EXCLUDE    = ['mysql', 'sys', 'information_schema', 'master', 'template'];
    public const LARAVEL_CONSTANTS  = ['created_at', 'updated_at'];
    public const LARAVEL_ID         = 'id';
    public const LARAVEL_TS_CREATED = 'created_at';
    public const LARAVEL_TS_UPDATED = 'updated_at';
    public const LARAVEL_TS_DELETED = 'deleted_at';
    /** @var array|string[] */
    private $values = [];

    protected function __construct()
    {
        $this->values                     = array_replace($this->defaults(), $this->user());
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
}