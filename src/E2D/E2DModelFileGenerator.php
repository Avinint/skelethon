<?php


namespace E2D;

use Core\FileGenerator;

class E2DModelFileGenerator extends FileGenerator
{
    public function __construct(string $moduleName, object $model)
    {
        parent::__construct($model->fileManager);
        $this->model = $model;
        $this->moduleName = $moduleName;
    }

    public function generate(string $path) : string
    {
        //$this->templatePath = $path;

        if (file_exists($templatePath = $this->getTrueTemplatePath($path))) {
            $text = file_get_contents($templatePath);
        }

        $joinTemplate = file_get_contents($this->getTrueTemplatePath(str_replace_first('.', 'Joins.', $templatePath)));
        $text = str_replace(['MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//MAPPINGCHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', 'LEFTJOINS', '//RECHERCHE', '//VALIDATION'], [
            $this->moduleName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes(), $this->model->getModalTitle($templatePath),
            $this->model->getSqlSelectFields(), $this->model->getJoins($joinTemplate),
            $this->model->getSearchCriteria(),
            $this->model->getValidationCriteria()], $text);

        return $text;
    }



}