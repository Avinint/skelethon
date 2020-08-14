<?php

namespace ESM;

use \Spyc;
use E2D\E2DModuleMaker;

class ESMModuleMaker extends E2DModuleMaker
{

    protected function askTemplate()
    {
        return  'esm';
    }


    /**
     * Vérifie qu'un sous menu correspondant au module existe dans menu.yml et soit conforme
     * Sinon on ajoute le sous-menu idoine
     */
    protected function addMenu(): void
    {

        if (isset($this->config['updateMenu']) && !$this->config['updateMenu']) {
            return;
        }

        if (!file_exists($this->menuPath)) {
            return;
        }

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
     * @param string $templatePath
     * @return string|string[]
     */
    protected function generateModel(string $templatePath)
    {
        $text = '';
        if (file_exists($templatePath = $this->getTrueTemplatePath($templatePath))) {
            $text = file_get_contents($templatePath);
        }

        $joinTemplate = file_get_contents($this->getTrueTemplatePath(str_replace_first('.', 'Joins.', $templatePath)));
        $text = str_replace(['MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//MAPPINGCHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', 'LEFTJOINS', '//RECHERCHE', '//VALIDATION', 'EDITCHAMPS', 'INSERTCOLUMNS', 'INSERTVALUES'], [
            $this->namespaceName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes(), $this->model->getModalTitle(),
            $this->model->getSqlSelectFields(), $this->model->getJoins($joinTemplate),
            $this->model->getSearchCriteria(),
            $this->model->getValidationCriteria(), $this->model->getEditFields(),
            $this->model->getInsertColumns(),$this->model->getInsertValues()], $text);

        return $text;
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