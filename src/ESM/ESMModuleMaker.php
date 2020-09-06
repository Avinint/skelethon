<?php

namespace ESM;

use \Spyc;
use E2D\E2DModuleMaker;

class ESMModuleMaker extends E2DModuleMaker
{

    protected static function askTemplate()
    {
        return  'esm';
    }

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
            $this->createFile($$this->menuPath, $menu, true);
        }
    }

    /**
     * @param $field
     * @param $fieldTemplatePath
     * @param string $fieldsText
     * @return string
     */
    protected function generateControllerIntegerField($field, $fieldTemplatePath): string
    {
        return str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[5]);
    }
}