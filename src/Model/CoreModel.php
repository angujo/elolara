<?php
/**
 * @author       bangujo ON 2021-04-18 17:42
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile Model.php
 */

namespace Angujo\LaravelModel\Model;


use Angujo\LaravelModel\Config;
use Angujo\LaravelModel\Database\DBColumn;
use Angujo\LaravelModel\Database\DBForeignConstraint;
use Angujo\LaravelModel\Database\DBTable;
use Angujo\LaravelModel\Model\Relations\BelongsTo;
use Angujo\LaravelModel\Model\Relations\BelongsToMany;
use Angujo\LaravelModel\Model\Relations\HasMany;
use Angujo\LaravelModel\Model\Relations\HasManyThrough;
use Angujo\LaravelModel\Model\Relations\HasOne;
use Angujo\LaravelModel\Model\Relations\HasOneThrough;
use Angujo\LaravelModel\Model\Relations\MorphedByMany;
use Angujo\LaravelModel\Model\Relations\MorphMany;
use Angujo\LaravelModel\Model\Relations\MorphOne;
use Angujo\LaravelModel\Model\Relations\MorphTo;
use Angujo\LaravelModel\Model\Relations\MorphToMany;
use Angujo\LaravelModel\Model\Traits\HasMetaData;
use Angujo\LaravelModel\Model\Traits\HasTemplate;
use Angujo\LaravelModel\Model\Traits\ImportsClass;
use Angujo\LaravelModel\Model\Traits\UsesTraits;
use Angujo\LaravelModel\Util;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Model
 *
 * @package Angujo\LaravelModel\Model
 */
class CoreModel
{
    use HasTemplate, ImportsClass, UsesTraits, HasMetaData;

    protected $template_name = 'core-model';

    public $uses          = '';
    public $relation_name = '';
    public $namespace;
    public $name;
    public $abstract      = '';
    public $parent;
    public $child;
    public $date;

    /** @var DBTable */
    public $table;

    protected function __construct()
    {
        $this->namespace     = Config::extension_namespace();
        $this->name          = Config::super_model_name();
        $this->parent        = basename(Config::model_class());
        $this->traits        = (array)Config::core_traits();
        $this->date          = date('Y-m-d H:i:s');
        $this->relation_name = 'Relation::$morphMap';
        $this->addImport(Relation::class);
        $this->addImport(Config::model_class());
        $this->addImport(...$this->traits);
    }

    protected function preProcessTemplate()
    {
        if (!empty($this->traits)) {
            $this->uses = 'use '.implode(',', array_map('basename', $this->traits())).';';
        }
    }

    public static function load()
    {
        return new self();
    }
}