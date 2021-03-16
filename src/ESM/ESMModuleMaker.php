<?php

namespace ESM;

use Core\FileManager;
use E2D\E2DConfigFileGenerator;
use E2D\E2DControllerFileGenerator;
use E2D\E2DJSFileGenerator;
use E2D\E2DModelFileGenerator;
use E2D\E2DViewFileGenerator;
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
            if (isset($menu[$this->name]['html_accueil_'.$this->model->getName()]) &&
                !array_contains_array($menu[$this->name]['html_accueil_'.$this->model->getName()], $subMenu[$this->name]['html_accueil_'.$this->model->getName()], ARRAY_ALL, true)) {
                unset($menu[$this->name]['html_accueil_'.$this->model->getName()]);
            }

            if (!isset($menu[$this->name]['html_accueil_'.$this->model->getName()])) {
                $menu = Spyc::YAMLDump(array_merge_recursive($menu, $subMenu), false, 0, true);
                $this->app->getFileManager()->createFile($this->menuPath, trim($menu, PHP_EOL), true);
            }
        } else {
            $menu = Spyc::YAMLDump($subMenu, false, 0, true);
            $this->app->getFileManager()->createFile($this->menuPath, trim($menu, PHP_EOL), true);
        }
    }

    protected function initializeFileGenerators()
    {
        $this->modelFileGenerator      = new ESMModelFileGenerator($this->app);
        $this->controllerFileGenerator = new ESMControllerFileGenerator($this->app);
        $this->jsFileGenerator         = new ESMJSFileGenerator($this->app);
        $this->configFileGenerator     = new ESMConfigFileGenerator($this->app);
        $this->viewFileGenerator       = new ESMViewFileGenerator($this->app);
    }

}