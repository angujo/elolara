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
use Angujo\LaravelModel\Model\Traits\ImportsClass;

/**
 * Class PhpDocProperty
 *
 * @package Angujo\LaravelModel\Model
 */
class PhpDocProperty
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'phpdoc-property';

    public $data_types = [];
    public $name;
    public $description;

    public static function fromColumn(DBColumn $column, &$imports = [])
    {
        $me               = new self();
        $me->name         = $column->name;
        $me->description  = $column->comment;
        $me->data_types[] = $column->data_type->phpName();
        if ($column->is_nullable) {
            $me->data_types[] = 'null';
        }
        $me->addImport($column->data_type->imports());
        $imports = array_merge($imports, $me->imports());
        return $me;
    }
}