<?php
/**
 * @author       bangujo ON 2021-04-29 18:36
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile HasMetaData.php
 */

namespace Angujo\LaravelModel\Model\Traits;


/**
 * Trait HasMetaData
 *
 * @package Angujo\LaravelModel\Model\Traits
 */
trait HasMetaData
{
    public $php_version = PHP_VERSION;
    public $lm_author   = LM_AUTHOR;
    public $lm_name   = LM_APP_NAME;
    public $lm_version   = LM_VERSION;
}