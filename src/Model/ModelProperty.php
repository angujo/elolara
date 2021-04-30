<?php
/**
 * @author       bangujo ON 2021-04-18 17:30
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile ModelProperty.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Traits\HasTemplate;
use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Angujo\LaravelModel\Model\Traits\UsesTraits;

/**
 * Class ModelProperty
 *
 * @package Angujo\LaravelModel\Model
 *
 */
class ModelProperty
{
    use HasTemplate, ImportsClass,UsesTraits;

    protected $template_name = 'property';

    public $description;
    public $var;
    public $access;
    public $name;
    public $value;

    public static function forPrimaryKey(DBTable $table)
    {
        if (!($primary = $table->primary_column) || 0 === strcasecmp(Config::LARAVEL_ID, $primary->name)) {
            return null;
        }
        $me         = new self();
        $me->var    = '* @var string';
        $me->access = 'protected';
        $me->name   = 'primaryKey';
        $me->value  = "'{$primary->name}'";
        return $me;
    }

    public static function forIncrementing(DBTable $table)
    {
        if (($primary = $table->primary_column) && $primary->is_auto_incrementing && ($primary->data_type->isBigint || $primary->data_type->isInteger)) {
            return null;
        }
        $me              = new self();
        $me->description = '* Indicates the IDs are not auto-incrementing';
        $me->var         = '* @var boolean';
        $me->access      = 'public';
        $me->name        = 'incrementing';
        $me->value       = 'false';
        return $me;
    }

    public static function forKeyType(DBTable $table)
    {
        if (!($primary = $table->primary_column) || ($primary->data_type->isBigint || $primary->data_type->isInteger)) {
            return null;
        }
        $me         = new self();
        $me->var    = '* @var string';
        $me->access = 'protected';
        $me->name   = 'keyType';
        $me->value  = '\'string\'';
        return $me;
    }

    public static function forTableName(DBTable $table)
    {
        $me              = new self();
        $me->description = '* Table associated with this model';
        $me->var         = '* @var string';
        $me->access      = 'protected';
        $me->name        = 'table';
        $me->value       = var_export($table->name, true);
        return $me;
    }

    public static function forSoftDeletion(DBTable $table)
    {
        if (1 === count(array_filter($table->columns, function(DBColumn $c){ return in_array($c->name, Config::timestampColumnNames()); }))) {
            return null;
        }
        $me              = new self();
        $me->var         = '* @var boolean';
        $me->description = '* Indicates if the model should be timestamped. ';
        $me->access      = 'public';
        $me->name        = 'timestamps';
        $me->value       = 'false';
        return $me;
    }

    public static function forTimestamps(DBTable $table)
    {
        if (2 === count(array_filter($table->columns, function(DBColumn $c){ return in_array($c->name, Config::timestampColumnNames()); }))) {
            return null;
        }
        $me              = new self();
        $me->var         = '* @var boolean';
        $me->description = '* Indicates if the model should be timestamped. ';
        $me->access      = 'public';
        $me->name        = 'timestamps';
        $me->value       = 'false';
        return $me;
    }

    public static function forDateFormat()
    {
        if (!is_string(Config::date_format())) {
            return null;
        }
        $me              = new self();
        $me->var         = '* @var boolean';
        $me->description = '* The storage format of the model\'s date columns';
        $me->access      = 'protected';
        $me->name        = 'dateFormat';
        $me->value       = var_export(Config::date_format(), true);
        return $me;
    }

    public static function forConnection(string $name = null)
    {
        if (!Config::define_connection() || !$name || !is_string($name)) {
            return null;
        }
        $me              = new self();
        $me->var         = '* @var string';
        $me->description = '* The connection name for model';
        $me->access      = 'protected';
        $me->name        = 'connection';
        $me->value       = var_export($name, true);
        return $me;
    }

    public static function forAttributes(DBTable $table)
    {
        $columns = array_filter($table->columns, function(DBColumn $col){
            return !$col->is_auto_incrementing && !$col->is_generated && $col->default && !in_array($col->name, Config::timestampColumnNames());
        });
        if (empty($columns)) {
            return null;
        }
        $keys            = array_map(function(DBColumn $col){ return $col->name; }, $columns);
        $values          = array_map(function(DBColumn $col){ return $col->default; }, $columns);
        $me              = new self();
        $me->var         = '* @var array';
        $me->description = '* Default values for attributes';
        $me->access      = 'protected';
        $me->name        = 'attributes';
        $me->value       = var_export(array_combine($keys, $values), true);
        return $me;
    }

    public static function forDates(DBTable $table)
    {
        $columns = array_filter($table->columns, function(DBColumn $col){
            return $col->data_type->isTimestamp;
        });
        if (empty($columns)) {
            return null;
        }
        $values          = array_values(array_map(function(DBColumn $col){ return $col->name; }, $columns));
        $me              = new self();
        $me->var         = '* @var array';
        $me->description = '* Attributes that should be muted to dates';
        $me->access      = 'protected';
        $me->name        = 'dates';
        $me->value       = array_export($values);
        return $me;
    }

    public static function forCasts(DBTable $table)
    {
        $imp  = [];
        $cols = array_filter(array_map(function(DBColumn $column) use (&$imp){
            $casts = Config::type_casts();
            foreach ($casts as $col_reg => $cast) {
                if (0 === strcasecmp($col_reg, $column->name) ||
                    preg_match('/^'.$col_reg.'$/', $column->name) ||
                    0 === strcasecmp($col_reg, "type:{$column->type}") ||
                    0 === strcasecmp($col_reg, "type:{$column->column_type}")) {
                    if (is_array($cast)) {
                        $imp[] = $cast[0];
                        $cast  = $cast[1];
                    }
                    return [$column->name, $cast];
                }
            }
            return null;
        }, $table->columns));
        if (empty($cols)) {
            return null;
        }
        $me = new self();
        $me->addImport(...$imp);
        $me->var         = '* @var array';
        $me->description = '* Attributes that should be cast';
        $me->access      = 'protected';
        $me->name        = 'casts';
        $me->value       = array_export(array_combine(array_column($cols, 0), array_column($cols, 1)));
        return $me;
    }
}