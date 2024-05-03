<?php


namespace ESM;


use Core\App;
use Core\ModuleMakerFactory;
use Core\ProjectType;
use E2D\E2DDatabaseAccess;
use E2D\E2DField;
use E2D\E2DModelMaker;
use E2D\E2DModuleMaker;
use E2D\E2DModuleMakerFactory;

class   ESMModuleMakerFactory extends E2DModuleMakerFactory
{

    public function initializeFileGenerators(App $app)
    {
        $app->modelFileGeneratorClass       = ESMModelFileGenerator::class;
        $app->controllerFileGeneratorClass  = ESMControllerFileGenerator::class;
        $app->viewFileGeneratorClass        = ESMViewFileGenerator::class;
        $app->jSFileGeneratorClass          = ESMJSFileGenerator::class;
        $app->configFileGeneratorClass      = ESMConfigFileGenerator::class;
    }

    protected function initializeComponents()
    {
        $this->databaseAccess = ESMDatabaseAccess::class;
        $this->modelMaker = ESMModelMaker::class;
        $this->moduleMaker = ESMModuleMaker::class;
        $this->fieldClass = ESMField::class;
    }
}