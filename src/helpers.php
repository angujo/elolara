<?php
/**
 * @author       bangujo ON 2021-04-20 23:47
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile helpers.php
 */

if (!function_exists('array_export')) {
    function array_export(array $arr, $print = false)
    {
        $v = var_export($arr, !$print);
        if ($v && !$print) {
            return preg_replace(['/(\s+)\d+(\s+)?\=\>(\s+)?/', '/(,|\()[\n\r]+/',], ['$1', '$1 '], $v);
        }
        return null;
    }
}