<?php
/**
 * @author       bangujo ON 2021-04-18 17:42
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Model.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Relations\BelongsTo;
use Angujo\LaravelModel\Model\Relations\BelongsToMany;
use Angujo\LaravelModel\Model\Relations\HasMany;
use Angujo\LaravelModel\Model\Relations\HasOne;
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
            if (Config::constant_column_names()) {
                $this->_constants[] = ModelConst::fromColumn($column, $this->_imports);
            }
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
        $this->_properties[] = ModelProperty::forDates($table);
        $this->_properties[] = ModelProperty::forAttributes($table);
        $this->_properties[] = ModelProperty::forCasts($table, $this->_imports);

        $this->hasOneRelation($table);
        $this->belongsToRelation($table);
        $this->hasManyRelation($table);
        $this->belongsToManyRelation($table);

        $this->addImport(null);
    }

    protected function belongsToManyRelation(DBTable $table)
    {
        if (!$table->has_pivot) {
            return;
        }
        $this->_functions[] = $bl_many = BelongsToMany::fromTable($table, $this->name);
        $this->addImport(...$bl_many->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($bl_many);
    }

    protected function belongsToRelation(DBTable $table)
    {
        $foreignKeys = $table->foreign_keys;
        foreach ($foreignKeys as $foreignKey) {
            $this->_functions[] = $belong_to = BelongsTo::fromForeignKey($foreignKey, $this->name);
            $this->addImport(...$belong_to->imports());
            $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($belong_to);
        }

        $columns = array_filter($table->columns, function(DBColumn $column){ return !is_null($column->probable_table); });
        foreach ($columns as $column) {
            $this->_functions[] = $has_prop = BelongsTo::fromColumn($column, $this->name);
            $this->addImport(...$has_prop->imports());
            $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_prop);
        }
    }

    protected function hasOneRelation(DBTable $table)
    {
        $foreignKeys = array_filter($table->referencing_foreign_keys, function(DBForeignConstraint $constraint){ return $constraint->column->is_unique && !$constraint->column->is_multi_unique; });
        foreach ($foreignKeys as $foreignKey) {
            $this->_functions[] = $has_one = HasOne::fromForeignKey($foreignKey, $this->name);
            $this->addImport(...$has_one->imports());
            $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_one);
        }
    }

    protected function hasManyRelation(DBTable $table)
    {
        $foreignKeys = array_filter($table->referencing_foreign_keys, function(DBForeignConstraint $constraint){ return !$constraint->column->is_unique; });
        foreach ($foreignKeys as $foreignKey) {
            $this->_functions[] = $has_one = HasMany::fromForeignKey($foreignKey, $this->name);
            $this->addImport(...$has_one->imports());
            $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_one);
        }
    }

    public function setConnection(string $name)
    {
        $this->_properties[] = ModelProperty::forConnection($name);
        return $this;
    }
}