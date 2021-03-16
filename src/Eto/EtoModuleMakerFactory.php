<?php


namespace Eto;

use E2D\E2DModuleMakerFactory;


class EtoModuleMakerFactory extends E2DModuleMakerFactory
{
//    public function initializeFileGenerators(App $app)
//    {
//        $app->modelFileGeneratorClass       = EtoModelFileGenerator::class;
//        $app->controllerFileGeneratorClass  = EtoControllerFileGenerator::class;
//        $app->viewFileGeneratorClass        = EtoViewFileGenerator::class;
//        $app->jSFileGeneratorClass          = EtoJSFileGenerator::class;
//        $app->configFileGeneratorClass      = EtoConfigFileGenerator::class;
//    }

    protected function initializeComponents()
    {
        $this->databaseAccess = EtoDatabaseAccess::class;
        $this->modelMaker = EtoModelMaker::class;
        $this->moduleMaker = EtoModuleMaker::class;
        $this->fieldClass = EtoField::class;
    }
}