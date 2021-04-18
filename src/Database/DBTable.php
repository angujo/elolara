<?php
/**
 * @author       bangujo ON 2021-04-12 14:07
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile DBTable.php
 */

namespace Angujo\LaravelModel\Database;


use Angujo\LaravelModel\Database\Traits\BaseDBClass;
use Angujo\LaravelModel\Database\Traits\HasComment;
use Angujo\LaravelModel\Database\Traits\HasName;

/**
 * Class DBTable
 *
 * @package Angujo\LaravelModel\Database
 *
 * @property string           $name
 * @property string           $comment
 * @property DBColumn[]|array $columns
 * @property DBColumn[]|array $primary_columns
 */
class DBTable extends BaseDBClass
{
    /** @var DatabaseSchema */
    private $db;

    public function __construct(DatabaseSchema $database, $values = [])
    {
        $this->db = $database;
        parent::__construct($values);
    }

    /**
     * @return DBColumn[]|array
     */
    protected function columns()
    {
        return $this->db->getColumn($this->name);
    }

    protected function primary_columns()
    {
        return array_filter($this->columns, function(DBColumn $col){ return $col->is_primary; });
    }
}