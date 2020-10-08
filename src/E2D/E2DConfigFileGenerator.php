<?php

namespace E2D;

use Core\Config;
use \Spyc;

class E2DConfigFileGenerator extends \Core\FileGenerator
{
    private string $moduleName;
    private string $controllerName;
    private E2DModelMaker $model;
    private string $pascalCaseModuleName;

    public function __construct(string $moduleName, string  $pascalCaseModuleName, E2DModelMaker $model, string $controllerName, Config $config)
    {
        $this->config = $config;
        $this->template = $model->getConfig()->get('template');
        parent::__construct($model->getFileManager());

        $this->model = $model;
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
        $this->pascalCaseModuleName = $pascalCaseModuleName;
    }

    /**
     * action de génération des fichiers
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function generate(string $path) : string
    {
        $modelName = '';
        $enumText = '';

        if (strpos($path, 'conf.yml') === false ) {
            $text = $this->generateRoutingConfigFiles($path);
        } else {
            $modelName = $this->model->getClassName();
            $templatePath = $this->getTrueTemplatePath($path);
            $text = file_get_contents($templatePath);
            $enumText = $this->addEnumsToConfig($templatePath, $modelName);
        }

        $text = str_replace(['mODULE', 'mODEL', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS'],
            [$this->moduleName, $this->urlize($this->model->getName()), $this->model->getName(), $modelName, $this->pascalCaseModuleName, $this->controllerName, $this->snakize($this->controllerName) , $enumText], $text);

        return $text;
    }

    /**
     * Génère des fichiers de config de routing (action, routing ou bloc)
     * @param string $path
     * @return string
     * @throws \Exception
     */
    private function generateRoutingConfigFiles(string $path): string
    {
        $texts = [];
        foreach ($this->model->actions as $action)
        {
            if ($action === 'accueil' && strpos($path, 'routing') === false) continue;
            if (!array_contains($action, ['consultation', 'edition']) && strpos($path, 'blocs')) continue;
            $templatePerActionPath = $this->getTrueTemplatePath($path, '_' . $action . '.');
            if (file_exists($templatePerActionPath)) {
                $texts[] = $this->getConfigTemplateForAction($templatePerActionPath, $path, $action);
            }
        }

        $text = implode(PHP_EOL, $texts);
        return $text;
    }

    /**
     * Modifie fichiers existants pour prendre en compte l'ajout de nouveaux fichiers
     * @param $templatePath
     * @param $filePath
     * @return string
     * @throws \Exception
     */
    public function modify($templatePath, $filePath)
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
     * @param $templatePath
     * @param array $config
     * @return array
     * @throws \Exception
     */
    private function modifyRoutingConfigFiles($templatePath, array $config): array
    {
        foreach ($this->model->actions as $action) {
            $templatePerActionPath = $this->getTrueTemplatePath(str_replace('.', '_' . $action . '.', $templatePath));
            if (file_exists($templatePerActionPath)) {
                $template = file_get_contents($templatePerActionPath) . $this->makeMultiModalBlock($templatePath, $action, $templatePerActionPath);

                $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'cONTROLLER', 'MODEL'],
                    [$this->moduleName, $this->model->getName(), $this->snakize($this->controllerName), ''], $template));
                $config = array_replace_recursive($config, $newConfig);
            }
        }
        return $config;
    }

    /**
     * Récupère le template pour générer un fichier action, routing ou bloc par action
     * @param string $templatePerActionPath
     * @param string $path
     * @param string $action
     * @return string
     */
    private function getConfigTemplateForAction(string $templatePerActionPath, string $path, string $action): string
    {
        return file_get_contents($templatePerActionPath) .
            $this->makeMultiModalBlock($path, $action, $templatePerActionPath);
    }

    /**
     * Ajoute les lignes permettant les calques multiples, dans les blocs
     * @param string $path
     * @param string $action
     * @param string $templatePerActionPath
     * @return false|string
     */
    private function makeMultiModalBlock(string $path, string $action, string $templatePerActionPath)
    {
        return ($this->model->usesMultiCalques && strpos($path, 'blocs') !== false ?
            file_get_contents(str_replace($action, 'multi', $templatePerActionPath)) : '');
    }

    /**
     * Ajout des Enums dans le fichier conf
     * @param string $templatePath
     * @param string $modelName
     * @return string
     */
    private function addEnumsToConfig(string $templatePath, string $modelName): string
    {
        $enumText = '';
        $fields = $this->model->getFields('', 'enum');

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $enumLines = file(str_replace('conf.', 'conf_enum.', $templatePath));
                $enumHandle = str_replace(['MODEL', 'COLUMN'], [$modelName, $field->getColumn()], $enumLines[0]);
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
     *  Modifie le fichier conf
     * @param $templatePath
     * @param array $config
     * @return array
     * @throws \Exception
     */
    private function modifyConfFile($templatePath, array $config): array
    {
        $enumText = '';
        $templatePath = $this->getTrueTemplatePath($templatePath);
        if (file_exists($templatePath)) {
            $modelName = $this->model->getClassName();
            $enumText = $this->addEnumsToConfig($templatePath, $modelName);
        }

        $template = file_get_contents($templatePath);
        $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS'],
            [$this->moduleName, $this->model->getName(), $this->model->getClassName(), $this->pascalCaseModuleName, $this->controllerName, $this->snakize($this->controllerName), $enumText], $template));

        $this->addMainModuleJSFileLinkToConfig($newConfig['aVues']);

        $config = array_replace_recursive($config, $newConfig);
        return $config;
    }

    /**
     * Ajoute le fichier nomdumodule.js dans les fichiers js liés aux controllers
     * @param $aVues
     * @return void
     */
    private function addMainModuleJSFileLinkToConfig(array $aVues): void
    {
        $baseJSFilePath = $this->moduleName . '/JS/' . $this->pascalCaseModuleName . '.js';
        array_unshift($aVues[$this->model->getName()]['admin']['formulaires']['edition_' . $this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);
        array_unshift($aVues[$this->model->getName()]['admin']['simples']['accueil_' . $this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);
    }

}