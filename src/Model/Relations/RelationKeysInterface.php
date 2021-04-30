<?php
/**
 * @author       bangujo ON 2021-04-24 18:46
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile RelationKeysInterface.php
 */

namespace Angujo\Elolara\Model\Relations;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;

interface RelationKeysInterface
{
    /**
     * @param DBForeignConstraint|DBColumn|DBTable $source
     *
     * @return mixed
     */
    function keyRelations($source);
}