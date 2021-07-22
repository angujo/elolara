<?php
/**
 * @author       bangujo ON 2021-04-18 17:30
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile ModelProperty.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Traits\HasTemplate;
use Angujo\Elolara\Model\Traits\ImportsClass;

/**
 * Class ModelProperty
 *
 * @package Angujo\Elolara\Model
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

    public static function fromColumn(DBColumn $column, ?string $name = null)
    {
        if (in_array($column->name, Config::LARAVEL_CONSTANTS) && !Config::constant_column_prefix()) {
            return null;
        }
        $me         = new self();
        $me->var    = "@var string Column name: {$column->name}, Data Type: ".$column->data_type->phpName()."({$column->column_type})";
        $me->access = 'public';
        $me->name   = strtoupper(\Str::slug((Config::constant_column_prefix()).($name ?: $column->name), '_'));
        $me->value  = "'{$column->name}'";
        $me->addImport($column->data_type->imports());
        return $me;
    }

    public static function forTimestamps(DBTable $table)
    {
        $outs = [null, null];
        if (1 != count($created = array_filter($table->columns, function(DBColumn $c){ return in_array($c->name, Config::timestamp_create_names()); })) ||
            1 != count($updated = array_filter($table->columns, function(DBColumn $c){ return in_array($c->name, Config::timestamp_update_names()); }))) {
            return $outs;
        }
        /** @var DBColumn $created */
        $created = array_pop($created);
        /** @var DBColumn $updated */
        $updated = array_pop($updated);
        if (!$created->data_type->isTimestamp || !$updated->data_type->isTimestamp) {
            return $outs;
        }

        if (0 !== strcasecmp(Config::LARAVEL_TS_CREATED, $created->name) || Config::constant_column_prefix()) {
            $outs[0] = self::fromColumn($created, Config::constant_column_prefix() ? null : Config::LARAVEL_TS_CREATED);
        }
        if (0 !== strcasecmp(Config::LARAVEL_TS_UPDATED, $updated->name) || Config::constant_column_prefix()) {
            $outs[1] = self::fromColumn($updated, Config::constant_column_prefix() ? null : Config::LARAVEL_TS_UPDATED);
        }
        return $outs;
    }
}
