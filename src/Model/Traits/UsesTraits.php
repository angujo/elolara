<?php
/**
 * @author       bangujo ON 2021-04-28 07:50
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile UsesTraits.php
 */

namespace Angujo\LaravelModel\Model\Traits;


use Angujo\LaravelModel\Model\ModelUses;

/**
 * Trait UsesTraits
 *
 * @package Angujo\LaravelModel\Model\Traits
 */
trait UsesTraits
{
    protected $traits = [];

    protected function addTrait(...$imports)
    {
        $trait = array_filter(array_map(function($i){ return is_string($i) ? $i : null; }, \Arr::flatten(func_get_args())),'trait_exists');
        if (method_exists($this, 'addImport')) $this->addImport(...$trait);
        $this->traits = array_unique(array_merge($this->traits,  $trait));
        return $this;
    }

    public function traits()
    {
        return $this->traits;
    }
}