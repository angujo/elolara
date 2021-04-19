<?php
/**
 * @author       bangujo ON 2021-04-17 08:01
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile HasTemplate.php
 */

namespace Angujo\LaravelModel\Model\Traits;


/**
 * Class HasTemplate
 *
 * @package Angujo\LaravelModel\Model\Traits
 */
trait HasTemplate
{
    public function __toString()
    {
        return (string)$this->processTemplate();
    }

    /**
     * @return string
     */
    protected function processTemplate()
    {
        if (!property_exists($this, 'template_name') || !file_exists($path = LM_TPL_DIR.$this->template_name.'.tpl')) {
            return '';
        }
        $content = file_get_contents($path);
        $vars    = get_object_vars($this);
        foreach ($vars as $var => $value) {
            if (is_null($value)) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(preg_match('/^_/', $var) ? "\n" : '|', array_map(function($v){ return (string)$v; }, (array_filter($value))));
            }
            $value   = (string)$value;
            $var     = preg_replace('/^[_]+/', '', $var);
            $content = preg_replace('/\{(\s+)?'.$var.'(\s+)?\}/', $value, $content);
        }
        $content = preg_replace('/{(\s+)?\w+(\s+)?}/', '', $content);
        return (string)$content;
    }
}