<?php
/**
 * @author       bangujo ON 2021-04-17 08:00
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile PhpDocProperty.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Model\Traits\HasTemplate;

/**
 * Class PhpDocProperty
 *
 * @package Angujo\LaravelModel\Model
 */
class PhpDocProperty
{
    use HasTemplate;

    protected $template_name = 'phpdoc-property';

    public $data_types = [];
    public $name;
    public $description;

    public static function fromColumn(DBColumn $column)
    {
        $me               = new self();
        $me->name         = $column->name;
        $me->description  = $column->comment;
        $me->data_types[] = $column->column_type;
        return $me;
    }
}