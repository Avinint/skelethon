<?php

namespace Core;

use E2D\E2DModelMaker;

abstract class ModuleMakerFactory extends CommandLineToolShelf
{
    protected $app;
    protected $moduleMaker;
    protected $modelMaker;
    protected $fieldClass;
    protected $databaseAccess;
    protected $templateNodeClass;

    public function __construct(ProjectType $type, $arguments, $appDir)
    {
        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error', false, true, true);
            die();
        }

        [$action, $moduleName, $modelName] = $arguments;
        if (!array_contains($action, ['module', 'modele'])) {
            $this->displayHelpPage();
        }
        $modelName = $this->askName($modelName, $moduleName);

        $config = new Config($moduleName, $modelName, $type);
        $config->setCurrentModel($modelName);
        $config->initialize();

        if ($config->get('showLogo')?? false) {
            echo $this->highlight($this->display_logo(), 'white');
        }

        $app = new App();
        $app->setConfig($config);
        $this->initialisePaths($app, $appDir);

        $app->setDatabaseAccess($this->databaseAccess::getDatabaseParams());
        //$this->initializeFileGenerators($app);
        $this->generate($action, $moduleName, $modelName, $app);


    }

    private function askName($name = '', $moduleName)
    {
        echo PHP_EOL;
        if ($name === '') {
            $name = readline($this->msg('Veuillez renseigner en '.$this->highlight('snake_case').' le nom du modèle'.PHP_EOL.' (' . $this->highlight('minuscules') . ' et ' . $this->highlight('underscores').')'.
                PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module : '. $this->frame($moduleName, 'success').'')) ? : $moduleName;
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
    protected function generate(string $action, $moduleName, string $modelName, App $app) : void
    {
        switch ($action) {
            case 'module':
                $model = $this->createModel($moduleName, $modelName, 'generate', $app);

                $model->recupereDonnees();
                $maker = new $this->moduleMaker($moduleName, $app, 'generate', [
                    'menuPath' => 'config/menu.yml',
                ]);
                $app->setModuleMaker($maker);
                $maker->generate();
                break;
            case 'modele':
                $model = $this->createModel($moduleName, $modelName, 'addModel', $app);
                $model->recupereDonnees();
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
//                $$this->create($module, $model, 'AddManyToOne');
//                break;
        }
    }

    /**
     * @param $moduleName
     * @param $modelName
     * @param $creationMode
     * @param App $app
     * @return ModelMaker
     */
    public function createModel($moduleName, $modelName, $creationMode, App $app) : ModelMaker
    {
        $model = new $this->modelMaker($this->fieldClass, $moduleName, $modelName, $creationMode, $app);

        return $model;
    }

    /**
     * Message affiché si l'utilisateur fait n'importe quoi en appelant l'application
     */
    private function displayHelpPage(): void
    {
        $this->msg('
    + + + + AIDE ModuleMaker : + + + +
    ' . static::Color['Red'] . '
    Vous devez passer une action en paramètre:
    ' . static::Color['Yellow'] . '
    \'module\' ' . self::Color['White'] . ' pour créer un module avec tous ses composants
    avec en arguments optionnels le nom du module
     ' . static::Color['Yellow'] . '
    \'modele\' ' . static::Color['White'] . ' pour ajouter un modèle.
    avec en arguments optionnels le nom et le module auquel le modèle est rattaché
    
    (' . static::Color['Red'] . 'Attention' . static::Color['White'] . ', pour le modèle, l\'ordre des arguments est important)
    ');

        die();
    }

    /**
     * @param App $app
     * @param $appDir
     * @return void
     */
    private function initialisePaths(App $app, $appDir): void
    {
        $app->setFileManager($this->templateNodeClass);
        $app->getFileManager()->setTemplate($app->get('template') ?? $app->getConfig()->askTemplate());
        $app->setProjectPath();

        $templatePath = new TemplatePath($appDir . '/templates', $app->getFileManager()->getTemplates());
        $app->getFileManager()->setTemplatePath($templatePath);
    }
}