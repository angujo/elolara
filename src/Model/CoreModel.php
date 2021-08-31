<?php
/**
 * @author       bangujo ON 2021-04-18 17:42
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Model.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DBColumn;
use Angujo\Elolara\Database\DBForeignConstraint;
use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Model\Relations\BelongsTo;
use Angujo\Elolara\Model\Relations\BelongsToMany;
use Angujo\Elolara\Model\Relations\HasMany;
use Angujo\Elolara\Model\Relations\HasManyThrough;
use Angujo\Elolara\Model\Relations\HasOne;
use Angujo\Elolara\Model\Relations\HasOneThrough;
use Angujo\Elolara\Model\Relations\MorphedByMany;
use Angujo\Elolara\Model\Relations\MorphMany;
use Angujo\Elolara\Model\Relations\MorphOne;
use Angujo\Elolara\Model\Relations\MorphTo;
use Angujo\Elolara\Model\Relations\MorphToMany;
use Angujo\Elolara\Model\Traits\HasMetaData;
use Angujo\Elolara\Model\Traits\HasTemplate;
use Angujo\Elolara\Model\Traits\ImportsClass;
use Angujo\Elolara\Model\Traits\UsesTraits;
use Angujo\Elolara\Util;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Model
 *
 * @package Angujo\Elolara\Model
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

    public $_functions = [];

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
        $me = new self();
        if (Config::validation_rules()) {
            $me->_functions[] = FunctionAbs::forValidation();
            if (Config::validate_on_save()) $me->_functions[] = FunctionAbs::forValidationSave();
        }
        return $me;
    }
}