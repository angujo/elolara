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

/**
 * Class ModelProperty
 *
 * @package Angujo\LaravelModel\Model
 */
class ModelConst
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'const';

    public $description;
    public $var;
    public $access;
    public $name;
    public $value;

    public static function fromColumn(DBColumn $column, array &$imports = [], ?string $name = null)
    {
        if (in_array($column->name, Config::LARAVEL_CONSTANTS)) {
            return null;
        }
        $me         = new self();
        $me->var    = "@var string Column name: {$column->name}, Data Type: ".$column->data_type->phpName()."({$column->column_type})";
        $me->access = 'public';
        $me->name   = strtoupper(\Str::slug($name ?: $column->name, '_'));
        $me->value  = "'{$column->name}'";
        $me->addImport($column->data_type->imports());
        $imports = array_merge($imports, $me->imports());
        return $me;
    }

    public static function forTimestamps(DBTable $table)
    {
        if (1 != count($created = array_filter($table->columns, function(DBColumn $c){ return in_array($c->name, array_merge([Config::LARAVEL_TS_CREATED], Config::create_columns())); }))) {
            return null;
        }
        if (1 != count($updated = array_filter($table->columns, function(DBColumn $c){ return in_array($c->name, array_merge([Config::LARAVEL_TS_UPDATED], Config::update_columns())); }))) {
            return null;
        }
        /** @var DBColumn $created */
        $created = array_pop($created);
        /** @var DBColumn $updated */
        $updated = array_pop($updated);
        $outs    = [null, null];
        $im      = [];
        if (0 !== strcasecmp(Config::LARAVEL_TS_CREATED, $created->name)) {
            $outs[0] = self::fromColumn($created, $im, Config::LARAVEL_TS_CREATED);
        }
        if (0 !== strcasecmp(Config::LARAVEL_TS_UPDATED, $updated->name)) {
            $outs[1] = self::fromColumn($updated, $im, Config::LARAVEL_TS_UPDATED);
        }
        return $outs;
    }
}