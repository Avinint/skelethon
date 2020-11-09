<?php


namespace E2D;

use Core\App;
use Core\BaseMaker;
use Core\FileGenerator;
use Core\FilePath;

class E2DModelFileGenerator extends FileGenerator
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app= $app;
        $this->model = $app->getModelMaker();
        $this->moduleName = $app->getModuleMaker()->getName();
    }

    public function generate(FilePath $path) : string
    {
        $templatePath = $this->getTrueTemplatePath($path);
        if (isset($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        $joinTemplate = file($this->getTrueTemplatePath($templatePath->add('joins')));
        $text = str_replace([
            '//METHODS', 'MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//MAPPINGCHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', 'LEFTJOINS', '//RECHERCHE', '//VALIDATION'
        ],[
            $this->getMethods($templatePath),
            $this->moduleName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes($this->getTrueTemplatePath($path->add('fieldMapping'))), $this->model->getModalTitle($templatePath),
            $this->model->getSqlSelectFields($this->getTrueTemplatePath($templatePath->add('selectFields'))),
            $this->model->getJoins($joinTemplate),
            $this->model->getSearchCriteria($this->getTrueTemplatePath($templatePath->add('searchCriterion'))),
            $this->model->getValidationCriteria($this->getTrueTemplatePath($templatePath->add('validationCriterion')))], $text);

        return $text;
    }

    public function getMethods($path)
    {
        if ($this->app->get('displayModelSqlMethods') ?? false) {
            return file_get_contents($this->getTrueTemplatePath($path->add('methods')));
        }

        return '';
    }
}