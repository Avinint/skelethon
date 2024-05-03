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
        $this->modulePascalCaseName = $app->getModuleMaker()->getNamespaceName();
        $this->moduleName = $app->getModuleMaker()->getName();
    }

    public function generateMapping(FilePath $path, $table)
    {
        $templatePath = $this->getTrueTemplatePath($path);
        if (isset($templatePath)) {
            $text = file_get_contents($templatePath);
        }


        $text = str_replace([
            'MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', 'CHAMPS', 'CLASSES'
        ],[
            $this->modulePascalCaseName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            ...$this->model->getAttributes($this->getTrueTemplatePath($path->add('fieldMapping')), $table),
        ], $text);

        return $text;
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
            $this->modulePascalCaseName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes($this->getTrueTemplatePath($path->add('fieldMapping'))), $this->model->getModalTitle(),
            $this->model->getSqlSelectFields($this->getTrueTemplatePath($templatePath->add('selectFields'))),
            $this->model->getJoins($joinTemplate),
            $this->model->getSearchCriteria($this->getTrueTemplatePath($templatePath->add('searchCriterion'))),
            $this->model->getValidationCriteria($this->getTrueTemplatePath($templatePath->add('validationCriterion')))
        ], $text);

        return $text;
    }

    public function getMethods($path)
    {
        $methodText = [];
        if ($this->app->get('displayModelSqlMethods') || $this->app->get('legacy')) {
            $text = file_get_contents($this->getTrueTemplatePath($path->add('methods')));

            if ($this->app->get('legacy')) {
                $text = str_replace(['EDITCHAMPS', 'INSERTCOLUMNS', 'INSERTVALUES'],
                    [$this->model->getEditFields(), $this->model->getInsertColumns(), $this->model->getInsertValues()],$text);
            }

            $methodText[] = $text;
        }

        if ($this->model->hasAction('export') && !$this->app->get('avecRessourceExport'))
            $methodText[] = str_replace(['mODULE', 'MODEL'], [$this->moduleName, $this->model->getClassName()],
                file_get_contents($this->getTrueTemplatePath($path->add('export'))));

        return empty($methodText) ? '' : PHP_EOL.implode(PHP_EOL, $methodText);
    }
}