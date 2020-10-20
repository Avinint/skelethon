<?php

namespace ESM;

class ESMModuleMakerLegacy extends ESMModuleMaker
{
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

        $joinTemplate = file_get_contents($this->getTrueTemplatePath($templatePath->add('joins')));
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
}