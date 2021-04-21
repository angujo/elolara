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
use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Angujo\LaravelModel\Util;

/**
 * Class Model
 *
 * @package Angujo\LaravelModel\Model
 */
class Model
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'model';

    public $_uses         = [];
    public $_constants    = [];
    public $_functions    = [];
    public $_properties   = [];
    public $_phpdoc_props = [];
    public $namespace;
    public $name;
    public $parent;

    public static function fromTable(DBTable $table)
    {
        $me         = new self();
        $me->name   = Util::className($table->name);
        $me->parent = basename(Config::model_class());
        $me->addImport(Config::model_class());
        $me->namespace = Config::namespace();
        $me->processTable($table);
        return $me;
    }

    protected function processTable(DBTable $table)
    {
        $columns = $table->columns;
        foreach ($columns as $column) {
            $this->_constants[]    = ModelConst::fromColumn($column, $this->_imports);
            $this->_phpdoc_props[] = PhpDocProperty::fromColumn($column, $this->_imports);
        }
        $this->_properties[] = ModelProperty::forTableName($table);
        $this->_properties[] = ModelProperty::forPrimaryKey($table);
        $this->_properties[] = ModelProperty::forIncrementing($table);
        $this->_properties[] = ModelProperty::forKeyType($table);
        $this->_properties[] = ModelProperty::forTimestamps($table);
        $this->_properties[] = ModelProperty::forDateFormat();
        [$cre, $upd] = ModelConst::forTimestamps($table);
        $this->_constants[]  = $cre;
        $this->_constants[]  = $upd;
        $this->_properties[] = ModelProperty::forPrimaryKey($table);
        $this->_properties[] = ModelProperty::forDates($table);
        $this->_properties[] = ModelProperty::forAttributes($table);
        $this->_properties[] = ModelProperty::forCasts($table, $this->_imports);
        $this->_functions    = array_merge($this->_functions, RelationshipFunction::oneToOne($table, $this->_phpdoc_props,$this->_imports));
        $this->addImport(null);
    }

    public function setConnection(string $name)
    {
        $this->_properties[] = ModelProperty::forConnection($name);
        return $this;
    }
}