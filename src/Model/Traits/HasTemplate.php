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
        return $this->processTemplate();
    }

    /**
     * @return string
     */
    protected function processTemplate()
    {
        if (!property_exists($this, 'template_name') || !file_exists($path = LM_TPL_DIR.$this->template_name.'.tpl')) {
            return '';
        }
        if (method_exists($this, 'preProcessTemplate')) {
            $this->preProcessTemplate();
        }
        $content = file_get_contents($path);
        $keys    = [];
        preg_match_all('/{(\w+)}/', $content, $keys);

        foreach ($keys[0] as $key) {
            $key=preg_replace('/[}{]+/', '', $key);
            if (!property_exists($this, $key) && !property_exists($this, "_{$key}")) continue;
            $var=property_exists($this, "_{$key}")?"_{$key}":$key;
            $value=$this->{$var};
            if (is_null($value)) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(preg_match('/^_/', $var) ? "\n" : '|', array_map(function($v){ return (string)$v; }, (array_filter($value))));
            }
            $content = preg_replace('/\{(\s+)?'.$key.'(\s+)?\}/',(string) $value, $content);
        }
        $content = preg_replace('/([\s\n]+)?{(\s+)?\w+(\s+)?}/', '', $content);
        return (string)$content;
    }
}