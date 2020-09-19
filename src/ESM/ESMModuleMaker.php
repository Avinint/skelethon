<?php

namespace ESM;

use \Spyc;
use E2D\E2DModuleMaker;

class ESMModuleMaker extends E2DModuleMaker
{
    /**
     * Vérifie qu'un sous menu correspondant au module existe dans menu.yml et soit conforme
     * Sinon on ajoute le sous-menu idoine
     */
    protected function addMenuItem(): void
    {
        $menu = Spyc::YAMLLoad($this->menuPath);
        $subMenu = $this->getSubMenu();

        if (!empty($menu)) {
            if (isset($menu[$this->name]['html_accueil_'.$this->model->getName()]) && !array_contains_array($menu[$this->name]['html_accueil_'.$this->model->getName()], $subMenu[$this->name]['html_accueil_'.$this->model->getName()], ARRAY_ALL, true)) {
                unset($menu[$this->name]['html_accueil_'.$this->model->getName()]);
            }

            if (!isset($menu[$this->name]['html_accueil_'.$this->model->getName()])) {
                $menu = Spyc::YAMLDump(array_merge_recursive($menu, $subMenu), false, 0, true);
                $this->fileManager->createFile($this->menuPath, $menu, true);
            }
        } else {
            $menu = Spyc::YAMLDump($subMenu, false, 0, true);
            $this->createFile($this->menuPath, $menu, true);
        }
    }

    protected function initializeFileGenerators($params)
    {
        $modelFileGenerator       = $params['modelFileGenerator'] ?? ESMModelFileGenerator::class;
        $controllerFileGenerator  = $params['controllerFileGenerator'] ?? ESMControllerFileGenerator::class;
        $viewFileGenerator        = $params['viewFileGenerator'] ?? ESMViewFileGenerator::class;
        $jSFileGenerator          = $params['jSFileGenerator'] ?? ESMJSFileGenerator::class;
        $configFileGenerator      = $params['ConfigFileGenerator'] ?? ESMConfigFileGenerator::class;

        $this->modelFileGenerator      = new $modelFileGenerator($this->name, $this->model);
        $this->controllerFileGenerator = new $controllerFileGenerator($this->name, $this->namespaceName,$this->model, $this->getControllerName());
        $this->jsFileGenerator         = new $jSFileGenerator($this->name, $this->namespaceName, $this->model, $this->getControllerName());
        $this->configFileGenerator     = new $configFileGenerator($this->name, $this->namespaceName,$this->model, $this->getControllerName());
        $this->viewFileGenerator       = new $viewFileGenerator($this->name, $this->model, $this->getControllerName());
    }
}