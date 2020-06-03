<?php

define('ARRAY_ALL', true);
define('ARRAY_ANY', false);
const DS = DIRECTORY_SEPARATOR;

require 'lib/Spyc/Spyc.php';

spl_autoload_register('autoloader');

if ($argc < 2 || !in_array($argv[1], ['module', 'modele'])) {
   ModuleFactory::msg('
    + + + + AIDE Skelethon: + + + +
    '.ModuleFactory::Color['Red'].'
    Vous devez passer une action en paramètre:
    '.ModuleFactory::Color['Yellow'].'
    \'module\' '.ModuleFactory::Color['White'].' pour créer un module avec tous ses composants
    avec en arguments optionnels le nom du module
     '.ModuleFactory::Color['Yellow'].'
    \'modele\' '.ModuleFactory::Color['White'].' pour ajouter un modèle.
    avec en arguments optionnels le nom et le module auquel le modèle est rattaché
    
    ('.ModuleFactory::Color['Red'].'Attention'.ModuleFactory::Color['White'].', pour le modèle, l\'ordre des arguments est important)
    ');

   die();
}

$action = $argv[1];

if ($argc < 4) {
    $argv[3] = '';
}

$argv[2] = $argc < 3 ? '' : $argv[2];

switch($action)
{
    case 'module':
        E2DModuleMaker::create($argv[2], $argv[3]);
        //generateModule($argv[2]);
        break;
    case 'modele':
        ModuleFactory::msg('Modèle');
        break;
}

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

