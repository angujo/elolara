<?php
/**
 * @author       bangujo ON 2021-04-28 19:17
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile HasOneThrough.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\RelationshipFunction;
use Angujo\Elolara\Util;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as LaravelHasManyThrough;

/**
 * Class HasOneThrough
 *
 * @package Angujo\Elolara\Model\Relations
 */
class HasManyThrough extends RelationshipFunction
{
    public function __construct(string $model_class){ parent::__construct(LaravelHasManyThrough::class, $model_class); }

    public static function fromTables(DBTable $primaryTable, DBTable $pivotTable, DBTable $endTable, $model_class)
    {
        $pri_column = $primaryTable->primary_column;
        $piv_column = $pivotTable->relationColumn($primaryTable);
        $end_column = $endTable->relationColumn($pivotTable);
        if (!$pri_column || !$piv_column || !$end_column) return null;

        $me                     = new self($model_class);
        $me->name               = function_name_single($pivotTable->name).Util::className($endTable->name);
        $me->_relations[]       = $endTable->fqdn;
        $me->_relations[]       = $pivotTable->fqdn;
        $me->data_types[]       = $endTable->class_name;
        $me->is_nullable        = $piv_column->is_nullable || $end_column->is_nullable;
        $me->phpdoc_description = "* Relation method to reach-out and get {$endTable->class_name}";
        $me->addImport($pivotTable->fqdn, $endTable->fqdn);
        $me->keys = relation_keys([$primaryTable->foreign_column_name, $piv_column->name], [$pivotTable->foreign_column_name, $end_column->name], [Config::LARAVEL_ID, $pri_column->name], [Config::LARAVEL_ID, $pivotTable->primary_column->name]);
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