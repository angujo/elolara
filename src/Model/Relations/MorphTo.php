<?php
/**
 * @author       bangujo ON 2021-04-27 15:45
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile MorphTo.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\MorphTo as LaravelMorphTo;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Class MorphTo
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class MorphTo extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelMorphTo::class, $model_class); }

    /**
     * @param string          $name
     * @param string          $model_class
     * @param array|DBTable[] $tables
     * @param bool            $nullable
     *
     * @return MorphTo
     */
    public static function fromTable(string $name, string $model_class, array $tables,bool $nullable = false)
    {
        $me = new self($model_class);
        $me->is_nullable = $nullable;
        foreach ($tables as $table) {
            $me->data_types[]        = $table->class_name;
            $me->implied_relations[] = $table->fqdn;
        }
        $me->name               = function_name_single($name);// ($name);
        $me->phpdoc_description = "* MorphTo method for the models ".implode(', ', $me->data_types);
        $me->autoload();

        return $me;
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}