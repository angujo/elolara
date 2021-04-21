<?php
/**
 * @author       bangujo ON 2021-04-21 09:13
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile RelationshipFunction.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Traits\HasTemplate;
use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class RelationshipFunction
 *
 * @package Angujo\LaravelModel\Model
 */
class RelationshipFunction
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'function';

    public $phpdoc_description;
    public $phpdoc_params;
    public $phpdoc_return;
    public $access;
    public $name;
    public $return_name;
    public $return_result;
    public $is_nullable = false;
    public $args;
    public $content;

    /**
     * @param DBForeignConstraint $constraint
     * @param array               $loads
     * @param bool                $reference
     *
     * @return string|null
     */
    public static function relationName(DBForeignConstraint $constraint, array &$loads, bool $reference = false)
    {
        $sequence = Config::relation_naming();
        foreach ($sequence as $key) {
            $name = null;
            switch (strtolower($key)) {
                case 'column':
                    if (false === stripos(Config::column_relation_pattern(), '{relation_name}')) {
                        break;
                    }
                    $regx = '/'.str_ireplace('{relation_name}', '(\w+)', Config::column_relation_pattern()).'/';
                    $name = preg_replace($regx, '$1', $reference ? $constraint->referenced_column_name : $constraint->column_name);
                    break;
                case 'table':
                    $name = $reference ? $constraint->referenced_table_name : $constraint->table_name;
                    break;
                case 'constraint':
                    $name = $constraint->name;
                    break;
                default:
                    $name = null;
                    break;
            }
            if (is_null($name) || in_array($name = !$reference ? function_name_plural($name) : function_name_single($name), $loads)) {
                continue;
            }
            return $loads[] = $name;
        }
        return null;
    }

    /**
     * @param DBTable                $table
     * @param array|PhpDocProperty[] $phpdoc_props
     * @param array                  $imports
     *
     * @return array
     */
    public static function oneToOne(DBTable $table, array &$phpdoc_props, &$imports = [])
    {
        $functions   = [];
        $foreignKeys = $table->foreign_keys;
        $loads       = array_map(function(PhpDocProperty $prop){ return $prop->name; }, $phpdoc_props);
        foreach ($foreignKeys as $foreignKey) {
            $name = self::relationName($foreignKey, $loads, true);
            if (!is_string($name)) {
                continue;
            }
            $has_one             = self::hasOneContent($foreignKey, $name);
            $imports        = array_merge($imports, $has_one->imports());
            $functions[]    = $has_one;
            $phpdoc_props[] = PhpDocProperty::fromRelationFunction($has_one);
        }
        return $functions;
    }

    public static function hasOneContent(DBForeignConstraint $foreignKey, $name)
    {
        $same = false;
        if (0 === strcasecmp(class_name($name), class_name($foreignKey->table->name))) {
            $name = 'parent';
            $same = true;
        }
        $me                     = new self();
        $me->access             = 'public';
        $me->name               = $name;
        $me->args               = '';
        $me->content            = 'return $this->hasOne('.class_path($foreignKey->referenced_table_name, true).', '.merged_columns($foreignKey->referenced_column_name,[$foreignKey->referenced_column_name,$foreignKey->table_name]).');';
        $me->phpdoc_description = '* No Documentation';
        $me->phpdoc_params      = '';
        $me->is_nullable        = $foreignKey->column->is_nullable;
        $me->return_name        = basename(HasOne::class);
        $me->return_result      = basename(class_path($foreignKey->referenced_table_name));
        $me->phpdoc_return      = '* @return '.$me->return_name;
        if (!$same && Config::full_namespace_import()) {
            $me->addImport(class_path($foreignKey->referenced_table_name));
        }
        $me->addImport(class_path(HasOne::class, false, true));
        return $me;
    }
}