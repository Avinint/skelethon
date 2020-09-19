<?php

namespace Core;


use E2D\E2DModelMaker;

class ModuleMakerFactory
{
    private $moduleMaker;
    private $modelMaker;

    public function __construct($arguments, $moduleMaker, $modelMaker, $fieldClass, $databaseAccess)
    {
        $this->databaseAccess = $databaseAccess;
        $this->moduleMaker = $moduleMaker;
        $this->modelMaker = $modelMaker;

        if (!is_dir('modules')) {
            ModuleMaker::msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error', false, true, true);
            die();
        }

        [$action, $moduleName, $modelName] = $arguments;
        if (!array_contains($action, ['module', 'modele'])) {
            $this->displayHelpPage();
        }

        $modelName = $action === 'module' ? $moduleName: $this->askName($modelName);

        $config = new Config($moduleName, $modelName);

        switch($action)
        {
            case 'module':
                $model = $this->buildModel($fieldClass, $moduleName, $modelName, 'generate', $config);
                $model->generate();
                $maker = new $moduleMaker($moduleName, $model, 'generate', [
                    'config' => $config,
                    'menuPath' => 'config/menu.yml',
                ]);
                $maker->generate();
                break;
            case 'modele':
                $model = $this->buildModel($fieldClass, $moduleName, $modelName, 'addModel', $config);
                ;
                $model->generate();
                $maker = new $moduleMaker($moduleName, $model, 'addModel', [
                    'config' => $config,
                    'menuPath' => 'config/menu.yml',
                ]);
                $maker->generate();
                break;
            /**
             *  Ajoute un bouton d'action dans la vue liste
             *  Ajoute une action et une route et une action du controlleur une callback js une vue
             */
            case 'action':
                $model = $this->buildModel($fieldClass, $moduleName, $modelName, 'addAction', $config);
                $maker = new $moduleMaker($fieldClass, $moduleName, $model, 'action', [
                    'config' => $config,
                    'menuPath' => 'config/menu.yml',
                ]);
                $maker->generateAction();
                break;

//            case 'select:ajax':
//                $moduleMaker::create($module, $model, 'AddManyToOne');
//                break;
        }

    }

    public function buildModel($fieldClass, $moduleName, $modelName, $creationMode, $config)
    {
        $params = [
            'config' => $config,
            'applyChoicesForAllModules' => (!$config->has('memorizeChoices') || $config->get('memorizeChoices')),
        ];

        if ($config->askLegacy($modelName)) {
            $modelMakerLegacy = $this->modelMaker. 'Legacy';
            $model = new $modelMakerLegacy($fieldClass, $moduleName, $modelName . 'Legacy', $creationMode, $params, $this->databaseAccess::getDatabaseParams(), null);
        } else {
            $model = new $this->modelMaker($fieldClass, $moduleName, $modelName, $creationMode, $params, $this->databaseAccess::getDatabaseParams(), null);
        }

        $model->setDatabaseAccess($this->databaseAccess::getDatabaseParams());


        return $model;
    }


    private function displayHelpPage(): void
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

    private function askName($name = '')
    {
        echo PHP_EOL;
        if ($name === '') {
            $name = readline($this->msg('Veuillez renseigner en '.$this->highlight('snake_case').' le nom du modèle'.PHP_EOL.' ('.$this->highlight('minuscules', 'warning') . ' et ' . $this->highlight('underscores', 'warning').')'.
                PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module : '. $this->frame($this->module, 'success').'')) ? : $this->module;
        }

        return $name;
    }


}