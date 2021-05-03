<?php
/**
 * @author       bangujo ON 2021-04-18 17:42
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Model.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Relations\BelongsTo;
use Angujo\Elolara\Model\Relations\BelongsToMany;
use Angujo\Elolara\Model\Relations\HasMany;
use Angujo\Elolara\Model\Relations\HasManyThrough;
use Angujo\Elolara\Model\Relations\HasOne;
use Angujo\Elolara\Model\Relations\HasOneThrough;
use Angujo\Elolara\Model\Relations\MorphedByMany;
use Angujo\Elolara\Model\Relations\MorphMany;
use Angujo\Elolara\Model\Relations\MorphOne;
use Angujo\Elolara\Model\Relations\MorphTo;
use Angujo\Elolara\Model\Relations\MorphToMany;
use Angujo\Elolara\Model\Traits\HasMetaData;
use Angujo\Elolara\Model\Traits\HasTemplate;
use Angujo\Elolara\Model\Traits\ImportsClass;
use Angujo\Elolara\Model\Traits\UsesTraits;
use Angujo\Elolara\Util;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Model
 *
 * @package Angujo\Elolara\Model
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
    : Model
    {
        $me             = new self($table);
        $me->namespace  = Config::models_namespace();
        $me->name       = Util::className($table->name);
        $me->base_model = $for_base;
        if (true === $for_base) {
            $me->abstract      = 'abstract ';
            $me->template_name = 'abstract-model';
            $me->namespace     = Config::abstracts_namespace();
            $me->name          = Util::baseClassName($table->name);
            $me->child         = Util::className($table->name);
            if (Config::db_directories()) {
                $me->parent = basename(Config::schema_model_name());
                $me->addImport(Config::schema_model_fqdn());
            } else {
                $me->parent = basename(Config::super_model_name());
                $me->addImport(Config::super_model_fqdn());
            }
        } elseif (false === $for_base) {
            $me->addImport($pns = Config::abstracts_namespace().'\\'.Util::baseClassName($table->name));
            $me->parent = basename($pns);
        }
        if (false !== $for_base) {
            $me->addTrait(...\Arr::wrap(Config::table_traits()[$table->name] ?? []));
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
            $this->_phpdoc_props[] = PhpDocProperty::fromColumn($column);
        }
        $this->_properties[] = ModelProperty::forTableName($this->table);

        $this->_properties[] = $prKeys = ModelProperty::forPrimaryKey($this->table);
        if ($prKeys) {
            $this->addTrait($prKeys->traits());
            $this->addImport($prKeys->imports());
        }

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
        HasManyThrough::fromTables($this->table, $pTable, $eTable, $this);
    }

    protected function hasOneThrough(DBTable $pTable, DBTable $eTable)
    {
        HasOneThrough::fromTables($this->table, $pTable, $eTable, $this);
    }

    protected function morphToFromMT($name, array $tables)
    {
        MorphTo::fromTable($name, $this, $tables, $this->table->nullableMorphTo($name));
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
            MorphToMany::fromTable($name, $column, $_table, $this);
        }
    }

    protected function morphManyFromMM(string $name, DBTable $morphTable)
    {
        if ($morphTable->uniqueMorph($name)) {
            return;
        }
        MorphMany::fromTable($name, $morphTable, $this);
    }

    protected function morphOneFromMM(string $name, DBTable $morphTable)
    {
        if (!$morphTable->uniqueMorph($name)) {
            return;
        }
        MorphOne::fromTable($name, $morphTable, $this);
    }


    protected function belongsToManyRelation()
    {
        if (!$this->table->has_pivot) {
            return;
        }
        BelongsToMany::fromTable($this->table, $this);
    }


    protected function belongsToFromFK(DBForeignConstraint $foreignKey)
    {
        BelongsTo::fromForeignKey($foreignKey, $this);
    }

    protected function belongsToFromColumn(DBColumn $column)
    {
        if (blank($column->probable_table)) {
            return;
        }
        BelongsTo::fromColumn($column, $this);
    }

    protected function hasOneFromRefFK(DBForeignConstraint $foreignKey)
    {
        if (!$foreignKey->column->is_unique || $foreignKey->column->is_multi_unique) {
            return;
        }
        HasOne::fromForeignKey($foreignKey, $this);
    }


    protected function hasManyFromRefFK(DBForeignConstraint $foreignKey)
    {
        if ($foreignKey->column->is_unique) return;
        HasMany::fromForeignKey($foreignKey, $this);
    }

    protected function morphedByManyFromRefFK(DBForeignConstraint $foreignKey)
    {
        if ($foreignKey->column->is_unique || blank($morphTos = $foreignKey->table->morph_tos)) {
            return;
        }
        foreach ($morphTos as $name => $tables) {
            foreach ($tables as $_table) {
                MorphedByMany::fromTable($name, $foreignKey->column, $_table, $this);
            }
        }
    }


    public function setConnection(string $name)
    {
        $this->_properties[] = ModelProperty::forConnection($name);
        return $this;
    }

    /**
     * @param RelationshipFunction|DBColumn $source
     */
    public function setPhpDocProperty($source)
    : ?PhpDocProperty
    {
        if (!is_object($source) || !(is_a($source, RelationshipFunction::class) || is_a($source, DBColumn::class))) {
            return null;
        }
        if (is_a($source, RelationshipFunction::class)) {
            $pDoc = PhpDocProperty::fromRelationFunction($source);
        } elseif (is_a($source, DBColumn::class)) $pDoc = PhpDocProperty::fromColumn($source);
        else return null;
        return $this->_phpdoc_props[$source->name] = $pDoc;
    }

    /**
     * @param RelationshipFunction $fn
     *
     * @return RelationshipFunction
     */
    public function setFunction(RelationshipFunction $fn)
    {
        $this->addImport(...$fn->imports());
        $this->addImport(...$fn->_relations);
        $this->setPhpDocProperty($fn);
        return $this->_functions[$fn->name] = $fn;
    }

    public function functionExist($name)
    {
        return array_key_exists($name, $this->_functions);
    }

    public function phpDocExist($name)
    {
        return array_key_exists($name, $this->_phpdoc_props);
    }
}