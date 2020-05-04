<?php

require_once "lib/Spyc/Spyc.php";

const DS = DIRECTORY_SEPARATOR;

if ($argc < 2) {
   msg('
    + + + + AIDE Skelethon: + + + +
    '.couleur('rouge').'
    Vous devez passer une action en paramètre:
    '.couleur('jaune').'
    \'module\' '.couleur('blanc').' pour créer un module avec tous ses composants
    avec en arguments optionnels le nom du module
     '.couleur('jaune').'
    \'modele\' '.couleur('blanc').' pour ajouter un modèle.
    avec en arguments optionnels le nom et le module auquel le modèle est rattaché
    
    ('.couleur('rouge').'Attention'.couleur('blanc').', pour le modèle, l\'ordre des arguments est important)
    ');
}
$action = $argv[1];

switch($action)
{
    case 'module':
        generateModule($argv[2]);
       break;
    case 'modele':
        msg('Modèle');
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

    $model = getModelName($name);

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
            if ($verbose) {
                msg('Création du fichier: '.$path.DS.$value);
            }
        }

    }
}

function getModuleName() : string
{
    $name = '';
    while($name === '' || $name === null) {
        $name = readline(msg('Veuillez renseigner le nom du module :'));
    }

    return $name;
}

function getModelName($module)
{
    $name = readline(msg('Veuillez renseigner le nom du modèle :'.
        PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module ['.$module.']'));
    if (!$name) {
        $name = $module;
    }

    return $name;
}

function ensureDirExists(string $name, bool $recursive = false, $verbose = false) : bool
{
    if(!is_dir($name)) {
        if ($verbose) {
            msg('Création du répertoire: '.$name);
        }
        return mkdir($name, 0777, $recursive) && is_dir($name);
    }
    if ($verbose) {
        msg('*** Répertoire: '.$name. ' déja existant');
    }

    return true;
}

function addModule(string $name) : bool
{
   return ensureDirExists('modules/'.$name);
}

function msg(string $text, $type = '')
{
    switch ($type){
        case 'error':
            echo couleur('rouge').$text . PHP_EOL;
            break;
        case 'neutral':
            echo couleur('jaune').$text . PHP_EOL;
            break;
        case 'success':
            echo couleur('vert').$text . PHP_EOL;
            break;
        case 'standard':
            echo couleur('blanc').$text . PHP_EOL;
            break;
        default:
            echo $text . PHP_EOL;
            break;
    }

}

function couleur(string $color)
{
    switch ($color) {
        case 'rouge':
            return "\e[1;31m";
            break;
        case 'jaune':
            return "\e[1;33m";
            break;
        case 'vert':
            return "\e[1;32m";
            break;
        case 'blanc':
            return "\e[1;37m";
            break;
        default:
            return '';
            break;
    }
}

