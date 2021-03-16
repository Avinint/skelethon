<?php


namespace DDD;


use Core\App;
use Core\ModuleMakerFactory;
use Core\ProjectType;
use E2D\E2DDatabaseAccess;
use E2D\E2DField;
use E2D\E2DModelMaker;
use E2D\E2DModuleMaker;
use E2D\E2DModuleMakerFactory;

class DDDModuleMakerFactory extends E2DModuleMakerFactory
{

    public function initializeFileGenerators(App $app)
    {
        $app->modelFileGeneratorClass       = E2DModelFileGenerator::class;
        $app->controllerFileGeneratorClass  = E2DControllerFileGenerator::class;
        $app->viewFileGeneratorClass        = E2DViewFileGenerator::class;
        $app->jSFileGeneratorClass          = E2DJSFileGenerator::class;
        $app->configFileGeneratorClass      = E2DConfigFileGenerator::class;
        $app->entityFileGeneratorClass      = EntityFileGenerator::class;
    }

}