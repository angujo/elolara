<?php
/**
 * @author       bangujo ON 2021-04-21 09:13
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile RelationshipFunction.php
 */

namespace Angujo\Elolara\Model;


use Angujo\Elolara\Config;
use Angujo\Elolara\Model\Traits\HasTemplate;
use Angujo\Elolara\Model\Traits\ImportsClass;

/**
 * Class FunctionAbs
 *
 * @package Angujo\Elolara\Model
 */
class FunctionAbs
{
    use HasTemplate, ImportsClass;

    protected $template_name = 'function';

    public $phpdoc_description;
    public $phpdoc_return;
    public $phpdoc_params;
    public $access  = 'public';
    public $name;
    public $args    = '';
    public $content = '';

    private static $var_regx = '/^[a-zA-Z][a-zA-Z0-9_]+$/';


    public function __construct(string $name, array $args = [])
    {

        $this->name = $name;
        $this->args = implode(',', array_filter(array_map(function($k, $v){
            $key   = is_numeric($k) && 1 === preg_match(self::$var_regx, $v) ? $v : (1 === preg_match(self::$var_regx, $k) ? $k : null);
            $value = is_numeric($k) ? null : var_export($v, true);
            return !$key ? null : '$'.$key.(!is_string($value) ? '' : ' = '.$value);
        }, array_keys($args), $args)));
    }

    protected function autoload()
    {

    }

    public static function forValidation()
    {
        if (!Config::validation_rules() || 1 !== preg_match(self::$var_regx, Config::validation_method())) return null;
        $me                     = new self(Config::validation_method());
        $me->phpdoc_description = '* Method to call validation for attributes!';
        $me->content            = <<<'N'
        if (!property_exists($this, 'rules') || !is_array($this->>rules)) return;
        $validator = \Validator::make($this->getAttributes(), $this->rules);
        if($validator->fails()) throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json($validator->errors()->messages(),422));
N;
        return $me;
    }

    public static function forValidationSave()
    {
        if (!Config::validation_rules() || 1 !== preg_match(self::$var_regx, Config::validation_method())) return null;
        $fn                     = Config::validation_method();
        $me                     = new self('saving', ['callback' ]);
        $me->access             = 'public static';
        $me->phpdoc_description = '* @inherit';
        $me->content            = <<<N
        \$call=function(self \$elolara)use (&\$callback){
            \$callback(\$elolara);
            \$elolara->{$fn}();
        };
        parent::saving(\$call);
N;
        return $me;
    }
}