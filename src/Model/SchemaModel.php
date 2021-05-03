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
class SchemaModel
{
    use HasTemplate, ImportsClass, UsesTraits, HasMetaData;

    protected $template_name = 'schema-model';

    public $uses        = '';
    public $namespace;
    public $name;
    public $abstract    = '';
    public $schema_name = '';
    public $parent;
    public $child;
    public $date;

    /** @var DBTable */
    public $table;

    protected function __construct()
    {
        $this->namespace   = Config::extension_namespace();
        $this->schema_name = Config::schema_name();
        $this->name        = Config::schema_model_name();
        $this->parent      = basename(Config::super_model_name());
        $this->traits      = (array)Config::schema_traits();
        $this->date        = date('Y-m-d H:i:s');
        $this->addImport(Config::super_model_fqdn());
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