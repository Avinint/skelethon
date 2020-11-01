<?php

namespace E2D;

use Core\{Action, App, FileGenerator, FilePath, ModuleMaker, PathNode};
use \Spyc;

class E2DConfigFileGenerator extends FileGenerator
{
    private string $moduleName;
    private string $controllerName;
    private E2DModelMaker $model;
    private string $pascalCaseModuleName;
    private ModuleMaker $module;

    public function __construct(App $app)
    {
        $this->app                  = $app;
        $this->template             = $app->get('template');
        $this->model                = $app->getModelMaker();
        $this->module               = $app->getModuleMaker();
        $this->moduleName           = $app->getModuleMaker()->getName();
        $this->controllerName       = $app->getModuleMaker()->getControllerName();
        $this->pascalCaseModuleName = $app->getModuleMaker()->getNamespaceName();
    }

    /**
     * action de génération des fichiers
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function generate(FilePath $path) : string
    {
        $modelName = '';
        $exportJSText = '';
        $enumText = '';
        $exportText = '';

        if ($path->getName() !== 'conf') {
            $text = $this->generateRoutingConfigFiles($path);
        } else {
            $modelName = $this->model->getClassName();
            $templatePath = $this->getTrueTemplatePath($path);
            $text = file_get_contents($templatePath);
            $enumText = $this->addEnumsToConfig($templatePath);
            if ($this->model->hasAction('export')) {
                $exportText = $this->generateChampsExportForConfig($templatePath);
                $exportJSText = PHP_EOL.file_get_contents($templatePath->add('exportjs'));
            }
        }

        $text = str_replace(['mODULE', 'mODEL', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS:', 'EXPORTJS', 'EXPORT'],
            [$this->moduleName, $this->urlize($this->model->getName()), $this->model->getName(), $modelName, $this->pascalCaseModuleName,
                $this->controllerName, $this->snakize($this->controllerName) , $enumText, $exportJSText, $exportText], $text);

        return $text;
    }

    /**
     * Génère des fichiers de config de routing (action, routing ou bloc)
     * @param string $path
     * @return string
     * @throws \Exception
     */
    private function generateRoutingConfigFiles(FilePath $path): string
    {
        $texts = [];

        foreach ($this->model->getActions() as $action)
        {
            /**
             * @var Action $action
             */
            $texts[] = $action->generateRoutingFile($path);
        }

        $text = implode(PHP_EOL, array_filter($texts));
        return $text;
    }

    /**
     * Modifie fichiers existants pour prendre en compte l'ajout de nouveaux fichiers
     * @param FilePath $templatePath
     * @param FilePath$filePath
     * @return string
     * @throws \Exception
     */
    public function modify(FilePath $templatePath, FilePath $filePath)
    {
        $config = Spyc::YAMLLoad($filePath);

        if (strpos($templatePath, 'conf.yml') === false ) {
            $config = $this->modifyRoutingConfigFiles($templatePath, $config);
        } else {
            $config = $this->modifyConfFile($templatePath, $config);
        }

        return Spyc::YAMLDump($config, false, 0, true);
    }

    /**
     *  Modifie les fichiers de routing (actions, routing, bloc)
     * @param FilePath $templatePath
     * @param array $config
     * @return array
     * @throws \Exception
     */
    private function modifyRoutingConfigFiles(FilePath $templatePath, array $config): array
    {
        foreach ($this->model->getActions() as $action => $actionObject) {

            $templatePerActionPath = $this->getTrueTemplatePath($templatePath->add($action));
            if (file_exists($templatePerActionPath)) {
                $template = file_get_contents($templatePerActionPath) . $actionObject->makeMultiModalBlock($templatePath, $templatePerActionPath);

                $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'cONTROLLER', 'MODEL'],
                    [$this->moduleName, $this->model->getName(), $this->snakize($this->controllerName), ''], $template));
                $config = array_replace_recursive($config, $newConfig);
            }
        }
        return $config;
    }

//    /**
//     * Récupère le template pour générer un fichier action, routing ou bloc par action
//     * @param string $templatePerActionPath
//     * @param string $path
//     * @param string $action
//     * @return string
//     */
//    private function getConfigTemplateForAction(FilePath $templatePerActionPath, FilePath $path, string $action): string
//    {
//        return file_get_contents($templatePerActionPath) .
//            $this->makeMultiModalBlock($path, $action, $templatePerActionPath);
//    }



    /**
     * Ajout des Enums dans le fichier conf
     * @param FilePath $templatePath
     * @return string
     */
    private function addEnumsToConfig(FilePath $templatePath): string
    {
        $enumText = '';
        $fields = $this->model->getFields('', 'enum');

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $enumLines = file($templatePath->add('enum'));
                $enumHandle = str_replace(['MODEL', 'COLUMN'], [$this->model->getClassName(), $field->getColumn()], $enumLines[0]);
                $enumText .= $enumHandle . PHP_EOL;
                foreach ($field->getEnum() as $value) {
                    $enumKeyValuePair = str_replace(['VALEUR', 'LIBELLE'], [$value, $this->labelize($value)], $enumLines[1]);
                    $enumText .= $enumKeyValuePair . PHP_EOL;
                }
            }
        }
        return $enumText;
    }

    /**
     * Ajout des champs export en conf, en mode génération de fichier
     * @param PathNode $templatePath
     * @return string
     */
    private function generateChampsExportForConfig(PathNode $templatePath)
    {
        $champsTexte = '';
        $fields = $this->model->getFieldsForExport();

        if (!empty($fields)) {
            $exportFieldLines = file($templatePath->add('export'));
            $champsTexte .= PHP_EOL.str_replace('cONTROLLER', $this->module->getControllerName('snake'), $exportFieldLines [0]);
            $champsTexte .= str_replace('MODEL', $this->model->getClassName(), $exportFieldLines [1]);
            foreach ($fields as $field) {
                $champsTexte .= str_replace(['VALEUR', 'LIBELLE'], [$field->getColumn(), $this->labelize($field)], $exportFieldLines[2]).PHP_EOL;
            }
        }

        return $champsTexte;
    }

    /**
     *  Modifie le fichier conf
     * @param $templatePath
     * @param array $config
     * @return array
     * @throws \Exception
     */
    private function modifyConfFile($templatePath, array $config): array
    {
        $enumText = '';
        $exportText = '';
        $exportJSText = '';
        $templatePath = $this->getTrueTemplatePath($templatePath);
        if (file_exists($templatePath)) {
            $enumText = $this->addEnumsToConfig($templatePath);
            $exportText = $this->generateChampsExportForConfig($templatePath);
            if ($this->model->hasAction('export')) {
                $exportJSText = PHP_EOL.file_get_contents($templatePath->add('exportjs'));
            }
        }

        $template = file_get_contents($templatePath);
        $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS:', 'EXPORTJS', 'EXPORT'],
            [$this->moduleName, $this->model->getName(), $this->model->getClassName(), $this->pascalCaseModuleName, $this->controllerName, $this->snakize($this->controllerName), $enumText, $exportJSText, $exportText], $template));

        //var_dump($newConfig['aVues']);
        $this->addMainModuleJSFileLinkToConfig($newConfig['aVues'], $exportJSText );

        $config = array_replace_recursive($config, $newConfig);

        return $config;
    }

    /**
     * Ajoute le fichier nomdumodule.js dans les fichiers js liés aux controllers
     * @param $aVues
     * @return void
     */
    private function addMainModuleJSFileLinkToConfig(array $aVues, $exportText = ''): void
    {

        $baseJSFilePath = $this->moduleName . '/JS/' . $this->pascalCaseModuleName . '.js';
        array_unshift($aVues[$this->model->getName()]['admin']['formulaires']['edition_' . $this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);
        array_unshift($aVues[$this->model->getName()]['admin']['simples']['accueil_' . $this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);
    }

    /**
     * @param FilePath$path
     * @param array $texts
     * @return array
     */
    private function generateRoutingFile(FilePath $path) : array
    {
        $templatePerActionPath = $this->getTrueTemplatePath($path->add($this->name));
        if (file_exists($templatePerActionPath)) {
            return $this->getConfigTemplateForAction($templatePerActionPath, $path, $this->name);
        }
    }

}