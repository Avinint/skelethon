<?php


namespace E2D;


use Core\App;
use Core\ModuleMakerFactory;
use Core\ProjectType;

class E2DModuleMakerFactory extends ModuleMakerFactory
{
    public function __construct(ProjectType $type, $arguments, $appDir)
    {
        $this->databaseAccess = E2DDatabaseAccess::class;
        $this->modelMaker = E2DModelMaker::class;
        $this->moduleMaker = E2DModuleMaker::class;
        $this->fieldClass = E2DField::class;



        parent::__construct($type, $arguments, $appDir);
    }

    public function initializeFileGenerators(App $app)
    {
        $app->modelFileGenerator       = E2DModelFileGenerator::class;
        $app->controllerFileGenerator  = E2DControllerFileGenerator::class;
        $app->viewFileGenerator        = E2DViewFileGenerator::class;
        $app->jSFileGenerator          = E2DJSFileGenerator::class;
        $app->configFileGenerator      = E2DConfigFileGenerator::class;
    }
}