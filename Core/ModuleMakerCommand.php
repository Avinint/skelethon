<?php

namespace Core;

class ModuleMakerCommand
{
    public function __construct($arguments)
    {
        Config::create(\dirname(__DIR__) . DS . 'config.yml');
        $moduleMaker = Config::get()['moduleMaker'];
        [$action, $module, $model] = $arguments;
        if (!array_contains($action, ['module', 'modele'])) {
            $this->displayErrorMessage();
        }

        switch($action)
        {
            case 'module':
                $moduleMaker::create($module , $model);
                //generateModule($argv[2]);
                break;
            case 'modele':
                $moduleMaker::create($module, $model, 'addModel');
                break;
        }
    }

    private function displayErrorMessage(): void
    {
        ModuleMaker::msg('
    + + + + AIDE ModuleMaker : + + + +
    ' . ModuleMaker::Color['Red'] . '
    Vous devez passer une action en paramètre:
    ' . ModuleMaker::Color['Yellow'] . '
    \'module\' ' . ModuleMaker::Color['White'] . ' pour créer un module avec tous ses composants
    avec en arguments optionnels le nom du module
     ' . ModuleMaker::Color['Yellow'] . '
    \'modele\' ' . ModuleMaker::Color['White'] . ' pour ajouter un modèle.
    avec en arguments optionnels le nom et le module auquel le modèle est rattaché
    
    (' . ModuleMaker::Color['Red'] . 'Attention' . ModuleMaker::Color['White'] . ', pour le modèle, l\'ordre des arguments est important)
    ');

        die();
    }


}