<?php
/**
 * @author       bangujo ON 2021-04-18 17:30
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile ModelProperty.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Model\Traits\HasTemplate;

/**
 * Class ModelProperty
 *
 * @package Angujo\LaravelModel\Model
 */
class ModelConst
{
    use HasTemplate;

    protected $template_name = 'const';

    public $description;
    public $var;
    public $access;
    public $name;
    public $value;

    public static function fromColumn(DBColumn $column)
    {
        $me         = new self();
        $me->var    = "@var {$column->column_type} Column name: {$column->name}";
        $me->access = 'public';
        $me->name   = \Str::slug(strtoupper($column->name), '_');
        $me->value  = "'{$column->name}'";
        return $me;
    }
}