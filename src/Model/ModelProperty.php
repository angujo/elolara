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
class ModelProperty
{
    use HasTemplate;

    protected $template_name = 'property';

    public $description;
    public $var;
    public $access;
    public $name;
    public $value;

    public static function columnConstant(DBColumn $column)
    {
        $me = new self();
    }
}