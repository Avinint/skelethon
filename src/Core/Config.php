<?php

namespace Core;

use ArrayAccess;
use Countable;
use \Spyc ;

class Config implements ArrayAccess, Countable
{
    use FileManager;

    private static $configs = [];
    private $path;
    private $data;

    public function __construct($module)
    {
        $this->path = $this->getPath($module);
        $this->module = $module;
        $this->data = file_exists($this->path) ? Spyc::YAMLLoad($this->path) : [];
    }

    /**
     * @param string $field
     * @return array|mixed|null
     */
    public function get(string $field = '')
    {
        return !empty($field) ? ($this->data[$field] ?? null) : $this->data;
    }

    public function getFromModel($model, string $field)
    {
        if (isset($this->data['models'][$model][$field])) {
            return $this->data['models'][$model][$field];
        }
        return null ;
    }

    /**
     * @param string $field
     * @param null $value
     */
    public function set($field = '', $value = null)
    {
        if (isset($value)) {
            self::$configs[$this->module][$field] = $value;
        }
        $this->write();
    }

    public function setForModel($model, $field = '', $value = null)
    {
        if (isset($value)) {
            if (!isset($this->data['models'][$model])) {
                $this->data['models'][$model] = [];
            }

            $this->data['models'][$model][$field] = $value;
        }

        $this->write();
    }

//    public static function create($module = 'main')
//    {
//        if (!isset(static::$configs[$module])) {
//            static::$configs[$module] = new static($module);
//        }
//
//        return static::$configs[$module];
//    }

    public function write()
    {
        $this->createFile($this->path, Spyc::YAMLDump($this->data, 4, 40, true), true);
    }

    private function getPath($module = '')
    {
        return dirname(dirname(__DIR__)) .DS .($module !== 'main' ? $module. '_' : '').'config.yml';
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {

        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset = null) {
        if ($offset === null) {
            return $this->data;
        }
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function count()
    {
        return count($this->data);
    }
}