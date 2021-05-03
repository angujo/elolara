<?php
/**
 * @author       bangujo ON 2021-04-12 14:08
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile DBColumn.php
 */

namespace Angujo\Elolara\Database;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\Traits\BaseDBClass;
use Angujo\Elolara\Database\Traits\HasComment;
use Angujo\Elolara\Database\Traits\HasName;
use Angujo\Elolara\Util;

/**
 * Class DBColumn
 *
 * @package Angujo\Elolara\Database
 *
 * @property string              $table_name
 * @property string              $name
 * @property string              $ordinal
 * @property string              $default
 * @property string              $relation_name_singular
 * @property string              $relation_name_plural
 * @property boolean             $is_nullable
 * @property boolean             $is_primary
 * @property boolean             $is_multi_primary
 * @property boolean             $is_unique
 * @property boolean             $is_multi_unique
 * @property boolean             $is_generated
 * @property boolean             $is_auto_incrementing
 * @property string              $type
 * @property string              $character_length
 * @property string              $column_type
 * @property string              $column_key
 * @property string              $extra
 * @property string              $comment
 * @property DataType            $data_type
 * @property DBForeignConstraint $foreign_key
 * @property DBTable|null        $probable_table Table likely to be related to the column but without foreign key
 */
class DBColumn extends BaseDBClass
{
    /** @var DatabaseSchema */
    protected $db;

    public function __construct(DatabaseSchema $schema, $values = [])
    {
        $this->db = $schema;
        parent::__construct($values);
        $this->_setProp('data_type', DataType::fromColumn($this));
    }

    protected function probable_table()
    {
        /** @var DBTable $table */
        return ($this->foreign_key || $this->is_primary || $this->is_auto_incrementing || $this->is_generated ||
            !($table = $this->db->getRelatableTable(preg_replace(Config::column_relation_regex(), '$1', $this->name), $this->name)) ||
            !$table->primary_column || 0 !== strcasecmp($this->data_type->phpName(), $table->primary_column->data_type->phpName())) ? null : $table;
    }

    protected function relation_name_singular()
    {
        return function_name_single($this->relation_name());
    }

    protected function relation_name_plural()
    {
        return function_name_plural($this->relation_name());
    }

    private function relation_name()
    {
        if (false === stripos(Config::column_relation_pattern(), '{relation_name}')) {
            return $this->name;
        }
        $regx = '/'.str_ireplace('{relation_name}', '(\w+)', Config::column_relation_pattern()).'/';
        return preg_replace($regx, '$1', $this->name);
    }

    protected function is_nullable()
    {
        return Util::booleanValue($this->nullable);
    }

    protected function is_primary()
    {
        return !$this->is_nullable && 0 === stripos($this->column_key, 'pri') && !empty($this->db->getPrimaryConstraint($this->table_name, null, $this->name));
    }

    protected function is_multi_primary()
    {
        return 1 < count($this->db->getPrimaryConstraint($this->table_name, null, $this->name));
    }

    protected function is_unique()
    {
        return !empty($this->db->getUniqueConstraint($this->table_name, null, $this->name));
    }

    protected function is_multi_unique()
    {
        return 1 < count($this->db->getUniqueConstraint($this->table_name, null, $this->name));
    }

    protected function foreign_key()
    {
        return $this->db->getForeignKey($this->table_name, null, $this->name);
    }

    protected function is_generated()
    {
        return false !== stripos($this->extra, 'DEFAULT_GENERATED') || false !== stripos($this->extra, 'auto_increment');
    }

    protected function is_auto_incrementing()
    {
        return false !== stripos($this->extra, 'auto_increment');
    }

    protected function table()
    {
        return $this->db->getTable($this->table_name);
    }
}