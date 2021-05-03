<?php
/**
 * @author       bangujo ON 2021-04-18 20:17
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile ImportsClass.php
 */

namespace Angujo\Elolara\Model\Traits;


use Angujo\Elolara\Model\ModelImport;

/**
 * Trait ImportsClass
 *
 * @package Angujo\Elolara\Model\Traits
 */
trait ImportsClass
{
    protected $_imports = [];

    public function addImport(...$imports)
    {
        $import         = array_filter(array_map(function($i){
            return is_string($i) ? ModelImport::fromClass($i) : (is_a($i, ModelImport::class) ? $i : null); }, \Arr::flatten(func_get_args())));
        $this->_imports = array_unique(array_merge($this->_imports, $import));
        return $this;
    }

    public function imports()
    {
        return $this->_imports;
    }
}