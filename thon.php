<?php

include 'Module.php';

require_once "lib/Spyc/Spyc.php";

const DS = DIRECTORY_SEPARATOR;

if ($argc < 2 || !in_array($argv[1], ['module', 'modele'])) {
   Module::msg('
    + + + + AIDE Skelethon: + + + +
    '.Module::Color['Red'].'
    Vous devez passer une action en paramètre:
    '.Module::Color['Yellow'].'
    \'module\' '.Module::Color['White'].' pour créer un module avec tous ses composants
    avec en arguments optionnels le nom du module
     '.Module::Color['Yellow'].'
    \'modele\' '.Module::Color['White'].' pour ajouter un modèle.
    avec en arguments optionnels le nom et le module auquel le modèle est rattaché
    
    ('.Module::Color['Red'].'Attention'.Module::Color['White'].', pour le modèle, l\'ordre des arguments est important)
    ');
}
$action = $argv[1];

if ($argc < 4) {
    $argv[3] = '';
}

switch($action)
{
    case 'module':
        Module::getInstance($argv[2], $argv[3]);
        //generateModule($argv[2]);
        break;
    case 'modele':
        Module::msg('Modèle');
        break;
}

function generateModule($name = null)
{
    $verbose = true;

    if (!is_dir('modules')) {
        msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error');
        return false;
    }

    if (!isset($name)) {
        $name = getModuleName();
    }

    $model = getModelName();

    if (!addModule($name)) {
        msg('Création de répertoire impossible. Processus interrompu', 'error');
        return false;
    }

    $moduleStructure = Spyc::YAMLLoad(__DIR__.DS.'module.yml');
    addSubDirectories('modules'.DS.$name, $moduleStructure, $model, $verbose);

}

function addSubDirectories($path, $structure, $verbose = false)
{
    foreach ($structure as $key => $value) {
        if (is_array($value)) {


            if (ensureDirExists($path.DS.$key, true, $verbose)) {
                addSubDirectories($path.DS.$key, $value, $verbose);
            }
        } else {
            // crée fichier
            ensureFileExists($path.DS.$value, $verbose);
        }

    }
}

//function getModuleName() : string
//{
//    $name = '';
//    while($name === '' || $name === null) {
//        $name = readline(msg('Veuillez renseigner le nom du module :'));
//    }
//
//    return $name;
//}
//
//function getModelName()
//{
//    $model = readline(msg('Veuillez renseigner le nom du modèle :'.
//        PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module ['.$this->name.']'));
//    if (!$model) {
//        $model = $this->name;
//    }
//
//    return $model;
//}
//
//function ensureDirExists(string $name, bool $recursive = false, $verbose = false) : bool
//{
//    if(!is_dir($name)) {
//        if ($verbose) {
//            msg('Création du répertoire: '.$name, 'success');
//        }
//        return mkdir($name, 0777, $recursive) && is_dir($name);
//    }
//    if ($verbose) {
//        msg('*** Répertoire: '.$name. ' déja existant', 'neutral');
//    }
//
//    return true;
//}
//
//function addModule(string $name) : bool
//{
//   return ensureDirExists('modules/'.$name);
//}
//
//
//function ensureFileExists(string $path, string $name, $verbose) : bool
//{
//    echo $path;
//
//    if (glob($name)) {
//        msg("Le fichier $name existe déja", 'error');
//        return true;
//    } else {
//        if ($verbose) {
//            msg('Création du fichier: '.$name, 'success');
//        }
//        $nameHierarchy = explode(DS, $name);
//        array_shift($nameHierarchy );
//        array_shift($nameHierarchy );
//        $patternPath = __DIR__.DS.'module'.DS.implode(DS, $nameHierarchy);
//        if (strpos($patternPath, 'actions.yml')) {
//            $text = file_get_contents($patternPath);
//            msg($text);
//        }
//
//        msg($patternPath);
//       // $genericFile = str_replace('modules/')
//        //__DIR__.DS.
//        //$file = fopen($name, 'w');
//
//        //fwrite($file, $txt);
//        //fclose($file);
//        return true;
//    }
//}
//
//function msg(string $text, $type = '')
//{
//    switch ($type){
//        case 'error':
//            echo color('red').$text . PHP_EOL;
//            break;
//        case 'neutral':
//            echo color('yellow').$text . PHP_EOL;
//            break;
//        case 'success':
//            echo color('green').$text . PHP_EOL;
//            break;
//        case 'standard':
//            echo color('white').$text . PHP_EOL;
//            break;
//        default:
//            echo color('white').$text . PHP_EOL;
//            break;
//    }
//
//}
//
//function color(string $color) : string
//{
//    switch ($color) {
//        case 'red':
//            return "\e[1;31m";
//            break;
//        case 'yellow':
//            return "\e[1;33m";
//            break;
//        case 'green':
//            return "\e[0;32m";
//            break;
//        case 'white':
//            return "\e[1;37m";
//            break;
//        default:
//            return '';
//            break;
//    }
//}

