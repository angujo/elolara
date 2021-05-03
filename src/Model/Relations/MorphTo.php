<?php
/**
 * @author       bangujo ON 2021-04-27 15:45
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile MorphTo.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Model;
use Angujo\Elolara\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\MorphTo as LaravelMorphTo;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Class MorphTo
 *
 * @package Angujo\Elolara\Model\Relations
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
     * @return MorphTo|RelationshipFunction
     */
    public static function fromTable(string $name, Model $model, array $tables, bool $nullable = false)
    {
        $me              = new self($model->name);
        $me->is_nullable = $nullable;
        foreach ($tables as $table) {
            $me->data_types[]        = $table->class_name;
            $me->implied_relations[] = $table->fqdn;
        }
        $me->name               = function_name_single($name);// ($name);
        $me->phpdoc_description = "* MorphTo method for the models ".implode(', ', $me->data_types);
        $me->autoload();

        return  $model->setFunction($me);
    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}