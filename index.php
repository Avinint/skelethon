<?php

use Core\ProjectType;

require __DIR__.DIRECTORY_SEPARATOR.'functions.php';
require __DIR__.DS.'lib'.DS.'Spyc'.DS.'Spyc.php';

spl_autoload_register('autoloader');
[$type, $arguments] = getArguments($argv);

if (!array_contains($type, ['e2d', 'esm', 'eto'])) {
    throw new InvalidArgumentException('type d\'application incorrect');
}

$type = new ProjectType($type, basename(getcwd()));

new Core\ModuleMakerFactory($type, $arguments);
//if ($type === 'eto')  {
//    new Core\ModuleMakerFactory($type, $arguments, Eto\EtoModuleMaker::class, Eto\EtoModelMaker::class, Eto\EtoField::class, Eto\EtoDatabaseAccess::class);
//} elseif ($type === 'esm') {
//    new Core\ModuleMakerFactory($type, $arguments, ESM\ESMModuleMaker::class, ESM\ESMModelMaker::class, ESM\ESMField::class, E2D\E2DDatabaseAccess::class);
//} elseif ($type === 'e2d') {
//    new Core\ModuleMakerFactory($type, E2D\E2DModuleMaker::class, E2D\E2DModelMaker::class, E2D\E2DField::class, E2D\E2DDatabaseAccess::class);
//}
