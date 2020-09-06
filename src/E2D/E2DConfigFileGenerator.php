<?php

namespace E2D;

use \Spyc;

class E2DConfigFileGenerator extends \Core\FileGenerator
{
    private string $moduleName;
    private string $controllerName;
    private E2DModelMaker $model;
    private string $pascalCaseModuleName;

    public function __construct(string $moduleName, string  $pascalCaseModuleName, E2DModelMaker $model, string $controllerName)
    {
        parent::__construct($model->fileManager);
        $this->model = $model;
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
        $this->pascalCaseModuleName = $pascalCaseModuleName;
    }

    public function generate(string $path) : string
    {
        $modelName = '';
        $enumText = '';

        if (strpos($path, 'conf.yml') === false ) {
            $texts = [];
            foreach ($this->model->actions as $action) {
                $templatePerActionPath = $this->getTrueTemplatePath(str_replace('.', '_' . $action . '.', $path));
                if (file_exists($templatePerActionPath)) {
                    $texts[] = file_get_contents($templatePerActionPath) .
                        ($this->model->usesMultiCalques && strpos($path, 'blocs') !== false ?
                            file_get_contents(str_replace($action, 'multi', $templatePerActionPath)) :
                            '');
                }
            }

            $text = implode(PHP_EOL, $texts);

        } else {
            $text = '';
            $templatePath = $this->getTrueTemplatePath($path);
            if (file_exists($templatePath)) {
                $text = file_get_contents($templatePath);

                $modelName = $this->model->getClassName() ;
                $fields = $this->model->getViewFieldsByType('enum');

                if (!empty($fields)) {
                    $enumText = '';
                    foreach ($fields as $field) {
                        $enumLines = file(str_replace('conf.', 'conf_enum.', $templatePath));
                        $enumHandle = str_replace(['MODEL', 'COLUMN'], [$modelName, $field['column']], $enumLines[0]);
                        $enumText .= $enumHandle.PHP_EOL;
                        foreach ($field['enum'] as $value) {
                            $enumKeyValuePair = str_replace(['VALEUR', 'LIBELLE'], [$value, $this->labelize($value)], $enumLines[1]);
                            $enumText .= $enumKeyValuePair.PHP_EOL;
                        }
                    }
                }
            }
        }

        $text = str_replace(['mODULE', 'mODEL', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS'],
            [$this->moduleName, $this->urlize($this->model->getName()), $this->model->getName(), $modelName, $this->pascalCaseModuleName, $this->controllerName, $this->snakize($this->controllerName) , $enumText], $text);

        return $text;
    }

    public function modify($templatePath, $filePath)
    {
        $config = Spyc::YAMLLoad($filePath);

        if (strpos($templatePath, 'conf.yml') === false ) {
            foreach ($this->model->actions as $action) {
                $templatePerActionPath = $this->getTrueTemplatePath(str_replace('.', '_' . $action . '.', $templatePath));
                if (file_exists($templatePerActionPath)) {
                    $template = file_get_contents($templatePerActionPath).($this->model->usesMultiCalques && strpos($templatePath, 'blocs') !== false ?
                            file_get_contents(str_replace($action, 'multi', $templatePerActionPath)) : '');

                    $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'cONTROLLER', 'MODEL'],
                        [$this->moduleName, $this->model->getName(), $this->snakize($this->controllerName), ''], $template));
                    $config = array_replace_recursive($config, $newConfig);
                }
            }

        } else {

            $enumText = '';
            $templatePath = $this->getTrueTemplatePath($templatePath);
            if (file_exists($templatePath)) {
                $modelName = $this->model->getClassName() ;
                $fields = $this->model->getViewFieldsByType('enum');

                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $enumLines = file(str_replace('conf.', 'conf_enum.', $templatePath));
                        $enumHandle = str_replace(['MODEL', 'COLUMN'], [$modelName, $field['column']], $enumLines[0]);
                        $enumText .= $enumHandle.PHP_EOL;
                        foreach ($field['enum'] as $value) {
                            $enumKeyValuePair = str_replace(['VALEUR', 'LIBELLE'], [$value, $this->labelize($value)], $enumLines[1]);
                            $enumText .= $enumKeyValuePair.PHP_EOL;
                        }
                    }
                }
            }

            $template = file_get_contents($templatePath);

            $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS'],
                [$this->moduleName, $this->model->getName(), $this->model->getClassName(), $this->pascalCaseModuleName, $this->controllerName, $this->snakize($this->controllerName), $enumText], $template));

            $baseJSFilePath = $this->moduleName.'/JS/'.$this->pascalCaseModuleName.'.js';
            array_unshift($newConfig['aVues'][$this->model->getName()]['admin']['formulaires']['edition_'.$this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);
            array_unshift($newConfig['aVues'][$this->model->getName()]['admin']['simples']['accueil_'.$this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);

            $config = array_replace_recursive($config, $newConfig);
        }

        $text = Spyc::YAMLDump($config, false, 0, true);

        return $text;
    }
}