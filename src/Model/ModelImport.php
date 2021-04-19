<?php
/**
 * @author       bangujo ON 2021-04-18 22:26
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile ModelImport.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Model\Traits\HasTemplate;

/**
 * Class ModelImport
 *
 * @package Angujo\LaravelModel\Model
 */
class ModelImport
{
    use HasTemplate;

    protected $template_name = 'import';
    public    $class;

    public static function fromClass($cl_name)
    {
        $me        = new self();
        $me->class = $cl_name;
        return $me;
    }
}