<?php
/**
 * @author       bangujo ON 2021-04-18 17:42
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile Model.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Config;
use Angujo\Elolara\Database\DatabaseSchema;
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
class MorphModel
{
    use HasTemplate, ImportsClass, UsesTraits, HasMetaData;

    protected $template_name = 'core-morph-model';

    public $morphs = '';
    public $namespace;
    public $name;
    public $observers;
    public $spacer = "             ";

    protected function __construct()
    {
        $this->namespace = Config::extension_namespace();
        $this->name      = Config::super_morph_name();
    }

    protected function schemaRun(DatabaseSchema $schema)
    {
        $morph_holder = $obs_holder = [];
        foreach ($schema->tables as $table) {
            # Hold for morphRel
            $morph_holder[$table->name] = var_export(Config::models_namespace().'\\'.Util::className($table->name), true);

            # Run for observers
            $model_class              = '\\'.Config::models_namespace().'\\'.Util::className($table->name);
            $observer_class           = '\\'.Config::observer_namespace().'\\'.class_name(Util::className($table->name).'_'.Config::observer_suffix());
            $obs_holder[$table->name] = "{$model_class}::observe({$observer_class}::class);\n";
        }
        # Compile observers
        $this->observers = implode('        ', $obs_holder);

        # Compile for morphs
        $max          = max(array_map(function($k){ return strlen($k); }, array_keys($morph_holder)));
        $this->morphs = implode(",\n", array_map(function($k, $v) use ($max){
            $sp = '';
            for ($i = 0; $i < ($max - strlen($k)); $i++) {
                $sp .= ' ';
            }
            $k = var_export($k, true).$sp;
            return "{$this->spacer}{$k} => {$v}";
        }, array_keys($morph_holder), array_values($morph_holder)));
        return $this;
    }

    public static function core(DatabaseSchema $schema)
    {
        $me = new self();
        if (Config::db_directories()) $me->name = Util::className($schema->name.'_morph_map');
        return $me->schemaRun($schema);
    }
}