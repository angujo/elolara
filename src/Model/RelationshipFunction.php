<?php
/**
 * @author       bangujo ON 2021-04-21 09:13
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile RelationshipFunction.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Relations\RelationKeysInterface;
use Angujo\LaravelModel\Model\Traits\HasTemplate;
use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class RelationshipFunction
 *
 * @package Angujo\LaravelModel\Model
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
        $this->classes = implode(', ', array_map(function($cl){ return class_path($cl, true, true); }, $this->_relations));
        if (is_array($this->keys)) {
            $this->keys = implode(', ', array_map(function($k){ return var_export($k, true); }, $this->keys));
        }
        $this->keys = $this->keys ? ", {$this->keys}" : '';
    }

    protected function autoload()
    {
        $relations = array_unique(array_merge($this->_relations, $this->implied_relations));
        $this->addImport(...array_filter($relations, function($cl){
            return 0 !== strcasecmp(basename($cl), $this->model_class) && (Config::full_namespace_import() || Config::base_abstract() || (!Config::full_namespace_import() && false === stripos($cl, Config::namespace())));
        }));
    }

    public static function relationName(DBForeignConstraint $constraint, array &$loads = [], bool $reference = false, $singular = true)
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