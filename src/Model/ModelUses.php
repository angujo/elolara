<?php
/**
 * @author       bangujo ON 2021-04-18 22:26
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile ModelImport.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Model\Traits\HasTemplate;

/**
 * Class ModelImport
 *
 * @package Angujo\Elolara\Model
 */
class ModelUses
{
    use HasTemplate;

    protected $template_name = 'uses';
    public    $class;

    public static function fromClass($cl_name)
    {
        $me        = new self();
        $me->class = $cl_name;
        return $me;
    }
}