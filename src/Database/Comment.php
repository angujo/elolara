<?php

namespace Angujo\Elolara\Database;

use Angujo\Elolara\Config;

/**
 * @property string $ref
 * @property string $reference
 */
class Comment
{
    /**
     * @var null|string
     */
    public $name = null;
    /**
     * @var null|string
     */
    public $content = null;
    /**
     * @var array<string,string>
     */
    private $entries = [];

    public function __construct(?string $content)
    {
        $this->content = $content;
        if ($content && trim($content = preg_replace('/^.*?(\{(\s+)?' . Config::LIBRARY_KEYWORD .
                                                     '(,|:)(.*?)(\s+)?\}).*?$/', '$1', $content)))
            $this->process($content);
    }

    public static function init(?string $content)
    {
        return new self($content);
    }

    private function process(string $content)
    {
        $contents =
            array_map(function ($cnt) { return array_filter(explode(':', $cnt), 'trim'); }, array_filter(explode(',', $content)));
        $key      = array_shift($contents);
        $contents =
            array_map(function ($cnts) { return [$cnts[0], $cnts[1]]; }, array_filter($contents, function ($cnts) {
                return 1 < count($cnts);
            }));
        if (1 < count($key)) $this->name = $key[1];
        $this->entries = collect($contents)->mapWithKeys(function ($cnts) { return [$cnts[0] => $cnts[1]]; })->all();
    }

    public function morphTables()
    {
        $details = $this->tables ?? $this->content;
        return array_filter(preg_split('/[,\s]+/', $details),
        function ($t_name){return strlen($t_name) && preg_match('/^[a-zA-Z][a-zA-Z_0-9]+$/',$t_name);});
    }

    public function __get($name)
    {
        return $this->entries[$name] ?? null;
    }

    public function __set($name, $value)
    {
        throw new \Exception('Setting values not allowed');
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->entries);
    }

    public function __toString()
    {
        return (string)$this->content;
    }
}