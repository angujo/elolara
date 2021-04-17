<?php
/**
 * @author       bangujo ON 2021-04-12 14:08
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile DBColumn.php
 */

namespace Angujo\LaravelModel\Database;


use Angujo\LaravelModel\Database\Traits\BaseDBClass;
use Angujo\LaravelModel\Database\Traits\HasComment;
use Angujo\LaravelModel\Database\Traits\HasName;
use Angujo\LaravelModel\Util;

/**
 * Class DBColumn
 *
 * @package Angujo\LaravelModel\Database
 *
 * @property string  $table_name
 * @property string  $name
 * @property string  $ordinal
 * @property string  $default
 * @property boolean $is_nullable
 * @property boolean $is_primary
 * @property boolean $is_multi_primary
 * @property boolean $is_generated
 * @property string  $type
 * @property string  $character_length
 * @property string  $column_type
 * @property string  $column_key
 * @property string  $extra
 * @property string  $comment
 */
class DBColumn extends BaseDBClass
{
    /** @var DatabaseSchema */
    protected $db;

    public function __construct(DatabaseSchema $schema, $values = [])
    {
        $this->db = $schema;
        parent::__construct($values);
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

    protected function is_generated()
    {
        return false !== stripos($this->extra, 'DEFAULT_GENERATED') || false !== stripos($this->extra, 'auto_increment');
    }

    protected function table()
    {
        return $this->db->getTable($this->table_name);
    }
}