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

        if (($this->app->get('restful') ?? false) && $path->getName() === 'services') {
            $text = $this->generateServiceFile();
        } elseif ($path->getName() !== 'conf') {
            $text = $this->generateRoutingConfigFiles($this->getTrueTemplatePath($path));

        } else {
            $modelName = $this->model->getClassName();
            $templatePath = $this->getTrueTemplatePath($path);

            $text = file_get_contents($this->getTrueTemplatePath($path));
            $enumText = $this->addEnumsToConfig($templatePath);
            if ($this->model->hasAction('export')) {
                $exportText = $this->generateChampsExportForConfig($templatePath);
                if ($this->app->get('avecRessourceExport')) {
                    $exportJSText = PHP_EOL . file_get_contents($templatePath->add('exportjs'));
                }
            }
        }

        $text = str_replace(['mODULE', 'mURL', 'mODEL', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS:', 'EXPORTJS', 'EXPORT'],
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

        if (strpos($templatePath, 'services.yml')) {
            $config = $this->modifyServiceFile();
        } elseif (strpos($templatePath, 'conf.yml') === false ) {
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

                $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'mODEL', 'cONTROLLER', 'MODEL'],
                    [$this->moduleName, $this->model->getName(), $this->snakize($this->controllerName), ''], $template));
                $config = array_replace_recursive($config, $newConfig);
            }
        }

        return $config;
    }

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
                $enumText .=  PHP_EOL . $enumHandle;
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
        $exportFieldLines = file($templatePath->add('export'));
        if (!empty($fields)) {
            if ($this->app->get('avecRessourceExport')) {
                $champsTexte .= PHP_EOL . str_replace('cONTROLLER', $this->module->getControllerName('snake'), $exportFieldLines [0]);
                $champsTexte .= str_replace('MODEL', $this->model->getClassName(), $exportFieldLines [1]);

            } else {
                $champsTexte .= PHP_EOL . str_replace('cONTROLLER', $this->model->getClassName(), $exportFieldLines [0]);
            }

            foreach ($fields as $field) {
                $champsTexte .= str_replace(['VALEUR', 'LIBELLE'], [$field->getFormattedName(), $this->labelize($field)], $exportFieldLines[2]).PHP_EOL;
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
        
        return [];
    }

    /**
     * Génère le fichier services.yml à partir des données du modèle à ajouter
     * @return string
     */
    private function generateServiceFile()
    {
        $config = $this->getServices();

        return Spyc::YAMLDump($config, false, 0, true);
    }

    /**
     * Modifie le fichier services.yml à partir des données du modèle à ajouter et des données existantes
     * @param $path
     * @return void
     */
    private function modifyServiceFile($config)
    {
        $newConfig = $this->getServices();
        return  array_merge_recursive($config, $newConfig);
    }

    /**
     * collecte les éléments à ajouter aux services
     * @return array|array[]
     */
    private function getServices(): array
    {
        $config = $this->getServicesBase();
        $config = $this->getServiceRecherche($config);
        $config = $this->addServiceConsultation($config);
        $config = $this->addServicesEdition($config);
        $config = $this->addServiceSuppression($config);

        return $config;
    }

    /**
     * Ajoute la structure de base du fichier et le service recherche, obligatoire
     * @return array[]
     */
    private function getServicesBase(): array
    {
        $config = [
            'aServices' => [
                'Recherche' . $this->model->getClassName() => [
                    'aMethodes' => []
                ],
                $this->model->getClassName()   => ['aMethodes' => [], 'aVariables' => $this->addVariables()]
        ]];

        return $config;
    }

    /**
     * Ajoute le service dynamisation recherche si nécessaire
     * @param array $config
     * @return array
     */
    private function getServiceRecherche(array $config): array
    {
        if ($this->model->hasAction('recherche')) {
            $config['aServices']['Recherche' . $this->model->getClassName()]['aMethodes']['Read'] = $this->createService('dynamisation_recherche');
        }

        $config['aServices']['Recherche' . $this->model->getClassName()]['aMethodes']['Create'] = $this->createService('recherche');

        return $config;
    }

    /**
     * @param array $config
     * @return array
     */
    private function addServicesEdition(array $config): array
    {
        if ($this->model->hasAction('edition')) {
            $config['aServices']['Formulaire' . $this->model->getClassName()] = [
                'aMethodes' => ['Read' => $this->createService('dynamisation_edition')],
                'aVariables' => $this->addVariables()
            ];

            $config['aServices'][$this->model->getClassName()]['aMethodes']['Create'] = $this->createService('creation');
            $config['aServices'][$this->model->getClassName()]['aMethodes']['Update'] = $this->createService('modification');

        }
        return $config;
    }

    /**
     * @param array $config
     * @return array
     */
    private function addServiceSuppression(array $config): array
    {
        if ($this->model->hasAction('suppression')) {
            $config['aServices'][$this->model->getClassName()]['aMethodes']['Delete'] = $this->createService('suppression');
        }
        return $config;
    }

    /**
     * @param array $config
     * @return array
     */
    private function addServiceConsultation(array $config): array
    {
        if ($this->model->hasAction('consultation')) {
            $config['aServices'][$this->model->getClassName()]['aMethodes']['Read'] = $this->createService('dynamisation_consultation');
        }
        return $config;
    }

    protected function createService($sAction)
    {
        return [
            'zone'   => 'admin',
            'module' => $this->moduleName,
            'controller' => $this->controllerName,
            'action' => $sAction,
        ];
    }

    protected function addVariables()
    {
        return [$this->model->getIdField() => ['szType' => 'int']];
    }
}