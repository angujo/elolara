<?php
/**
 * @author       bangujo ON 2021-04-16 11:32
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile DBPrimaryConstraint.php
 */

namespace Angujo\LaravelModel\Database;


/**
 * Class DBPrimaryConstraint
 *
 * @package Angujo\LaravelModel\Database
 *
 * @property string       $name
 * @property string       $table_name
 * @property string       $column_name
 * @property string       $relation_name_singular
 * @property string       $relation_name_plural
 * @property DBColumn     $column
 * @property DBTable|null $table
 * @property string       $referenced_table_name
 * @property string       $referenced_column_name
 * @property DBColumn     $referenced_column
 * @property DBTable|null $referenced_table
 */
class DBForeignConstraint extends Traits\BaseDBClass
{
    /** @var DatabaseSchema */
    private $db;

    public function __construct(DatabaseSchema $schema, $values = [])
    {
        $this->db = $schema;
        parent::__construct($values);
    }

    protected function relation_name_singular()
    {
        return function_name_single($this->name);
    }

    protected function relation_name_plural()
    {
        return function_name_plural($this->name);
    }

    protected function column()
    {
        return $this->db->getColumn($this->table_name, $this->column_name);
    }

    protected function table()
    {
        return $this->db->getTable($this->table_name);
    }

    protected function referenced_column()
    {
        return $this->db->getColumn($this->referenced_table_name, $this->referenced_column_name);
    }

    protected function referenced_table()
    {
        return $this->db->getTable($this->referenced_table_name);
    }
}