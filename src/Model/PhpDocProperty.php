<?php
/**
 * @author       bangujo ON 2021-04-17 08:00
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile PhpDocProperty.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Model\Traits\HasTemplate;
use Angujo\Elolara\Model\Traits\ImportsClass;

/**
 * Class PhpDocProperty
 *
 * @package Angujo\Elolara\Model
 */
class PhpDocProperty
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'phpdoc-property';

    public $data_types = [];
    public $name;
    public $description;

    public static function fromColumn(DBColumn $column)
    {
        $me               = new self();
        $me->name         = $column->name;
        $me->description  = $column->comment->content;
        $me->data_types[] = $column->data_type->phpName();
        if ($column->is_nullable) {
            $me->data_types[] = 'null';
        }
        $me->addImport($column->data_type->imports());
        return $me;
    }

    public static function fromRelationFunction(RelationshipFunction $relation)
    {
        $me              = new self();
        $me->name        = $relation->name;
        $me->description = '';
        if (is_array($relation->data_types)) {
            $me->data_types = $relation->data_types;
        } else {
            $me->data_types[] = $relation->data_types;
        }
        if ($relation->is_nullable) {
            $me->data_types[] = 'null';
        }
        return $me;
    }
}