<?php

namespace Core;

use E2D\E2DModelMaker;

abstract class ModuleMakerFactory
{
    protected $app;
    protected $moduleMaker;
    protected $modelMaker;
    protected $fieldClass;
    protected $databaseAccess;

    public function __construct(ProjectType $type, $arguments, $appDir)
    {
//        $this->modelMaker = $type.'ModelMaker';
//        $moduleMaker = $type.'ModuleMaker';
//        $this->fieldClass = $type.'Field';

        if (!is_dir('modules')) {
            ModuleMaker::msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error', false, true, true);
            die();
        }

        [$action, $moduleName, $modelName] = $arguments;
        if (!array_contains($action, ['module', 'modele'])) {
            $this->displayHelpPage();
        }
        $modelName = $action === 'module' ? $moduleName: $this->askName($modelName);

        $config = new Config($moduleName, $modelName, $type);
        $config->setCurrentModel($modelName);
        $config->initialize();

        $app = new App();
        $app->setConfig($config);

        $app->setFileManager($config->get('template', $modelName) ?? $config->askTemplate());
        $templatePath = new Path($appDir.'/templates', 'templatePath');
        $app->getFileManager()->setTemplatePath($templatePath);

        $app->setDatabaseAccess($this->databaseAccess::getDatabaseParams());
        $this->initializeFileGenerators($app);
        $this->generate($action, $moduleName, $modelName, $app, $config);
    }


    private function askName($name = '')
    {
        echo PHP_EOL;
        if ($name === '') {
            $name = readline(ModuleMaker::msg('Veuillez renseigner en '.ModuleMaker::highlight('snake_case').' le nom du modèle'.PHP_EOL.' (' . ModuleMaker::highlight('minuscules') . ' et ' . ModuleMaker::highlight('underscores').')'.
                PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module : '. ModuleMaker::frame($this->module, 'success').'')) ? : $this->module;
        }

        return $name;
    }

    /**
     * @param string $action
     * @param $moduleName
     * @param string $modelName
     * @param App $app
     * @param Config $config
     */
    protected function generate(string $action, $moduleName, string $modelName, App $app, Config $config) : void
    {
        switch ($action) {
            case 'module':
                $model = $this->createModel($moduleName, $modelName, 'generate', $app);
                $model->generate();
                $maker = new $this->moduleMaker($moduleName, $app, 'generate', [
                    'menuPath' => 'config/menu.yml',
                ]);
                $app->setModuleMaker($maker);
                $maker->generate();
                break;
            case 'modele':
                $model = $this->createModel($moduleName, $modelName, 'addModel', $app);
                $model->generate();
                $maker = new $this->moduleMaker($moduleName, $app, 'addModel', [

                    'menuPath' => 'config/menu.yml',
                ]);
                $app->setModuleMaker($maker);
                $maker->generate();
                break;
            /**
             *  Ajoute un bouton d'action dans la vue liste
             *  Ajoute une action et une route et une action du controlleur une callback js une vue
             */
            case 'action':
                $model = $this->createModel($moduleName, $modelName, 'addAction', $app);
                $maker = new $this->moduleMaker($moduleName, $model, 'action', [
                    'menuPath' => 'config/menu.yml',
                ]);
                $app->setModuleMaker($maker);
                $maker->generateAction();
                break;

//            case 'select:ajax':
//                $moduleMaker::create($module, $model, 'AddManyToOne');
//                break;
        }
    }

    /**
     * @param $moduleName
     * @param $modelName
     * @param $creationMode
     * @param App $app
     * @return mixed
     */
    public function createModel($moduleName, $modelName, $creationMode, App $app)
    {
        $params = [
            'app' => $app,
            'applyChoicesForAllModules' => (!$app->getConfig()->has('memorizeChoices') || $app->getConfig()->get('memorizeChoices')),
        ];

        if ($app->getConfig()->askLegacy($modelName)) {
            $modelMakerLegacy = $this->modelMaker. 'Legacy';
            $model = new $modelMakerLegacy($this->fieldClass, $moduleName, $modelName . 'Legacy', $creationMode, $app);
        } else {
            $model = new $this->modelMaker($this->fieldClass, $moduleName, $modelName, $creationMode, $app);
        }

        //$model->setDatabaseAccess($this->databaseAccess::getDatabaseParams());


        return $model;
    }

    /**
     * Message affiché si l'utilisateur fait n'importe quoi en appelant l'application
     */
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