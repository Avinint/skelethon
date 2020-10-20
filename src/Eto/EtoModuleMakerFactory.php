<?php


namespace Eto;


use Core\App;
use Core\ProjectType;
use E2D\E2DDatabaseAccess;
use E2D\E2DField;
use E2D\E2DModelMaker;
use E2D\E2DModuleMaker;
use E2D\E2DModuleMakerFactory;
use ESM\ESMDatabaseAccess;
use ESM\ESMField;
use ESM\ESMModelMaker;
use ESM\ESMModuleMaker;

class EtoModuleMakerFactory extends E2DModuleMakerFactory
{

    public function initializeFileGenerators(App $app)
    {
        $app->modelFileGeneratorClass       = EtoModelFileGenerator::class;
        $app->controllerFileGeneratorClass  = EtoControllerFileGenerator::class;
        $app->viewFileGeneratorClass        = EtoViewFileGenerator::class;
        $app->jSFileGeneratorClass          = EtoJSFileGenerator::class;
        $app->configFileGeneratorClass      = EtoConfigFileGenerator::class;
    }

    protected function initializeComponents()
    {
        $this->databaseAccess = EtoDatabaseAccess::class;
        $this->modelMaker = EtoModelMaker::class;
        $this->moduleMaker = EtoModuleMaker::class;
        $this->fieldClass = EtoField::class;
    }
}