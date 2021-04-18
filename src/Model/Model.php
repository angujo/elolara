<?php
/**
 * @author       bangujo ON 2021-04-18 17:42
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Model.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Traits\HasTemplate;
use Angujo\LaravelModel\Util;

/**
 * Class Model
 *
 * @package Angujo\LaravelModel\Model
 */
class Model
{
    use HasTemplate;

    protected $template_name = 'model';

    public $_imports     = [];
    public $_uses        = [];
    public $_constraints = [];
    public $_functions   = [];
    public $namespace;
    public $name;
    public $parent;

    public static function fromTable(DBTable $table)
    {
        $me            = new self();
        $me->name      = Util::className($table->name);
        $me->parent    = Config::model_class();
        $me->namespace = Config::namespace();
        return $me;
    }
}