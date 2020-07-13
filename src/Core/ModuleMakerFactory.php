<?php

namespace Core;


class ModuleMakerFactory
{
    private $moduleMaker;
    private $modelMaker;

    public function __construct($arguments, $moduleMaker, $modelMaker, $databaseAccess)
    {
        $this->moduleMaker = $moduleMaker;
        $this->modelMaker = $modelMaker;

        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error', false, true, true);
            die();
        }

        [$action, $moduleName, $modelName] = $arguments;
        if (!array_contains($action, ['module', 'modele'])) {
            $this->displayHelpPage();
        }

        $config = new Config('main');
        $moduleConfig = new Config($moduleName);



        switch($action)
        {
            case 'module':
                $model = $this->buildModel($moduleName, $modelName, 'generate', $config, $moduleConfig);
                /**
                 * @var \E2DModelMaker
                 */
                $model->setDatabaseAccess($databaseAccess::getDatabaseParams());
                $model->setDbTable();
                $model->generate();

                $maker = new $moduleMaker($moduleName, $model, 'generate', [
                    'config' => $config,
                    'moduleConfig' => $moduleConfig,
                    'menuPath' => 'config/menu.yml',
                ]);
                $maker->generate();
                //generateModule($argv[2]);
                break;
            case 'modele':
                $model = $this->buildModel($moduleName, $modelName, 'addModel', $config, $moduleConfig);
                $maker = new $moduleMaker($moduleName, $model, 'addModel', [
                    'config' => $config,
                    'moduleConfig' => $moduleConfig
                ]);
                $maker->generate();
                break;

//            case 'select:ajax':
//                $moduleMaker::create($module, $model, 'addSelectAjax');
//                break;
        }

    }

    public function buildModel($moduleName, $modelName, $creationMode, $config, $moduleConfig)
    {
        $params = [
            'config' => $config,
            'moduleConfig' => $moduleConfig,
            'applyChoicesForAllModules' => $config['memorize']
        ];
        $params['applyChoicesForAllModules'] = !isset($config['memorizeChoices']) || $config['memorizeChoices'];

        return new $this->modelMaker($moduleName, $modelName, $creationMode, $params);
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