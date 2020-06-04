<?php

namespace Core;

use \Spyc ;

class Config
{
    use FileManager;

    private static $configs = [];
    private static $instance;
    private $path;
    private $filename;

    public function __construct($path)
    {
        $this->path = dirname($path).DS;
        $this->filename = str_replace($this->path, '', $path);
    }

    public static function get($module = '')
    {
        $path = self::$instance->getPath($module);
        return file_exists($path) ? Spyc::YAMLLoad($path ) : [];
    }

    public static function create($path)
    {
        if (!isset(static::$instance)) {
            static::$instance = new static($path);
        }
        
        return  static::$instance ;
    }

    public static function write($module, $config)
    {
        self::$instance->createFile( self::$instance->getPath($module), Spyc::YAMLDump($config), true);
    }

    private function getPath($module = '')
    {
        return $this->path.($module? $module. '_' : '').$this->filename;
    }

}