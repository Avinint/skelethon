<?php

use Core\ProjectType;

const DS = DIRECTORY_SEPARATOR;
require __DIR__.DS.'lib'.DS.'Spyc'.DS.'Spyc.php';
require __DIR__.DS.'functions.php';

spl_autoload_register('autoloader');
[$type, $arguments] = getArguments($argv);

$allowed_types = ['e2d', 'esm', 'eto', 'ddd', 'g4e', 'ora'];

if (!array_contains($type, $allowed_types)) {
    throw new InvalidArgumentException('type d\'application incorrect');
}

$projectType = new ProjectType($type, basename(getcwd()));
$moduleMakerFactory = $projectType->getConcreteClassName('ModuleMakerFactory');

new $moduleMakerFactory($projectType, $arguments, __DIR__);