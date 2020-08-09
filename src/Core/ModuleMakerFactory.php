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

        $config = new Config($moduleName, $modelName);
//        $moduleConfig = new Config($moduleName);



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
                $model->setDatabaseAccess($databaseAccess::getDatabaseParams());
                $maker = new $moduleMaker($fieldClass, $moduleName, $model, 'action', [
                    'config' => $config,
                    'menuPath' => 'config/menu.yml',
                ]);
                $maker->generateAction();
                break;

//            case 'select:ajax':
//                $moduleMaker::create($module, $model, 'AddOneToMany');
//                break;
        }

    }

    public function buildModel($moduleName, $modelName,$fieldClass,  $creationMode, $config)
    {
        $params = [
            'config' => $config,
            'applyChoicesForAllModules' => $config['memorize'],
        ];
        $params['applyChoicesForAllModules'] = !isset($config['memorizeChoices']) || $config['memorizeChoices'];

        /**
         * @var E2DModelMaker $model
         */
        $model =  new $this->modelMaker($moduleName, $modelName, $fieldClass, $creationMode, $params);

        $model->setDatabaseAccess($this->databaseAccess::getDatabaseParams());
        $model->setDbTable();


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


}