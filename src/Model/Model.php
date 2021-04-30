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
use Angujo\LaravelModel\Model\Relations\HasManyThrough;
use Angujo\LaravelModel\Model\Relations\HasOne;
use Angujo\LaravelModel\Model\Relations\HasOneThrough;
use Angujo\LaravelModel\Model\Relations\MorphedByMany;
use Angujo\LaravelModel\Model\Relations\MorphMany;
use Angujo\LaravelModel\Model\Relations\MorphOne;
use Angujo\LaravelModel\Model\Relations\MorphTo;
use Angujo\LaravelModel\Model\Relations\MorphToMany;
use Angujo\LaravelModel\Model\Traits\HasMetaData;
use Angujo\LaravelModel\Model\Traits\HasTemplate;
use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Angujo\LaravelModel\Model\Traits\UsesTraits;
use Angujo\LaravelModel\Util;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Model
 *
 * @package Angujo\LaravelModel\Model
 */
class Model
{
    use HasTemplate, ImportsClass, UsesTraits, HasMetaData;

    protected $template_name = 'model';

    public $uses          = '';
    public $_constants    = [];
    public $_functions    = [];
    public $_properties   = [];
    public $_phpdoc_props = [];
    public $namespace;
    public $name;
    public $abstract      = '';
    public $parent;
    public $child;
    public $date;
    public $base_model    = false;

    /** @var DBTable */
    public $table;

    protected function __construct(DBTable $table)
    {
        $this->table = $table;
        $this->date  = date('Y-m-d H:i:s');
    }

    protected function preProcessTemplate()
    {
        if (!empty($this->traits)) {
            $this->uses = 'use '.implode(',', array_map('basename', $this->traits())).';';
        }
    }

    public static function fromTable(DBTable $table, bool $for_base = null)
    {
        $me             = new self($table);
        $me->namespace  = Config::namespace();
        $me->name       = Util::className($table->name);
        $me->base_model = $for_base;
        if (true === $for_base) {
            $me->abstract      = 'abstract ';
            $me->template_name = 'abstract-model';
            $me->namespace     = Config::abstracts_namespace();
            $me->name          = Util::baseClassName($table->name);
            $me->parent        = basename(Config::super_model_name());
            $me->child         = Util::className($table->name);
            $me->addImport(Config::super_model_fqdn());
        } elseif (false === $for_base) {
            $me->addImport($pns = Config::abstracts_namespace().'\\'.Util::baseClassName($table->name));
            $me->parent = basename($pns);
        }
        if (false !== $for_base) {
            $me->processTable();
        }
        return $me;
    }

    protected function processTable()
    {
        $columns = $this->table->columns;
        foreach ($columns as $column) {
            if (in_array($column->name, Config::soft_delete_columns()) && $column->data_type->isTimestamp) {
                $this->addTrait(SoftDeletes::class);
                $this->addImport(SoftDeletes::class);
                if (0 !== strcasecmp(Config::LARAVEL_TS_DELETED, $column->name)) {
                    $this->_constants[] = ModelConst::fromColumn($column, Config::LARAVEL_TS_DELETED);
                }
            }
            if (Config::constant_column_names()) {
                $this->_constants[] = $const = ModelConst::fromColumn($column);
                if ($const) {
                    $this->addImport(...$const->imports());
                }
            }
            $this->_phpdoc_props[] = PhpDocProperty::fromColumn($column, $this->_imports);
        }
        $this->_properties[] = ModelProperty::forTableName($this->table);
        $this->_properties[] = ModelProperty::forPrimaryKey($this->table);
        $this->_properties[] = ModelProperty::forIncrementing($this->table);
        $this->_properties[] = ModelProperty::forKeyType($this->table);
        $this->_properties[] = ModelProperty::forTimestamps($this->table);
        $this->_properties[] = ModelProperty::forDateFormat();
        [$cre, $upd] = ModelConst::forTimestamps($this->table);
        $this->_constants[]  = $cre;
        $this->_constants[]  = $upd;
        $this->_properties[] = ModelProperty::forDates($this->table);
        $this->_properties[] = ModelProperty::forAttributes($this->table);
        $this->_properties[] = $casts = ModelProperty::forCasts($this->table);
        if ($casts) {
            $this->addImport(...$casts->imports());
        }

        $this->refForeignKeysFilters();
        $this->foreignKeysFilters();
        $this->columnFilters();
        $this->morphManyFilters();
        $this->morphToFilters();
        $this->belongsToManyRelation();
        $this->oneThroughFilters();
        $this->manyThroughFilters();

        $this->addImport(null);
    }

    protected function oneThroughFilters()
    {
        foreach ($this->table->one_through as $item) {
            $this->hasOneThrough($item[0], $item[1]);
        }
    }

    protected function manyThroughFilters()
    {
        foreach ($this->table->one_through as $item) {
            $this->hasManyThrough($item[0], $item[1]);
        }
    }

    protected function refForeignKeysFilters()
    {
        foreach ($this->table->referencing_foreign_keys as $foreign_key) {
            $this->hasOneFromRefFK($foreign_key);
            $this->hasManyFromRefFK($foreign_key);
            $this->morphedByManyFromRefFK($foreign_key);
        }
    }

    protected function foreignKeysFilters()
    {
        foreach ($this->table->foreign_keys as $foreign_key) {
            $this->belongsToFromFK($foreign_key);
        }
    }

    protected function columnFilters()
    {
        foreach ($this->table->columns as $column) {
            $this->belongsToFromColumn($column);
        }
    }

    protected function morphManyFilters()
    {
        foreach ($this->table->morph_manys as $name => $morph_table) {
            $this->morphOneFromMM($name, $morph_table);
            $this->morphManyFromMM($name, $morph_table);
            $this->morphToManyFromMM($name, $morph_table);
        }
    }

    protected function morphToFilters()
    {
        foreach ($this->table->morph_tos as $name => $tables) {
            $this->morphToFromMT($name, $tables);
        }
    }

    protected function hasManyThrough(DBTable $pTable, DBTable $eTable)
    {
        $this->_functions[] = $morph_to = HasManyThrough::fromTables($this->table, $pTable, $eTable, $this->name);
        $this->addImport(...$morph_to->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_to);
    }

    protected function hasOneThrough(DBTable $pTable, DBTable $eTable)
    {
        $this->_functions[] = $morph_to = HasOneThrough::fromTables($this->table, $pTable, $eTable, $this->name);
        $this->addImport(...$morph_to->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_to);
    }

    protected function morphToFromMT($name, array $tables)
    {
        $this->_functions[] = $morph_to = MorphTo::fromTable($name, $this->name, $tables, $this->table->nullableMorphTo($name));
        $this->addImport(...$morph_to->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_to);
    }

    protected function morphToManyFromMM(string $name, DBTable $morphTable)
    {
        if ($morphTable->uniqueMorph($name)) {
            return;
        }
        /** @var DBColumn[] $columns */
        $columns = array_filter($morphTable->columns, function(DBColumn $col) use ($name){ return !$col->is_primary && !in_array($col, ["{$name}_id", "{$name}_type"]); });
        foreach ($columns as $column) {
            if (!($_table = $column->foreign_key ? $column->foreign_key->referenced_table : $column->probable_table)) {
                continue;
            }
            $this->_functions[] = $morph_many = MorphToMany::fromTable($name, $column, $_table, $this->name);
            $this->addImport(...$morph_many->imports());
            $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_many);
        }
    }

    protected function morphManyFromMM(string $name, DBTable $morphTable)
    {
        if ($morphTable->uniqueMorph($name)) {
            return;
        }
        $this->_functions[] = $morph_many = MorphMany::fromTable($name, $morphTable, $this->name);
        $this->addImport(...$morph_many->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_many);
    }

    protected function morphOneFromMM(string $name, DBTable $morphTable)
    {
        if (!$morphTable->uniqueMorph($name)) {
            return;
        }
        $this->_functions[] = $morph_one = MorphOne::fromTable($name, $morphTable, $this->name);
        $this->addImport(...$morph_one->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_one);
    }


    protected function belongsToManyRelation()
    {
        if (!$this->table->has_pivot) {
            return;
        }
        $this->_functions[] = $bl_many = BelongsToMany::fromTable($this->table, $this->name);
        $this->addImport(...$bl_many->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($bl_many);
    }


    protected function belongsToFromFK(DBForeignConstraint $foreignKey)
    {
        $this->_functions[] = $belong_to = BelongsTo::fromForeignKey($foreignKey, $this->name);
        $this->addImport(...$belong_to->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($belong_to);
    }

    protected function belongsToFromColumn(DBColumn $column)
    {
        if (blank($column->probable_table)) {
            return;
        }
        $this->_functions[] = $has_prop = BelongsTo::fromColumn($column, $this->name);
        $this->addImport(...$has_prop->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_prop);
    }

    protected function hasOneFromRefFK(DBForeignConstraint $foreignKey)
    {
        if (!$foreignKey->column->is_unique || $foreignKey->column->is_multi_unique) {
            return;
        }
        $this->_functions[] = $has_one = HasOne::fromForeignKey($foreignKey, $this->name);
        $this->addImport(...$has_one->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_one);
    }


    protected function hasManyFromRefFK(DBForeignConstraint $foreignKey)
    {
        if ($foreignKey->column->is_unique) {
            return;
        }
        $this->_functions[] = $has_one = HasMany::fromForeignKey($foreignKey, $this->name);
        $this->addImport(...$has_one->imports());
        $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_one);
    }

    protected function morphedByManyFromRefFK(DBForeignConstraint $foreignKey)
    {
        if ($foreignKey->column->is_unique || blank($morphTos = $foreignKey->table->morph_tos)) {
            return;
        }
        foreach ($morphTos as $name => $tables) {
            foreach ($tables as $_table) {
                $this->_functions[] = $morph_many = MorphedByMany::fromTable($name, $foreignKey->column, $_table, $this->name);
                $this->addImport(...$morph_many->imports());
                $this->_phpdoc_props[] = PhpDocProperty::fromRelationFunction($morph_many);
            }
        }
    }


    public function setConnection(string $name)
    {
        $this->_properties[] = ModelProperty::forConnection($name);
        return $this;
    }
}