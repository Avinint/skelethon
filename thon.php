<?php

define('ARRAY_ALL', true);
define('ARRAY_ANY', false);
const DS = DIRECTORY_SEPARATOR;

require 'lib/Spyc/Spyc.php';

spl_autoload_register('autoloader');

$argv = array_replace(array_fill(0, 5, ''), $argv);
array_shift($argv);

new Core\ModuleMakerCommand($argv);

function autoloader($class_name)
{
    $class_name = str_replace('\\', DS, $class_name);
    if (file_exists(__DIR__.DS.$class_name.'.php')) {
        require $class_name.'.php';
    }
}

function array_contains($needle, array $haystack, bool $all = false, $has_nested_arrays = false)
{
    if (is_array($needle)) {
        if ($has_nested_arrays) {
            return strpos(serialize($needle), serialize($haystack)) !== false;
        } elseif ($all) {
            return empty(array_diff($needle, $haystack));
        }
        return !empty(array_intersect($needle, $haystack));
    }

    return isset(array_flip($haystack)[$needle]);
}

function get_config()
{
    return file_exists(__DIR__ . DS . 'config.yml') ? Spyc::YAMLLoad(__DIR__ . DS . 'config.yml') : [];
}

