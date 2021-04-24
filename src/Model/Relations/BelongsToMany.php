<?php
/**
 * @author       bangujo ON 2021-04-24 19:55
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile BelongsToMany.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\RelationshipFunction;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as LaravelBelongsToMany;

/**
 * Class BelongsToMany
 *
 * @package Angujo\LaravelModel\Model\Relations
 */
class BelongsToMany extends RelationshipFunction
{
    public function __construct($modelClass){ parent::__construct(LaravelBelongsToMany::class, $modelClass); }

    public static function fromTable(DBTable $table)
    {

    }

    /**
     * @inheritDoc
     */
    function keyRelations($source)
    {
        // TODO: Implement keyRelations() method.
    }
}