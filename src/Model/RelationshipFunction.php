<?php
/**
 * @author       bangujo ON 2021-04-21 09:13
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile RelationshipFunction.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Relations\HasMany;
use Angujo\Elolara\Model\Relations\RelationKeysInterface;
use Angujo\Elolara\Model\Traits\HasTemplate;
use Angujo\Elolara\Model\Traits\ImportsClass;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class RelationshipFunction
 *
 * @package Angujo\Elolara\Model
 */
abstract class RelationshipFunction implements RelationKeysInterface
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'relation-function';

    public $phpdoc_description;
    public $phpdoc_return;
    public $keys              = '';
    public $_relations        = [];
    public $implied_relations = [];
    public $classes           = '';
    public $model_class       = '';
    public $rel_method;
    public $is_nullable       = false;
    public $data_types        = [];
    public $name;
    public $rel_extend;


    public $content;

    public function __construct(string $relation, $modelClass)
    {
        $this->model_class   = $modelClass;
        $this->rel_method    = function_name_single(basename(static::class));
        $this->phpdoc_return = class_name(basename($relation));
        $this->addImport($relation);
    }

    protected function preProcessTemplate()
    {
        if (empty($this->_relations)) {
            return;
        }
        $this->classes =
            implode(', ', array_map(function ($cl) { return class_path($cl, true, true); }, $this->_relations));
        if (is_array($this->keys)) {
            $this->keys = implode(', ', array_map(function ($k) { return var_export($k, true); }, $this->keys));
        }
        $this->keys = $this->keys ? ", {$this->keys}" : '';
    }

    protected function autoload()
    {
        $relations = array_unique(array_merge($this->_relations, $this->implied_relations));
        $this->addImport(...array_filter($relations, function ($cl) {
            return 0 !== strcasecmp(basename($cl), $this->model_class) &&
                   (Config::full_namespace_import() || Config::base_abstract() ||
                    (!Config::full_namespace_import() && false === stripos($cl, Config::namespace())));
        }));
    }

    /**
     * @param Model $model
     * @param DBColumn|DBForeignConstraint $relation
     * @param DBTable|null $parentTable
     * @param DBTable|null $endTable
     */
    protected function columnarRelationName(Model $model, $relation, DBTable $parentTable = null, DBTable $endTable = null)
    {
        if (!is_object($relation) ||
            !(is_a($relation, DBColumn::class) || is_a($relation, DBForeignConstraint::class))) {
            return null;
        }
        switch (static::class) {
            case Relations\HasOne::class:
                if (in_array($relation->column_name, [\Str::plural($relation->referenced_table_name) . '_' .
                                                      Config::LARAVEL_ID, $relation->referenced_table->foreign_column_name])) {
                    return function_name_single($relation->table_name);
                }
                return function_name_single($relation->table_name . '_' .
                                            preg_replace('/_' . Config::LARAVEL_ID . '$/', '', $relation->column_name));
            // return function_name_single(preg_replace(['/'.$relation->referenced_table->foreign_column_name.'$/', '/'.\Str::plural($relation->referenced_table_name).'_'.Config::LARAVEL_ID.'$/'], '', $relation->column_name).'_'.$relation->table_name);
            case HasMany::class:
                if (in_array($relation->column_name, [\Str::plural($relation->referenced_table_name) . '_' .
                                                      Config::LARAVEL_ID, $relation->referenced_table->foreign_column_name])) {
                    return $relation->table->relation_name_plural;
                }
                return function_name_plural(preg_replace(['/' . $relation->referenced_table->foreign_column_name .
                                                          '$/', '/' . \Str::plural($relation->referenced_table_name) .
                                                                '_' . Config::LARAVEL_ID .
                                                                '$/'], '', $relation->column_name) . '_' .
                                            $relation->table_name);
        }
        return null;
    }

    public static function relationName(DBForeignConstraint $constraint, bool $reference = false, $singular = true, array &$loads = [])
    {
        $sequence = Config::relation_naming();
        $relation = $singular ? 'relation_name_singular' : 'relation_name_plural';
        foreach ($sequence as $key) {
            $name = null;
            switch (strtolower($key)) {
                case 'column':
                    $name = ($reference ? $constraint->referenced_column : $constraint->column)->{$relation};
                    break;
                case 'table':
                    $name = ($reference ? $constraint->table : $constraint->referenced_table)->{$relation};
                    break;
                case 'constraint':
                    $name = $constraint->{$relation};
                    break;
            }
            if (is_null($name) || in_array($name, $loads)) {
                continue;
            }
            return $loads[] = $name;
        }
        return null;
    }
}