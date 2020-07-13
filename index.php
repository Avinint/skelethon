<?php

require __DIR__.DIRECTORY_SEPARATOR.'functions.php';
require __DIR__.DS.'lib'.DS.'Spyc'.DS.'Spyc.php';

function autoloader($class_name)
{
    $class_name = str_replace('\\', DS, 'src'.DS.$class_name);
    if (file_exists(__DIR__.DS.$class_name.'.php')) {
        require $class_name.'.php';
    }
}

spl_autoload_register('autoloader');

$argv = array_replace(array_fill(0, 5, ''), $argv);
array_shift($argv);

new Core\ModuleMakerFactory($argv, E2DModuleMaker::class, E2DModelMaker::class, EtoDatabaseAccess::class);