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
 * @property string           $name
 * @property string           $table_name
 * @property string[]|array   $column_names
 * @property DBColumn[]|array $columns
 * @property DBTable|null     $table
 */
class DBPrimaryConstraint extends Traits\BaseDBClass
{
    /** @var DatabaseSchema */
    private $db;

    public function __construct(DatabaseSchema $schema, $values = [])
    {
        $this->db = $schema;
        parent::__construct($values);
        if (isset($this->_props['column_names'])) {
            $this->_props['column_names'] = array_map('trim', explode(',', $this->_props['column_names']));
        }
    }

    protected function columns()
    {
        return $this->db->getColumn($this->table_name, \Arr::wrap($this->column_names));
    }

    protected function table()
    {
        return $this->db->getTable($this->table_name);
    }
}