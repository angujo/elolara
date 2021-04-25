<?php
/**
 * @author       bangujo ON 2021-04-24 18:46
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile RelationKeysInterface.php
 */

namespace Angujo\LaravelModel\Model\Relations;


use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;

interface RelationKeysInterface
{
    /**
     * @param DBForeignConstraint|DBColumn|DBTable $source
     *
     * @return mixed
     */
    function keyRelations($source);
}