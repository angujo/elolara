<?php
/**
 * @author       bangujo ON 2021-04-18 19:47
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile DataType.php
 */

namespace Angujo\LaravelModel\Database;


use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Carbon\Carbon;

/**
 * Class DataType
 *
 * @package Angujo\LaravelModel\Database
 *
 * @property boolean $isBool
 * @property boolean $isChar
 * @property boolean $isDecimal
 * @property boolean $isFloat
 * @property boolean $isInteger
 * @property boolean $isSmallint
 * @property boolean $isTinyint
 * @property boolean $isBigint
 * @property boolean $isTimestamp
 * @property boolean $isTime
 * @property boolean $isVarchar
 * @property boolean $isJson
 *
 */
class DataType
{
    use ImportsClass;

    /**
     * Name
     *
     * @var string
     */
    protected $type;
    /**
     * Raw name with lengths attached
     *
     * @var string
     */
    protected $column_type;
    /**
     * Maximum allowed number of characters or digit size
     *
     * @var int
     */
    protected $character_length;

    protected function __construct(){ }

    public static function fromColumn(DBColumn $column)
    {
        $me                   = new self();
        $me->type             = $column->type;
        $me->column_type      = $column->column_type;
        $me->character_length = $column->character_length;
        if (!$me->character_length) {
            $me->character_length = (int)preg_replace('/^(\w+)(\s+)?\((\d+)\)/', '$3', $me->column_type);
        }
        return $me;
    }

    public function __get($name)
    {
        if (!preg_match('/^is/', $name)) {
            return null;
        }
        $name = strtolower(preg_replace('/^is/', '', $name));
        return 0 === strcasecmp($name, $this->phpName(true));
    }

    public function phpName($check = false)
    {
        switch ($this->groupName()) {
            case 'bool':
                return 'boolean';
            case 'decimal':
            case 'float':
                return 'float';
            case 'tinyint':
                if (1 === $this->character_length) {
                    return 'boolean';
                }
            case 'int':
            case 'smallint':
            case 'bigint':
                return 'integer';
            case 'date':
            case 'timestamp':
                if ($check) {
                    return 'timestamp';
                }
                $this->addImport(Carbon::class);
                return basename(Carbon::class);
            case 'time':
            case 'char':
            case 'varchar':
                return 'string';
            case 'json':
                return 'array';
            default:
                return 'mixed';
        }
    }

    protected function groupName()
    {
        $type = explode(' ', strtolower(trim($this->type)));
        if (count($type) == 2 && 0 === strcasecmp('unsigned', $type[1])) {
            $type = [$type[0]];
        }
        $type = implode(' ', $type);
        switch ($type) {
            case 'boolean':
            case 'bool':
                return 'bool';
            case 'character':
            case 'char':
            case 'bpchar':
                return 'char';
            case 'numeric':
            case 'decimal':
            case 'money':
            case 'decimal unsigned':
                return 'decimal';
            case 'real':
            case 'float4':
            case 'float':
            case 'double':
            case 'double unsigned':
            case 'float8':
                return 'float';
            case 'integer':
            case 'int':
            case 'int unsigned':
            case 'int4':
            case 'mediumint':
                return 'int';
            case 'serial':
            case 'serial4':
            case 'int2':
            case 'smallint':
                return 'smallint';
            case 'tinyint':
            case 'smallserial':
            case 'serial2':
                return 'tinyint';
            case 'bigint':
            case 'int8':
            case 'bigserial':
            case 'serial8':
                return 'bigint';
            case 'timestamp':
            case 'datetime':
            case 'timestamptz':
                return 'timestamp';
            case 'time':
            case 'timetz':
                return 'time';
            case 'macaddr':
            case 'macaddr8':
            case 'varchar':
            case 'character varying':
                return 'varchar';
            case 'json':
            case 'jsonb':
                return 'json';
            case 'varbit':
            case 'bit':
            case 'box':
            case 'bytea':
            case 'cidr':
            case 'circle':
            case 'date':
            case 'inet':
            case 'interval':
            case 'line':
            case 'lseg':
            case 'path':
            case 'pg_lsn':
            case 'point':
            case 'polygon':
            case 'tsquery':
            case 'tsvector':
            case 'txid_snapshot':
            case 'uuid':
            case 'xml':
            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'enum':
            case 'set':
            default:
                return strtolower(trim($type));
        }
    }
}