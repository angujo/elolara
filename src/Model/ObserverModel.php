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
class ObserverModel
{
    use HasTemplate, ImportsClass, HasMetaData;

    protected $template_name = 'observer-model';

    public $uses     = '';
    public $namespace;
    public $name;
    public $abstract = '';
    public $date;

    public $retrieved_content   = '//';
    public $creating_content    = '//';
    public $created_content     = '//';
    public $updating_content    = '//';
    public $updated_content     = '//';
    public $saving_content      = '//';
    public $saved_content       = '//';
    public $deleting_content    = '//';
    public $deleted_content     = '//';
    public $restoring_content   = '//';
    public $restored_content    = '//';
    public $replicating_content = '//';

    public $_functions = [];
    public $var_name;
    public $model_name;
    public $model_class;

    /** @var DBTable */
    public $table;

    protected function __construct(Model $model)
    {
        $this->namespace  = Config::observer_namespace();
        $this->name       = class_name($model->name.'_'.Config::observer_suffix());
        $this->date       = date('Y-m-d H:i:s');
        $this->var_name   = '$'.\Str::camel($model->name);
        $this->model_class =   $this->model_name = $model->name;
        $this->addImport($model->namespace.'\\'.$model->name);
    }

    protected function preProcessTemplate()
    {
    }

    public static function load(Model $model)
    {
        $me = new self($model);
        if (Config::validation_rules() && Config::validate_on_save()) {
            $fn                 = Config::validation_method();
            $me->saving_content = "{$me->var_name}->{$fn}();";
        }
        return $me;
    }
}