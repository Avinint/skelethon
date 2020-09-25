<?php


namespace E2D;

use Core\BaseMaker;
use Core\FileGenerator;

class E2DModelFileGenerator extends FileGenerator
{

    public function __construct(string $moduleName, object $model, $config)
    {
        $this->config = $config;
        BaseMaker::__construct($model->getFileManager());
        $this->model = $model;
        $this->moduleName = $moduleName;
    }

    public function generate(string $path) : string
    {
        //$this->templatePath = $path;

        if (file_exists($templatePath = $this->getTrueTemplatePath($path))) {
            $text = file_get_contents($templatePath);
        }

        $joinTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, 'MODEL_joins.class', 'MODEL.class'));
        $text = str_replace([
            '//METHODS', 'MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//MAPPINGCHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', 'LEFTJOINS', '//RECHERCHE', '//VALIDATION'
        ],[
            $this->getMethods($templatePath),
            $this->moduleName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes($this->getTrueTemplatePath($path, 'MODEL_fieldmapping.class', 'MODEL.class')), $this->model->getModalTitle($templatePath),
            $this->model->getSqlSelectFields($this->getTrueTemplatePath($templatePath, 'MODEL_selectfields.class', 'MODEL.class')),
            $this->model->getJoins($joinTemplate),
            $this->model->getSearchCriteria($this->getTrueTemplatePath($templatePath, 'MODEL_searchCriterion.class', 'MODEL.class')),
            $this->model->getValidationCriteria($this->getTrueTemplatePath($templatePath, 'MODEL_validationCriterion.class', 'MODEL.class'))], $text);

        return $text;
    }

    public function getMethods($path)
    {
        if ($this->model->getConfig()->get('displayModelSqlMethods') ?? false) {
            return file_get_contents(str_replace_first('.', '_methods.', $path));
        }

        return '';
    }
}