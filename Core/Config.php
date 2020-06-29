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
     * @param string $module
     * @param string $field
     * @return Config|string|null
     */
    public static function get($module = 'main', $field = '')
    {
        $config = self::$configs[$module];
        return !empty($field) ? $config[$field] ?? null : $config;
    }

//    public static function set($module = 'main', $field = '', $value = '')
//    {
//        if (isset($value)) {
//            self::$configs[$module]->data[$field] = $value;
//
//            self::$configs[$module]->write();
//        }
//    }

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

    public static function create($module = 'main')
    {
        if (!isset(static::$configs[$module])) {
            static::$configs[$module] = new static($module);
        }
        
        return static::$configs[$module];
    }

    public function write()
    {
        $this->createFile($this->path, Spyc::YAMLDump($this->data, 4, 40, true), true);
    }

    private function getPath($module = '')
    {
        return dirname(__DIR__) .DS .($module !== 'main' ? $module. '_' : '').'config.yml';
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

    public function offsetGet($offset) {
        if ($offset === null) {
            return isset($this->data);
        }
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function count()
    {
        return count($this->data);
    }
}